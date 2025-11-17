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
include_once '../../models/User.php';

$user_id = JWT::getUserIdFromToken();

if (!$user_id) {
	http_response_code(401);
	echo json_encode(['message' => 'Unauthorized']);
	exit;
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

$name = isset($data->name) ? $data->name : null;
$password = isset($data->password) ? $data->password : null;
$photo = isset($data->photo) ? $data->photo : null;

if ($user->update($user_id, $name, $password, $photo)) {
	http_response_code(200);
	echo json_encode(['message' => 'User updated successfully']);
} else {
	http_response_code(400);
	echo json_encode(['message' => 'Unable to update user']);
}
?>