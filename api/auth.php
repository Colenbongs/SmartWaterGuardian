<?php
/**
 * Smart Water Guardian - Authentication API
 * Handles PHP sessions and login with approval check
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit();
}

$action = $data['action'] ?? '';

switch($action) {
    case 'set_session':
        if (!isset($data['uid']) || !isset($data['email'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit();
        }
        
        $_SESSION['user_id'] = $data['uid'];
        $_SESSION['email'] = $data['email'];
        $_SESSION['firstName'] = $data['firstName'] ?? '';
        $_SESSION['lastName'] = $data['lastName'] ?? '';
        $_SESSION['role'] = $data['role'] ?? 'consumer';
        $_SESSION['logged_in'] = true;
        
        require_once '../config/database.php';
        $stmt = $conn->prepare("
            UPDATE users SET last_login = NOW() WHERE firebase_uid = ?
        ");
        $stmt->bind_param("s", $data['uid']);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $data['uid'],
                'email' => $data['email'],
                'firstName' => $_SESSION['firstName'],
                'lastName' => $_SESSION['lastName'],
                'role' => $_SESSION['role']
            ]
        ]);
        break;
        
    case 'check_session':
        if (isset($_SESSION['user_id']) && $_SESSION['logged_in']) {
            echo json_encode([
                'success' => true,
                'logged_in' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'email' => $_SESSION['email'],
                    'firstName' => $_SESSION['firstName'],
                    'lastName' => $_SESSION['lastName'],
                    'role' => $_SESSION['role']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'logged_in' => false
            ]);
        }
        break;
        
    case 'logout':
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>