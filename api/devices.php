<?php
/**
 * Smart Water Guardian - Device Management API
 * Handles all device-related operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
require_once '../config/database.php';

// ============================
// AUTHENTICATION CHECK
// ============================
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    echo json_encode([
        'success' => false,
        'error' => '❌ Unauthorized access. Please login first.',
        'code' => 'UNAUTHORIZED'
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

// ============================
// GET DEVICES
// ============================
if ($method === 'GET') {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    try {
        if ($role === 'system_admin' || $role === 'municipal_admin') {
            // Admin can see all devices
            $query = "
                SELECT 
                    d.id,
                    d.meter_id,
                    d.serial_number,
                    d.model,
                    d.firmware_version,
                    d.status,
                    d.battery_level,
                    d.signal_strength,
                    d.last_communication,
                    d.installed_at,
                    d.registered_at,
                    p.id as property_id,
                    p.property_name,
                    p.address,
                    u.firebase_uid,
                    u.first_name,
                    u.last_name,
                    u.email
                FROM devices d
                LEFT JOIN properties p ON d.property_id = p.id
                LEFT JOIN users u ON p.firebase_uid = u.firebase_uid
                ORDER BY d.registered_at DESC
            ";
            $result = $conn->query($query);
        } else {
            // Consumer can only see their own devices
            $query = "
                SELECT 
                    d.id,
                    d.meter_id,
                    d.serial_number,
                    d.model,
                    d.firmware_version,
                    d.status,
                    d.battery_level,
                    d.signal_strength,
                    d.last_communication,
                    d.installed_at,
                    d.registered_at,
                    p.id as property_id,
                    p.property_name,
                    p.address
                FROM devices d
                LEFT JOIN properties p ON d.property_id = p.id
                WHERE p.firebase_uid = ?
                ORDER BY d.registered_at DESC
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        $devices = [];
        while ($row = $result->fetch_assoc()) {
            // Get latest reading from Firebase (if available)
            $meter_id = $row['meter_id'];
            $firebase_data = null;
            
            try {
                // We'll get this from Firebase in the frontend
                // For API response, we just return the device info
                $row['latest_reading'] = null;
                $row['last_reading_time'] = $row['last_communication'];
            } catch (Exception $e) {
                // Firebase not available
                $row['latest_reading'] = null;
            }
            
            $devices[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'devices' => $devices,
            'count' => count($devices)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => '❌ Failed to fetch devices: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================
// REGISTER DEVICE (POST)
// ============================
if ($method === 'POST') {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // Only admins can register devices
    if ($role !== 'system_admin' && $role !== 'municipal_admin') {
        echo json_encode([
            'success' => false,
            'error' => '❌ Only administrators can register devices'
        ]);
        exit();
    }
    
    // Validate required fields
    $required = ['meter_id', 'property_id'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo json_encode([
                'success' => false,
                'error' => "❌ Missing required field: $field"
            ]);
            exit();
        }
    }
    
    // Check if meter_id already exists
    $check_stmt = $conn->prepare("SELECT id FROM devices WHERE meter_id = ?");
    $check_stmt->bind_param("s", $data['meter_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'error' => "❌ Device with meter ID '{$data['meter_id']}' already exists"
        ]);
        exit();
    }
    
    // Check if property exists
    $prop_stmt = $conn->prepare("SELECT id FROM properties WHERE id = ?");
    $prop_stmt->bind_param("i", $data['property_id']);
    $prop_stmt->execute();
    $prop_result = $prop_stmt->get_result();
    
    if ($prop_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => "❌ Property with ID '{$data['property_id']}' not found"
        ]);
        exit();
    }
    
    // Prepare device data
    $serial_number = $data['serial_number'] ?? 'SN-' . date('Ymd') . '-' . rand(1000, 9999);
    $model = $data['model'] ?? 'ESP32-YF-S201';
    $firmware_version = $data['firmware_version'] ?? '1.0.0';
    $status = $data['status'] ?? 'offline';
    $battery_level = $data['battery_level'] ?? 100;
    $signal_strength = $data['signal_strength'] ?? 0;
    $installed_at = $data['installed_at'] ?? date('Y-m-d H:i:s');
    
    try {
        // Insert into database
        $stmt = $conn->prepare("
            INSERT INTO devices (
                meter_id, 
                serial_number, 
                model, 
                firmware_version,
                property_id, 
                status, 
                battery_level, 
                signal_strength,
                installed_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "ssssisids",
            $data['meter_id'],
            $serial_number,
            $model,
            $firmware_version,
            $data['property_id'],
            $status,
            $battery_level,
            $signal_strength,
            $installed_at
        );
        
        if ($stmt->execute()) {
            $device_id = $conn->insert_id;
            
            // Log the action
            logAction($_SESSION['user_id'], 'device_registered', 'device', $data['meter_id'], [
                'meter_id' => $data['meter_id'],
                'property_id' => $data['property_id'],
                'model' => $model
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => "✅ Device registered successfully! 🎉",
                'device_id' => $device_id,
                'meter_id' => $data['meter_id']
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => '❌ Failed to register device: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================
// UPDATE DEVICE (PUT)
// ============================
if ($method === 'PUT') {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // Only admins can update devices
    if ($role !== 'system_admin' && $role !== 'municipal_admin') {
        echo json_encode([
            'success' => false,
            'error' => '❌ Only administrators can update devices'
        ]);
        exit();
    }
    
    // Validate required fields
    if (!isset($data['meter_id']) || empty($data['meter_id'])) {
        echo json_encode([
            'success' => false,
            'error' => '❌ Meter ID is required'
        ]);
        exit();
    }
    
    // Check if device exists
    $check_stmt = $conn->prepare("SELECT id FROM devices WHERE meter_id = ?");
    $check_stmt->bind_param("s", $data['meter_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => "❌ Device with meter ID '{$data['meter_id']}' not found"
        ]);
        exit();
    }
    
    // Build update query
    $updates = [];
    $params = [];
    $types = "";
    
    $fields = [
        'serial_number' => 's',
        'model' => 's',
        'firmware_version' => 's',
        'property_id' => 'i',
        'status' => 's',
        'battery_level' => 'i',
        'signal_strength' => 'i'
    ];
    
    foreach ($fields as $field => $type) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
            $types .= $type;
        }
    }
    
    if (empty($updates)) {
        echo json_encode([
            'success' => false,
            'error' => '❌ No fields to update'
        ]);
        exit();
    }
    
    // Add meter_id to params
    $params[] = $data['meter_id'];
    $types .= "s";
    
    $sql = "UPDATE devices SET " . implode(", ", $updates) . " WHERE meter_id = ?";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Log the action
            logAction($_SESSION['user_id'], 'device_updated', 'device', $data['meter_id'], [
                'meter_id' => $data['meter_id'],
                'updates' => array_intersect_key($data, $fields)
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => "✅ Device updated successfully! 🔧"
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => '❌ Failed to update device: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================
// DELETE DEVICE (DELETE)
// ============================
if ($method === 'DELETE') {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // Only system admins can delete devices
    if ($role !== 'system_admin') {
        echo json_encode([
            'success' => false,
            'error' => '❌ Only system administrators can delete devices'
        ]);
        exit();
    }
    
    // Get meter_id from query string or request body
    $meter_id = $_GET['meter_id'] ?? ($data['meter_id'] ?? null);
    
    if (!$meter_id) {
        echo json_encode([
            'success' => false,
            'error' => '❌ Meter ID is required'
        ]);
        exit();
    }
    
    // Check if device exists
    $check_stmt = $conn->prepare("SELECT id, property_id FROM devices WHERE meter_id = ?");
    $check_stmt->bind_param("s", $meter_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => "❌ Device with meter ID '{$meter_id}' not found"
        ]);
        exit();
    }
    
    $device = $check_result->fetch_assoc();
    
    // Check if device has readings
    $reading_stmt = $conn->prepare("SELECT COUNT(*) as count FROM water_readings WHERE meter_id = ?");
    $reading_stmt->bind_param("s", $meter_id);
    $reading_stmt->execute();
    $reading_result = $reading_stmt->get_result();
    $reading_count = $reading_result->fetch_assoc()['count'];
    
    if ($reading_count > 0) {
        echo json_encode([
            'success' => false,
            'error' => "❌ Cannot delete device with {$reading_count} readings. Archive readings first."
        ]);
        exit();
    }
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Delete from devices
        $delete_stmt = $conn->prepare("DELETE FROM devices WHERE meter_id = ?");
        $delete_stmt->bind_param("s", $meter_id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception($delete_stmt->error);
        }
        
        // Log the action
        logAction($_SESSION['user_id'], 'device_deleted', 'device', $meter_id, [
            'meter_id' => $meter_id,
            'property_id' => $device['property_id']
        ]);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "✅ Device deleted successfully! 🗑️"
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'error' => '❌ Failed to delete device: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================
// BULK OPERATIONS
// ============================
if ($method === 'POST' && isset($data['action'])) {
    $action = $data['action'];
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // Bulk status update
    if ($action === 'bulk_status_update') {
        if ($role !== 'system_admin' && $role !== 'municipal_admin') {
            echo json_encode([
                'success' => false,
                'error' => '❌ Only administrators can perform bulk updates'
            ]);
            exit();
        }
        
        if (!isset($data['meter_ids']) || !is_array($data['meter_ids']) || empty($data['meter_ids'])) {
            echo json_encode([
                'success' => false,
                'error' => '❌ Please provide meter IDs'
            ]);
            exit();
        }
        
        if (!isset($data['status'])) {
            echo json_encode([
                'success' => false,
                'error' => '❌ Please provide a status'
            ]);
            exit();
        }
        
        $meter_ids = $data['meter_ids'];
        $status = $data['status'];
        
        try {
            // Create placeholders for IN clause
            $placeholders = implode(',', array_fill(0, count($meter_ids), '?'));
            $types = str_repeat('s', count($meter_ids));
            
            $stmt = $conn->prepare("UPDATE devices SET status = ? WHERE meter_id IN ($placeholders)");
            $params = array_merge([$status], $meter_ids);
            $stmt->bind_param("s" . $types, ...$params);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => "✅ Updated " . $stmt->affected_rows . " devices to '{$status}' status 🔄"
                ]);
            } else {
                throw new Exception($stmt->error);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => '❌ Bulk update failed: ' . $e->getMessage()
            ]);
        }
        exit();
    }
    
    // Bulk delete
    if ($action === 'bulk_delete') {
        if ($role !== 'system_admin') {
            echo json_encode([
                'success' => false,
                'error' => '❌ Only system administrators can delete devices'
            ]);
            exit();
        }
        
        if (!isset($data['meter_ids']) || !is_array($data['meter_ids']) || empty($data['meter_ids'])) {
            echo json_encode([
                'success' => false,
                'error' => '❌ Please provide meter IDs'
            ]);
            exit();
        }
        
        $meter_ids = $data['meter_ids'];
        
        try {
            $conn->begin_transaction();
            
            $placeholders = implode(',', array_fill(0, count($meter_ids), '?'));
            $types = str_repeat('s', count($meter_ids));
            
            // Check for readings
            $check_stmt = $conn->prepare("SELECT meter_id, COUNT(*) as count FROM water_readings WHERE meter_id IN ($placeholders) GROUP BY meter_id");
            $check_stmt->bind_param($types, ...$meter_ids);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            $has_readings = [];
            while ($row = $result->fetch_assoc()) {
                $has_readings[$row['meter_id']] = $row['count'];
            }
            
            if (!empty($has_readings)) {
                $list = implode(', ', array_map(function($k, $v) {
                    return "$k ($v readings)";
                }, array_keys($has_readings), $has_readings));
                
                echo json_encode([
                    'success' => false,
                    'error' => "❌ Cannot delete devices with readings: $list. Archive readings first."
                ]);
                exit();
            }
            
            // Delete devices
            $delete_stmt = $conn->prepare("DELETE FROM devices WHERE meter_id IN ($placeholders)");
            $delete_stmt->bind_param($types, ...$meter_ids);
            
            if (!$delete_stmt->execute()) {
                throw new Exception($delete_stmt->error);
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => "✅ Deleted " . $delete_stmt->affected_rows . " devices successfully 🗑️"
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'error' => '❌ Bulk delete failed: ' . $e->getMessage()
            ]);
        }
        exit();
    }
}

// ============================
// DEVICE STATISTICS
// ============================
if ($method === 'GET' && isset($_GET['stats'])) {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    try {
        if ($role === 'system_admin' || $role === 'municipal_admin') {
            // Admin stats - all devices
            $stats_query = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online,
                    SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline,
                    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error,
                    AVG(battery_level) as avg_battery,
                    MIN(battery_level) as min_battery,
                    MAX(battery_level) as max_battery
                FROM devices
            ";
            $result = $conn->query($stats_query);
        } else {
            // Consumer stats - only their devices
            $stats_query = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN d.status = 'online' THEN 1 ELSE 0 END) as online,
                    SUM(CASE WHEN d.status = 'offline' THEN 1 ELSE 0 END) as offline,
                    SUM(CASE WHEN d.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
                    SUM(CASE WHEN d.status = 'error' THEN 1 ELSE 0 END) as error,
                    AVG(d.battery_level) as avg_battery,
                    MIN(d.battery_level) as min_battery,
                    MAX(d.battery_level) as max_battery
                FROM devices d
                LEFT JOIN properties p ON d.property_id = p.id
                WHERE p.firebase_uid = ?
            ";
            $stmt = $conn->prepare($stats_query);
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        $stats = $result->fetch_assoc();
        
        // Add emojis for display
        $stats['status_summary'] = [
            '🟢 Online' => $stats['online'] ?? 0,
            '🔴 Offline' => $stats['offline'] ?? 0,
            '🟡 Maintenance' => $stats['maintenance'] ?? 0,
            '❌ Error' => $stats['error'] ?? 0
        ];
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => '❌ Failed to get device statistics: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================
// DEVICE STATUS CHECK
// ============================
if ($method === 'POST' && isset($data['action']) && $data['action'] === 'check_status') {
    if (!isset($data['meter_id'])) {
        echo json_encode([
            'success' => false,
            'error' => '❌ Meter ID is required'
        ]);
        exit();
    }
    
    $meter_id = $data['meter_id'];
    
    try {
        // Check if device exists
        $stmt = $conn->prepare("SELECT status, battery_level, last_communication FROM devices WHERE meter_id = ?");
        $stmt->bind_param("s", $meter_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'error' => "❌ Device '{$meter_id}' not found"
            ]);
            exit();
        }
        
        $device = $result->fetch_assoc();
        
        // Check if device is online (last communication within 5 minutes)
        $is_online = false;
        if ($device['last_communication']) {
            $last_comm = strtotime($device['last_communication']);
            $now = time();
            $is_online = ($now - $last_comm) < 300; // 5 minutes
        }
        
        // Update status if needed
        $new_status = $is_online ? 'online' : 'offline';
        if ($new_status !== $device['status']) {
            $update_stmt = $conn->prepare("UPDATE devices SET status = ? WHERE meter_id = ?");
            $update_stmt->bind_param("ss", $new_status, $meter_id);
            $update_stmt->execute();
        }
        
        echo json_encode([
            'success' => true,
            'meter_id' => $meter_id,
            'status' => $new_status,
            'battery_level' => $device['battery_level'],
            'last_communication' => $device['last_communication'],
            'is_online' => $is_online,
            'status_emoji' => $is_online ? '🟢' : '🔴'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => '❌ Failed to check device status: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================
// HELPER FUNCTIONS
// ============================

/**
 * Log user actions to audit log
 */
function logAction($user_id, $action, $entity_type, $entity_id, $details = null) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO audit_logs (firebase_uid, action, entity_type, entity_id, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $details_json = $details ? json_encode($details) : null;
        
        $stmt->bind_param(
            "sssssss",
            $user_id,
            $action,
            $entity_type,
            $entity_id,
            $details_json,
            $ip,
            $user_agent
        );
        
        $stmt->execute();
    } catch (Exception $e) {
        // Silent fail for logging
        error_log("Failed to log action: " . $e->getMessage());
    }
}


echo json_encode([
    'success' => false,
    'error' => "❌ Unsupported HTTP method: $method"
]);
exit();
?>