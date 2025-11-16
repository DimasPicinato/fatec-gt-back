<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

include_once '../../config/database.php';
include_once '../../config/jwt.php';
include_once '../../models/Status.php';

$user_id = JWT::getUserIdFromToken();

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$status = new Status($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name) && !empty($data->stage)) {
    $status_id = $status->create($data->name, $data->stage);

    if ($status_id) {
        http_response_code(201);
        echo json_encode(['message' => 'Status created', 'id' => $status_id]);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Unable to create status']);
    }
} else {
    http_response_code(400);
    echo json_encode(['message' => 'Incomplete data']);
}
?>