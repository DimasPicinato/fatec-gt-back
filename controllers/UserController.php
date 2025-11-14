<?php
function UserController_update() {
    $user = auth_user_or_401();
    $pdo = db();

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $name = null; $password = null; $photoPath = null;

    if (stripos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = $input['name'] ?? null;
        $password = $input['password'] ?? null;
        if (!empty($input['photo_base64'])) {
            $data = $input['photo_base64'];
            if (preg_match('/^data:\w+\/\w+;base64,/', $data)) $data = preg_replace('/^data:\w+\/\w+;base64,/', '', $data);
            $bin = base64_decode($data);
            if ($bin !== false) {
                $file = uuid_v4() . '.jpg';
                global $config;
                if (!is_dir($config->upload_dir)) mkdir($config->upload_dir,0755,true);
                file_put_contents($config->upload_dir . '/' . $file, $bin);
                $photoPath = 'uploads/'.$file;
            }
        }
    } else {
        $name = $_POST['name'] ?? null;
        $password = $_POST['password'] ?? null;
        if (!empty($_FILES['photo']['tmp_name'])) {
            $tmp = $_FILES['photo']['tmp_name'];
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $file = uuid_v4() . '.' . $ext;
            global $config;
            if (!is_dir($config->upload_dir)) mkdir($config->upload_dir,0755,true);
            move_uploaded_file($tmp, $config->upload_dir . '/' . $file);
            $photoPath = 'uploads/'.$file;
        }
    }

    $fields = [];
    $params = [];
    if ($name) { $fields[] = 'name = ?'; $params[] = $name; }
    if ($password) { $fields[] = 'password_hash = ?'; $params[] = password_hash($password, PASSWORD_DEFAULT); }
    if ($photoPath) { $fields[] = 'photo = ?'; $params[] = $photoPath; }

    if (count($fields) === 0) json_response(['error'=>'nothing to update'],400);

    if ($name && $name !== $user['name']) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE name = ? AND id != ? LIMIT 1');
        $stmt->execute([$name, $user['id']]);
        if ($stmt->fetch()) json_response(['error'=>'name already taken'],409);
    }

    $params[] = $user['id'];
    $sql = 'UPDATE users SET ' . implode(',', $fields) . ' , updated_at = NOW() WHERE id = ? AND deleted_at IS NULL';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $stmt = $pdo->prepare('SELECT id,name,photo FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $u = $stmt->fetch();
    json_response(['user'=>$u]);
}

function UserController_delete() {
    $user = auth_user_or_401();
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $password = $input['password'] ?? null;
    if (!$password) json_response(['error'=>'password required'],400);

    $pdo = db();
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $u = $stmt->fetch();
    if (!$u || !password_verify($password, $u['password_hash'])) json_response(['error'=>'invalid password'],401);

    $stmt = $pdo->prepare('UPDATE users SET deleted_at = NOW() WHERE id = ?');
    $stmt->execute([$user['id']]);

    $stmt = $pdo->prepare('UPDATE tasks SET deleted_at = NOW() WHERE user_id = ? AND deleted_at IS NULL');
    $stmt->execute([$user['id']]);

    json_response(['ok'=>true]);
}
