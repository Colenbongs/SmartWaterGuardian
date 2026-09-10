<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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
        if (isset($_GET['meter_id']) && isset($_GET['days'])) {
            $meterId = $_GET['meter_id'];
            $days = intval($_GET['days']) ?: 7;
            
            $stmt = $conn->prepare("SELECT * FROM water_readings WHERE meter_id = ? AND reading_time >= DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY reading_time DESC");
            $stmt->bind_param("si", $meterId, $days);
            $stmt->execute();
            $result = $stmt->get_result();
            $readings = $result->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode(['success' => true, 'readings' => $readings]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
        }
        break;
        
    case 'POST':
        if (!isset($data['meter_id']) || !isset($data['flow_rate']) || !isset($data['volume'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit();
        }
        
        $stmt = $conn->prepare("INSERT INTO water_readings (meter_id, flow_rate, volume, reading_time) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sdd", $data['meter_id'], $data['flow_rate'], $data['volume']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Reading saved successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>