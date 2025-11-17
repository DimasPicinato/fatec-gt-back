<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: *");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

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