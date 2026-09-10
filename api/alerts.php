<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

switch($method) {
    case 'GET':
        $firebaseUid = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM alerts WHERE firebase_uid = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->bind_param("s", $firebaseUid);
        $stmt->execute();
        $result = $stmt->get_result();
        $alerts = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'alerts' => $alerts]);
        break;
        
    case 'POST':
        if (!isset($data['alert_type']) || !isset($data['message'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit();
        }
        
        $stmt = $conn->prepare("INSERT INTO alerts (firebase_uid, alert_type, severity, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $_SESSION['user_id'], $data['alert_type'], $data['severity'], $data['message']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Alert created successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        break;
        
    case 'PUT':
        if (!isset($data['alert_id']) || !isset($data['is_read'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit();
        }
        
        $stmt = $conn->prepare("UPDATE alerts SET is_read = ? WHERE id = ? AND firebase_uid = ?");
        $stmt->bind_param("iis", $data['is_read'], $data['alert_id'], $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Alert updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>