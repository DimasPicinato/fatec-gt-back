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

if (!empty($data->id)) {
	if ($status->delete($data->id)) {
		http_response_code(200);
		echo json_encode(['message' => 'Status deleted']);
	} else {
		http_response_code(400);
		echo json_encode(['message' => 'Unable to delete status']);
	}
} else {
	http_response_code(400);
	echo json_encode(['message' => 'ID required']);
}
?>