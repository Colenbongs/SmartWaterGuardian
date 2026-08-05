<?php
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (isset($input['meter_id'])) {
    $_SESSION['meter_id'] = $input['meter_id'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'No meter_id provided']);
}