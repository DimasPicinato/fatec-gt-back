<?php

function AuthController_register() {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = $input['name'] ?? null;
    $password = $input['password'] ?? null;
    if (!$name || !$password) json_response(['error'=>'name and password required'],400);
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE name = ? LIMIT 1');
    $stmt->execute([$name]);
    if ($stmt->fetch()) json_response(['error'=>'name already taken'],409);

    $id = uuid_v4();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (id,name,password_hash) VALUES (?,?,?)');
    $stmt->execute([$id,$name,$hash]);

    $token = jwt_encode(['uuid'=>$id]);

    $user = ['id'=>$id,'name'=>$name,'photo'=>null];
    json_response(['token'=>$token,'user'=>$user],201);
}

function AuthController_login() {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = $input['name'] ?? null;
    $password = $input['password'] ?? null;
    if (!$name || !$password) json_response(['error'=>'name and password required'],400);
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id,password_hash,name,photo,deleted_at FROM users WHERE name = ? LIMIT 1');
    $stmt->execute([$name]);
    $u = $stmt->fetch();
    if (!$u || $u['deleted_at'] !== null) json_response(['error'=>'invalid credentials'],401);
    if (!password_verify($password, $u['password_hash'])) json_response(['error'=>'invalid credentials'],401);
    $token = jwt_encode(['uuid'=>$u['id']]);
    json_response(['token'=>$token,'user'=>['id'=>$u['id'],'name'=>$u['name'],'photo'=>$u['photo']]]);
}
