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

if (!empty($data->id) && !empty($data->name) && !empty($data->stage)) {
	if ($status->update($data->id, $data->name, $data->stage)) {
		http_response_code(200);
		echo json_encode(['message' => 'Status updated']);
	} else {
		http_response_code(400);
		echo json_encode(['message' => 'Unable to update status']);
	}
} else {
	http_response_code(400);
	echo json_encode(['message' => 'Incomplete data']);
}
?>