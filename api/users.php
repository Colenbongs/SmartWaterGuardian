<?php
/**
 * Smart Water Guardian - User Management API
 * Syncs data between Firebase and MySQL
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

if ($method === 'GET') {
    if (isset($_GET['all']) && $_GET['all'] === 'true') {
        try {
            $result = $conn->query("
                SELECT id, firebase_uid, email, first_name, last_name, phone, address, role, is_active, is_approved, created_at, updated_at
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
    
    if (!isset($_GET['firebase_uid'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Firebase UID is required'
        ]);
        exit();
    }
    
    $firebase_uid = $_GET['firebase_uid'];
    
    try {
        $stmt = $conn->prepare("
            SELECT id, firebase_uid, email, first_name, last_name, phone, address, role, is_active, is_approved, created_at, updated_at
            FROM users 
            WHERE firebase_uid = ?
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

if ($method === 'POST') {
    $required = ['firebase_uid', 'email', 'first_name', 'last_name'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo json_encode([
                'success' => false,
                'error' => "Missing required field: $field"
            ]);
            exit();
        }
    }
    
    try {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE firebase_uid = ?");
        $check_stmt->bind_param("s", $data['firebase_uid']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        $role = $data['role'] ?? 'consumer';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        $meter_number = $data['meter_number'] ?? '';
        $is_approved = $data['is_approved'] ?? 0;
        
        if ($check_result->num_rows > 0) {
            $stmt = $conn->prepare("
                UPDATE users SET 
                    email = ?,
                    first_name = ?,
                    last_name = ?,
                    phone = ?,
                    address = ?,
                    role = ?,
                    is_approved = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE firebase_uid = ?
            ");
            $stmt->bind_param(
                "ssssssis",
                $data['email'],
                $data['first_name'],
                $data['last_name'],
                $phone,
                $address,
                $role,
                $is_approved,
                $data['firebase_uid']
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO users (
                    firebase_uid, 
                    email, 
                    first_name, 
                    last_name, 
                    phone, 
                    address, 
                    role,
                    is_approved,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param(
                "sssssssi",
                $data['firebase_uid'],
                $data['email'],
                $data['first_name'],
                $data['last_name'],
                $phone,
                $address,
                $role,
                $is_approved
            );
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'User saved to MySQL successfully!',
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

if ($method === 'PUT') {
    if (!isset($data['firebase_uid']) && !isset($data['id'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Firebase UID or ID is required'
        ]);
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
            echo json_encode([
                'success' => false,
                'error' => 'No fields to update'
            ]);
            exit();
        }
        
        if (isset($data['firebase_uid'])) {
            $params[] = $data['firebase_uid'];
            $types .= "s";
            $sql = "UPDATE users SET " . implode(", ", $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE firebase_uid = ?";
        } else {
            $params[] = $data['id'];
            $types .= "i";
            $sql = "UPDATE users SET " . implode(", ", $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'User updated in MySQL successfully!'
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

if ($method === 'DELETE') {
    if (!isset($_GET['firebase_uid']) && !isset($_GET['id'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Firebase UID or ID is required'
        ]);
        exit();
    }
    
    try {
        if (isset($_GET['firebase_uid'])) {
            $stmt = $conn->prepare("DELETE FROM users WHERE firebase_uid = ?");
            $stmt->bind_param("s", $_GET['firebase_uid']);
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $_GET['id']);
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'User deleted from MySQL successfully!'
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

if ($method === 'POST' && isset($data['action']) && $data['action'] === 'sync') {
    if (!isset($data['firebase_uid'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Firebase UID is required'
        ]);
        exit();
    }
    
    try {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE firebase_uid = ?");
        $check_stmt->bind_param("s", $data['firebase_uid']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $stmt = $conn->prepare("
                UPDATE users SET 
                    email = ?,
                    first_name = ?,
                    last_name = ?,
                    phone = ?,
                    address = ?,
                    role = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE firebase_uid = ?
            ");
            $stmt->bind_param(
                "sssssss",
                $data['email'] ?? '',
                $data['first_name'] ?? '',
                $data['last_name'] ?? '',
                $data['phone'] ?? '',
                $data['address'] ?? '',
                $data['role'] ?? 'consumer',
                $data['firebase_uid']
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO users (
                    firebase_uid, 
                    email, 
                    first_name, 
                    last_name, 
                    phone, 
                    address, 
                    role,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param(
                "sssssss",
                $data['firebase_uid'],
                $data['email'] ?? '',
                $data['first_name'] ?? '',
                $data['last_name'] ?? '',
                $data['phone'] ?? '',
                $data['address'] ?? '',
                $data['role'] ?? 'consumer'
            );
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'User synced to MySQL successfully!'
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

echo json_encode([
    'success' => false,
    'error' => "Unsupported HTTP method: $method"
]);
exit();
?>