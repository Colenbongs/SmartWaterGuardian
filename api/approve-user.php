<?php
/**
 * Smart Water Guardian - User Approval API
 * Admins can approve or reject user registrations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$role = $_SESSION['role'] ?? 'consumer';
if ($role !== 'system_admin' && $role !== 'municipal_admin' && $role !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

if ($method === 'GET') {
    try {
        $stmt = $conn->prepare("
            SELECT 
                id, firebase_uid, email, first_name, last_name, 
                phone, address, role, created_at, is_active, is_approved
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

if ($method === 'POST' && isset($data['action'])) {
    if (!isset($data['firebase_uid'])) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit();
    }
    
    $firebase_uid = $data['firebase_uid'];
    $admin_uid = $_SESSION['user_id'];
    
    try {
        if ($data['action'] === 'approve') {
            $stmt = $conn->prepare("
                UPDATE users SET 
                    is_approved = 1,
                    approval_date = NOW(),
                    approved_by = ?
                WHERE firebase_uid = ?
            ");
            $stmt->bind_param("ss", $admin_uid, $firebase_uid);
            
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
            
            $user_stmt = $conn->prepare("
                SELECT email, first_name, last_name FROM users WHERE firebase_uid = ?
            ");
            $user_stmt->bind_param("s", $firebase_uid);
            $user_stmt->execute();
            $user = $user_stmt->get_result()->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'message' => 'User approved successfully',
                'user' => $user
            ]);
            
        } elseif ($data['action'] === 'reject') {
            $stmt = $conn->prepare("UPDATE users SET is_active = 0, is_approved = 0 WHERE firebase_uid = ?");
            $stmt->bind_param("s", $firebase_uid);
            
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'User rejected and deactivated'
            ]);
            
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'MySQL Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Method not allowed']);
?>