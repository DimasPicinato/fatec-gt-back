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

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name) && !empty($data->password)) {
	$photo = isset($data->photo) ? $data->photo : null;
	$user_id = $user->register($data->name, $data->password, $photo);

	if ($user_id) {
		$token = JWT::encode(['user_id' => $user_id]);
		http_response_code(201);
		echo json_encode(['token' => $token, 'user_id' => $user_id]);
	} else {
		http_response_code(400);
		echo json_encode(['message' => 'Unable to register user']);
	}
} else {
	http_response_code(400);
	echo json_encode(['message' => 'Incomplete data']);
}
?>