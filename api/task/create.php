<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

include_once '../../config/database.php';
include_once '../../config/jwt.php';
include_once '../../models/Task.php';

$user_id = JWT::getUserIdFromToken();

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$task = new Task($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->status_id) && !empty($data->title)) {
    $description = isset($data->description) ? $data->description : null;
    $due_date = isset($data->due_date) ? $data->due_date : null;

    $task_id = $task->create($user_id, $data->status_id, $data->title, $description, $due_date);

    if ($task_id) {
        http_response_code(201);
        echo json_encode(['message' => 'Task created', 'id' => $task_id]);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Unable to create task']);
    }
} else {
    http_response_code(400);
    echo json_encode(['message' => 'Incomplete data']);
}
?>