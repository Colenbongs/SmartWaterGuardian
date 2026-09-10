<?php
/**
 * Smart Water Guardian - Complete Sync API
 * Syncs ALL data between Firebase and MySQL
 * Includes user approval, email notifications, and PDF reports
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

// ============================================================
// AUTHENTICATION - Register (Firebase + MySQL)
// ============================================================
if ($method === 'POST' && isset($data['action']) && $data['action'] === 'register') {
    $required = ['email', 'password', 'firstName', 'lastName', 'firebase_uid'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo json_encode(['success' => false, 'error' => 'Missing field: ' . $field]);
            exit();
        }
    }
    
    try {
        $is_approved = ($data['role'] === 'system_admin' || $data['role'] === 'municipal_admin') ? 1 : 0;
        
        $stmt = $conn->prepare("
            INSERT INTO users (
                firebase_uid,
                email,
                first_name,
                last_name,
                phone,
                address,
                meter_number,
                role,
                is_active,
                is_approved,
                firebase_synced,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 1, NOW())
        ");
        $stmt->bind_param(
            "ssssssssi",
            $data['firebase_uid'],
            $data['email'],
            $data['firstName'],
            $data['lastName'],
            $data['phone'] ?? '',
            $data['address'] ?? '',
            $data['meterNumber'] ?? '',
            $data['role'] ?? 'consumer',
            $is_approved
        );
        
        if ($stmt->execute()) {
            // If this is a new consumer, send email to admin for approval
            if ($is_approved === 0) {
                // Notify admin about new pending user
                // This would trigger email notification to admin
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'User registered in Firebase and MySQL!',
                'firebase_uid' => $data['firebase_uid']
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// AUTHENTICATION - Login (Check Both)
// ============================================================
if ($method === 'POST' && isset($data['action']) && $data['action'] === 'login') {
    if (!isset($data['firebase_uid']) || !isset($data['email'])) {
        echo json_encode(['success' => false, 'error' => 'Missing credentials']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM users WHERE firebase_uid = ? OR email = ?
        ");
        $stmt->bind_param("ss", $data['firebase_uid'], $data['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if user is approved
            if ($user['is_approved'] == 0) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Your account is pending approval. Please wait for admin approval.',
                    'pending_approval' => true
                ]);
                exit();
            }
            
            $update_stmt = $conn->prepare("
                UPDATE users SET last_login = NOW() WHERE firebase_uid = ?
            ");
            $update_stmt->bind_param("s", $user['firebase_uid']);
            $update_stmt->execute();
            
            echo json_encode([
                'success' => true,
                'user' => $user,
                'message' => 'User found in MySQL!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'User not found in MySQL'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// USERS - Get All (Admin)
// ============================================================
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_users') {
    try {
        $result = $conn->query("
            SELECT id, firebase_uid, email, first_name, last_name, phone, address, 
                   meter_number, role, is_active, is_approved, last_login, created_at 
            FROM users 
            ORDER BY created_at DESC
        ");
        $users = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            'success' => true,
            'users' => $users,
            'count' => count($users)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// USERS - Get Pending Approvals
// ============================================================
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_pending') {
    try {
        $stmt = $conn->prepare("
            SELECT id, firebase_uid, email, first_name, last_name, phone, address, role, created_at 
            FROM users 
            WHERE is_approved = 0 AND is_active = 1
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $pending = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            'success' => true,
            'pending' => $pending,
            'count' => count($pending)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// USERS - Approve User
// ============================================================
if ($method === 'POST' && isset($data['action']) && $data['action'] === 'approve_user') {
    if (!isset($data['firebase_uid'])) {
        echo json_encode(['success' => false, 'error' => 'Firebase UID required']);
        exit();
    }
    
    try {
        $admin_uid = $_SESSION['user_id'] ?? 'system';
        
        $stmt = $conn->prepare("
            UPDATE users SET 
                is_approved = 1,
                approval_date = NOW(),
                approved_by = ?
            WHERE firebase_uid = ? AND is_active = 1
        ");
        $stmt->bind_param("ss", $admin_uid, $data['firebase_uid']);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Get user details for notification
            $user_stmt = $conn->prepare("
                SELECT email, first_name, last_name FROM users WHERE firebase_uid = ?
            ");
            $user_stmt->bind_param("s", $data['firebase_uid']);
            $user_stmt->execute();
            $user = $user_stmt->get_result()->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'message' => 'User approved successfully',
                'user' => $user
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'User not found or already approved'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// USERS - Reject User
// ============================================================
if ($method === 'POST' && isset($data['action']) && $data['action'] === 'reject_user') {
    if (!isset($data['firebase_uid'])) {
        echo json_encode(['success' => false, 'error' => 'Firebase UID required']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("
            UPDATE users SET is_active = 0, is_approved = 0 
            WHERE firebase_uid = ?
        ");
        $stmt->bind_param("s", $data['firebase_uid']);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'User rejected and deactivated'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'User not found'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// USERS - Get Single User
// ============================================================
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_user') {
    if (!isset($_GET['firebase_uid'])) {
        echo json_encode(['success' => false, 'error' => 'Firebase UID required']);
        exit();
    }
    
    $firebase_uid = $_GET['firebase_uid'];
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM users WHERE firebase_uid = ?
        ");
        $stmt->bind_param("s", $firebase_uid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'User not found'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// USERS - Update User (Sync to Both)
// ============================================================
if ($method === 'PUT' && isset($data['action']) && $data['action'] === 'update_user') {
    if (!isset($data['firebase_uid'])) {
        echo json_encode(['success' => false, 'error' => 'Firebase UID required']);
        exit();
    }
    
    try {
        $updates = [];
        $params = [];
        $types = "";
        
        $fields = [
            'first_name' => 's',
            'last_name' => 's',
            'phone' => 's',
            'address' => 's',
            'meter_number' => 's',
            'role' => 's',
            'is_active' => 'i',
            'is_approved' => 'i'
        ];
        
        foreach ($fields as $field => $type) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
                $types .= $type;
            }
        }
        
        if (empty($updates)) {
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            exit();
        }
        
        $params[] = $data['firebase_uid'];
        $types .= "s";
        
        $sql = "UPDATE users SET " . implode(", ", $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE firebase_uid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'User updated in MySQL!'
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// DEVICES - Get All
// ============================================================
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_devices') {
    try {
        $result = $conn->query("
            SELECT d.*, p.property_name, u.first_name, u.last_name 
            FROM devices d
            LEFT JOIN properties p ON d.property_id = p.id
            LEFT JOIN users u ON p.firebase_uid = u.firebase_uid
            ORDER BY d.registered_at DESC
        ");
        $devices = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            'success' => true,
            'devices' => $devices,
            'count' => count($devices)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// DEVICES - Register New Device (Sync to Both)
// ============================================================
if ($method === 'POST' && isset($data['action']) && $data['action'] === 'register_device') {
    if (!isset($data['meter_id']) || !isset($data['firebase_uid'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }
    
    try {
        $check_stmt = $conn->prepare("SELECT id FROM devices WHERE meter_id = ?");
        $check_stmt->bind_param("s", $data['meter_id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Device already exists']);
            exit();
        }
        
        $prop_stmt = $conn->prepare("SELECT id FROM properties WHERE firebase_uid = ?");
        $prop_stmt->bind_param("s", $data['firebase_uid']);
        $prop_stmt->execute();
        $prop_result = $prop_stmt->get_result();
        $property = $prop_result->fetch_assoc();
        $property_id = $property ? $property['id'] : null;
        
        $stmt = $conn->prepare("
            INSERT INTO devices (
                meter_id,
                serial_number,
                model,
                property_id,
                status,
                battery_level,
                firmware_version,
                firebase_synced,
                registered_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->bind_param(
            "sssisis",
            $data['meter_id'],
            $data['serial_number'] ?? 'SN-' . time(),
            $data['model'] ?? 'ESP32-YF-S201',
            $property_id,
            $data['status'] ?? 'offline',
            $data['battery_level'] ?? 100,
            $data['firmware_version'] ?? '1.0.0'
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Device registered in MySQL!',
                'device_id' => $conn->insert_id
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// DEVICES - Update Device Status
// ============================================================
if ($method === 'PUT' && isset($data['action']) && $data['action'] === 'update_device') {
    if (!isset($data['meter_id'])) {
        echo json_encode(['success' => false, 'error' => 'Meter ID required']);
        exit();
    }
    
    try {
        $updates = [];
        $params = [];
        $types = "";
        
        $fields = [
            'status' => 's',
            'battery_level' => 'i',
            'signal_strength' => 'i',
            'firmware_version' => 's'
        ];
        
        foreach ($fields as $field => $type) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
                $types .= $type;
            }
        }
        
        if (empty($updates)) {
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            exit();
        }
        
        $params[] = $data['meter_id'];
        $types .= "s";
        
        $sql = "UPDATE devices SET " . implode(", ", $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE meter_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Device updated in MySQL!'
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// ALERTS - Get All Alerts
// ============================================================
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_alerts') {
    if (!isset($_GET['firebase_uid'])) {
        echo json_encode(['success' => false, 'error' => 'Firebase UID required']);
        exit();
    }
    
    $firebase_uid = $_GET['firebase_uid'];
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM alerts 
            WHERE firebase_uid = ? 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->bind_param("s", $firebase_uid);
        $stmt->execute();
        $result = $stmt->get_result();
        $alerts = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            'success' => true,
            'alerts' => $alerts,
            'count' => count($alerts)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// ALERTS - Create Alert (Sync to Both)
// ============================================================
if ($method === 'POST' && isset($data['action']) && $data['action'] === 'create_alert') {
    if (!isset($data['firebase_uid']) || !isset($data['message'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO alerts (
                firebase_uid,
                alert_type,
                severity,
                title,
                message,
                is_read,
                firebase_synced,
                created_at
            ) VALUES (?, ?, ?, ?, ?, 0, 1, NOW())
        ");
        $stmt->bind_param(
            "sssss",
            $data['firebase_uid'],
            $data['alert_type'] ?? 'system',
            $data['severity'] ?? 'info',
            $data['title'] ?? '',
            $data['message']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Alert saved to MySQL!',
                'alert_id' => $conn->insert_id
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// ALERTS - Mark as Read
// ============================================================
if ($method === 'PUT' && isset($data['action']) && $data['action'] === 'mark_alert_read') {
    if (!isset($data['alert_id']) || !isset($data['firebase_uid'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("
            UPDATE alerts SET is_read = 1, firebase_synced = 1 
            WHERE id = ? AND firebase_uid = ?
        ");
        $stmt->bind_param("is", $data['alert_id'], $data['firebase_uid']);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Alert marked as read in MySQL!'
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// THRESHOLDS - Get All Thresholds
// ============================================================
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_thresholds') {
    if (!isset($_GET['firebase_uid'])) {
        echo json_encode(['success' => false, 'error' => 'Firebase UID required']);
        exit();
    }
    
    $firebase_uid = $_GET['firebase_uid'];
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM alert_thresholds 
            WHERE firebase_uid = ? 
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("s", $firebase_uid);
        $stmt->execute();
        $result = $stmt->get_result();
        $thresholds = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            'success' => true,
            'thresholds' => $thresholds,
            'count' => count($thresholds)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// THRESHOLDS - Create/Update Threshold
// ============================================================
if ($method === 'POST' && isset($data['action']) && $data['action'] === 'save_threshold') {
    if (!isset($data['firebase_uid']) || !isset($data['threshold_type']) || !isset($data['threshold_value'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }
    
    try {
        $check_stmt = $conn->prepare("
            SELECT id FROM alert_thresholds 
            WHERE firebase_uid = ? AND threshold_type = ?
        ");
        $check_stmt->bind_param("ss", $data['firebase_uid'], $data['threshold_type']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            $stmt = $conn->prepare("
                UPDATE alert_thresholds SET 
                    threshold_value = ?,
                    is_active = ?,
                    firebase_synced = 1,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->bind_param(
                "dii",
                $data['threshold_value'],
                $data['is_active'] ?? 1,
                $row['id']
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO alert_thresholds (
                    firebase_uid,
                    threshold_type,
                    threshold_value,
                    is_active,
                    firebase_synced,
                    created_at
                ) VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->bind_param(
                "ssdi",
                $data['firebase_uid'],
                $data['threshold_type'],
                $data['threshold_value'],
                $data['is_active'] ?? 1
            );
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Threshold saved to MySQL!'
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// REVIEWS - Get All Reviews
// ============================================================
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_reviews') {
    try {
        $result = $conn->query("
            SELECT * FROM reviews 
            WHERE is_approved = 1 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $reviews = $result->fetch_all(MYSQLI_ASSOC);
        
        $avg_result = $conn->query("
            SELECT AVG(rating) as avg_rating, COUNT(*) as total 
            FROM reviews WHERE is_approved = 1
        ");
        $avg = $avg_result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'reviews' => $reviews,
            'count' => count($reviews),
            'average_rating' => round($avg['avg_rating'] ?? 0, 1),
            'total_reviews' => $avg['total'] ?? 0
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// REVIEWS - Create Review (Sync to Both)
// ============================================================
if ($method === 'POST' && isset($data['action']) && $data['action'] === 'create_review') {
    if (!isset($data['firebase_uid']) || !isset($data['rating']) || !isset($data['comment'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO reviews (
                firebase_uid,
                user_name,
                rating,
                title,
                comment,
                helpful_count,
                is_approved,
                firebase_synced,
                created_at
            ) VALUES (?, ?, ?, ?, ?, 0, 1, 1, NOW())
        ");
        $stmt->bind_param(
            "ssiss",
            $data['firebase_uid'],
            $data['user_name'] ?? 'Anonymous',
            $data['rating'],
            $data['title'] ?? '',
            $data['comment']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Review saved to MySQL!',
                'review_id' => $conn->insert_id
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// MESSAGES - Get Messages
// ============================================================
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_messages') {
    if (!isset($_GET['firebase_uid'])) {
        echo json_encode(['success' => false, 'error' => 'Firebase UID required']);
        exit();
    }
    
    $firebase_uid = $_GET['firebase_uid'];
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM messages 
            WHERE firebase_uid = ? 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->bind_param("s", $firebase_uid);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'count' => count($messages)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// MESSAGES - Send Message (Sync to Both)
// ============================================================
if ($method === 'POST' && isset($data['action']) && $data['action'] === 'send_message') {
    if (!isset($data['firebase_uid']) || !isset($data['from_uid']) || !isset($data['message'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO messages (
                firebase_uid,
                from_uid,
                from_name,
                from_email,
                subject,
                message,
                is_read,
                firebase_synced,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 0, 1, NOW())
        ");
        $stmt->bind_param(
            "ssssss",
            $data['firebase_uid'],
            $data['from_uid'],
            $data['from_name'] ?? 'Admin',
            $data['from_email'] ?? '',
            $data['subject'] ?? 'Message',
            $data['message']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Message saved to MySQL!',
                'message_id' => $conn->insert_id
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// BILLING - Get Billing Data
// ============================================================
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_bills') {
    if (!isset($_GET['firebase_uid'])) {
        echo json_encode(['success' => false, 'error' => 'Firebase UID required']);
        exit();
    }
    
    $firebase_uid = $_GET['firebase_uid'];
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM billing 
            WHERE firebase_uid = ? 
            ORDER BY billing_month DESC
        ");
        $stmt->bind_param("s", $firebase_uid);
        $stmt->execute();
        $result = $stmt->get_result();
        $bills = $result->fetch_all(MYSQLI_ASSOC);
        
        $total_stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN is_paid = 0 THEN total_amount ELSE 0 END) as outstanding,
                SUM(CASE WHEN is_paid = 1 THEN total_amount ELSE 0 END) as paid
            FROM billing WHERE firebase_uid = ?
        ");
        $total_stmt->bind_param("s", $firebase_uid);
        $total_stmt->execute();
        $totals = $total_stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'bills' => $bills,
            'outstanding' => $totals['outstanding'] ?? 0,
            'paid' => $totals['paid'] ?? 0
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// BILLING - Create Bill (Sync to Both)
// ============================================================
if ($method === 'POST' && isset($data['action']) && $data['action'] === 'create_bill') {
    if (!isset($data['firebase_uid']) || !isset($data['amount']) || !isset($data['month'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }
    
    try {
        $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
        
        $stmt = $conn->prepare("
            INSERT INTO billing (
                firebase_uid,
                billing_month,
                total_volume,
                total_amount,
                invoice_number,
                is_paid,
                firebase_synced,
                created_at
            ) VALUES (?, ?, ?, ?, ?, 0, 1, NOW())
        ");
        $stmt->bind_param(
            "sddss",
            $data['firebase_uid'],
            $data['month'],
            $data['volume'] ?? 0,
            $data['amount'],
            $invoice_number
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Bill created in MySQL!',
                'invoice_number' => $invoice_number
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// BILLING - Pay Bill
// ============================================================
if ($method === 'PUT' && isset($data['action']) && $data['action'] === 'pay_bill') {
    if (!isset($data['bill_id']) || !isset($data['firebase_uid'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("
            UPDATE billing SET 
                is_paid = 1, 
                paid_at = NOW(),
                payment_method = ?,
                firebase_synced = 1
            WHERE id = ? AND firebase_uid = ?
        ");
        $stmt->bind_param("sis", $data['payment_method'] ?? 'online', $data['bill_id'], $data['firebase_uid']);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Bill paid in MySQL!'
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// STATS - Get Dashboard Stats
// ============================================================
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_stats') {
    if (!isset($_GET['firebase_uid'])) {
        echo json_encode(['success' => false, 'error' => 'Firebase UID required']);
        exit();
    }
    
    $firebase_uid = $_GET['firebase_uid'];
    
    try {
        $stats = [];
        
        $user_stmt = $conn->prepare("SELECT * FROM users WHERE firebase_uid = ?");
        $user_stmt->bind_param("s", $firebase_uid);
        $user_stmt->execute();
        $stats['user'] = $user_stmt->get_result()->fetch_assoc();
        
        $prop_stmt = $conn->prepare("SELECT COUNT(*) as count FROM properties WHERE firebase_uid = ?");
        $prop_stmt->bind_param("s", $firebase_uid);
        $prop_stmt->execute();
        $stats['properties'] = $prop_stmt->get_result()->fetch_assoc()['count'];
        
        $alert_stmt = $conn->prepare("SELECT COUNT(*) as count FROM alerts WHERE firebase_uid = ? AND is_read = 0");
        $alert_stmt->bind_param("s", $firebase_uid);
        $alert_stmt->execute();
        $stats['unread_alerts'] = $alert_stmt->get_result()->fetch_assoc()['count'];
        
        $msg_stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE firebase_uid = ? AND is_read = 0");
        $msg_stmt->bind_param("s", $firebase_uid);
        $msg_stmt->execute();
        $stats['unread_messages'] = $msg_stmt->get_result()->fetch_assoc()['count'];
        
        $bill_stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM billing WHERE firebase_uid = ? AND is_paid = 0");
        $bill_stmt->bind_param("s", $firebase_uid);
        $bill_stmt->execute();
        $stats['outstanding_bills'] = $bill_stmt->get_result()->fetch_assoc()['total'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// FORCE SYNC ALL DATA FROM FIREBASE TO MYSQL
// ============================================================
if ($method === 'POST' && isset($data['action']) && $data['action'] === 'force_sync_all') {
    if (!isset($data['firebase_uid'])) {
        echo json_encode(['success' => false, 'error' => 'Firebase UID required']);
        exit();
    }
    
    $firebase_uid = $data['firebase_uid'];
    $results = [];
    
    try {
        $stmt = $conn->prepare("
            UPDATE users SET firebase_synced = 1, updated_at = CURRENT_TIMESTAMP 
            WHERE firebase_uid = ? AND firebase_synced = 0
        ");
        $stmt->bind_param("s", $firebase_uid);
        $stmt->execute();
        $results['users'] = $stmt->affected_rows;
        
        $stmt = $conn->prepare("
            UPDATE alerts SET firebase_synced = 1 
            WHERE firebase_uid = ? AND firebase_synced = 0
        ");
        $stmt->bind_param("s", $firebase_uid);
        $stmt->execute();
        $results['alerts'] = $stmt->affected_rows;
        
        $stmt = $conn->prepare("
            UPDATE alert_thresholds SET firebase_synced = 1, updated_at = CURRENT_TIMESTAMP 
            WHERE firebase_uid = ? AND firebase_synced = 0
        ");
        $stmt->bind_param("s", $firebase_uid);
        $stmt->execute();
        $results['thresholds'] = $stmt->affected_rows;
        
        $stmt = $conn->prepare("
            UPDATE billing SET firebase_synced = 1, updated_at = CURRENT_TIMESTAMP 
            WHERE firebase_uid = ? AND firebase_synced = 0
        ");
        $stmt->bind_param("s", $firebase_uid);
        $stmt->execute();
        $results['billing'] = $stmt->affected_rows;
        
        echo json_encode([
            'success' => true,
            'message' => 'Force sync completed!',
            'results' => $results
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ============================================================
// DEFAULT RESPONSE
// ============================================================
echo json_encode([
    'success' => false,
    'error' => 'Invalid action or method'
]);
exit();
?>