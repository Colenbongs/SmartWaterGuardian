<?php
/**
 * Smart Water Guardian - Activity Log API
 * Logs user activities to MySQL
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['firebase_uid']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

try {
    // Get IP address
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    // Prepare statement
    $stmt = $conn->prepare("
        INSERT INTO audit_logs (
            firebase_uid, 
            action, 
            details, 
            ip_address, 
            user_agent
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    $details = json_encode($data['details'] ?? []);
    
    $stmt->bind_param(
        "sssss",
        $data['firebase_uid'],
        $data['action'],
        $details,
        $ip,
        $user_agent
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Activity logged successfully'
        ]);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => '❌ Database Error: ' . $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();
?>