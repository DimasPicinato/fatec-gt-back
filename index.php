<?php
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/StatusController.php';
require_once __DIR__ . '/controllers/TaskController.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$base = '/';
$uri = '/'.trim(substr($path, strlen($base)), '/');

$segments = array_values(array_filter(explode('/', $uri), fn($s)=>$s !== ''));

if ($segments === []) {
    json_response(['status'=>'ok','routes'=>[
        'POST /register','POST /login',
        'GET /statuses','POST /statuses','PUT /statuses/{id}','DELETE /statuses/{id}',
        'POST /tasks','GET /tasks','GET /tasks/{id}','PUT /tasks/{id}','DELETE /tasks/{id}',
        'PUT /user','DELETE /user (requires password)'
    ]]);
}

$first = $segments[0] ?? '';

if ($first === 'register' && $method === 'POST') {
    AuthController_register();
}
elseif ($first === 'login' && $method === 'POST') {
    AuthController_login();
}
elseif ($first === 'user') {
    if ($method === 'PUT') UserController_update();
    elseif ($method === 'DELETE') UserController_delete();
    else json_response(['error'=>'method not allowed'],405);
}
elseif ($first === 'statuses') {
    $id = $segments[1] ?? null;
    if ($method === 'GET' && !$id) StatusController_list();
    elseif ($method === 'POST' && !$id) StatusController_create();
    elseif ($method === 'PUT' && $id) StatusController_update($id);
    elseif ($method === 'DELETE' && $id) StatusController_delete($id);
    else json_response(['error'=>'not found'],404);
}
elseif ($first === 'tasks') {
    $id = $segments[1] ?? null;
    if ($method === 'POST' && !$id) TaskController_create();
    elseif ($method === 'GET' && $id) TaskController_get($id);
    elseif ($method === 'GET' && !$id) TaskController_list();
    elseif ($method === 'PUT' && $id) TaskController_update($id);
    elseif ($method === 'DELETE' && $id) TaskController_delete($id);
    else json_response(['error'=>'not found'],404);
}
else {
    json_response(['error'=>'not found'],404);
}
