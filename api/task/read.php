<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
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

$search = isset($_GET['search']) ? $_GET['search'] : null;
$orderBy = isset($_GET['order_by']) ? $_GET['order_by'] : 'created_at';
$orderDir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'DESC';

$tasks = $task->getAll($user_id, $search, $orderBy, $orderDir);

http_response_code(200);
echo json_encode($tasks);
?>