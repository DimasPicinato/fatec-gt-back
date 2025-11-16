<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

if (!empty($data->password)) {
    if ($user->delete($user_id, $data->password)) {
        http_response_code(200);
        echo json_encode(['message' => 'User deleted successfully']);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Unable to delete user or invalid password']);
    }
} else {
    http_response_code(400);
    echo json_encode(['message' => 'Password required']);
}
?>