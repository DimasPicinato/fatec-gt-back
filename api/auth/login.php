<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include_once '../../config/database.php';
include_once '../../config/jwt.php';
include_once '../../models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name) && !empty($data->password)) {
    $user_id = $user->login($data->name, $data->password);

    if ($user_id) {
        $token = JWT::encode(['user_id' => $user_id]);
        http_response_code(200);
        echo json_encode(['token' => $token, 'user_id' => $user_id]);
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid credentials']);
    }
} else {
    http_response_code(400);
    echo json_encode(['message' => 'Incomplete data']);
}
?>