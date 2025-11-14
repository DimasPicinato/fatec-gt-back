<?php
function StatusController_create() {
    $user = auth_user_or_401();
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = $input['name'] ?? null;
    $stage = $input['stage'] ?? null;
    if (!$name || !$stage) json_response(['error'=>'name and stage required'],400);
    if (!in_array($stage, ['TO DO','DOING','DONE'])) json_response(['error'=>'invalid stage'],400);
    $pdo = db();
    $id = uuid_v4();
    $stmt = $pdo->prepare('INSERT INTO statuses (id,name,stage) VALUES (?,?,?)');
    $stmt->execute([$id,$name,$stage]);
    json_response(['id'=>$id,'name'=>$name,'stage'=>$stage],201);
}

function StatusController_list() {
    $pdo = db();
    $stmt = $pdo->query('SELECT id,name,stage,created_at,updated_at FROM statuses');
    $rows = $stmt->fetchAll();
    json_response($rows);
}

function StatusController_update($id) {
    $user = auth_user_or_401();
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = $input['name'] ?? null;
    $stage = $input['stage'] ?? null;
    if (!$name && !$stage) json_response(['error'=>'nothing to update'],400);
    if ($stage && !in_array($stage, ['TO DO','DOING','DONE'])) json_response(['error'=>'invalid stage'],400);
    $pdo = db();
    $fields = []; $params = [];
    if ($name) { $fields[] = 'name = ?'; $params[] = $name; }
    if ($stage) { $fields[] = 'stage = ?'; $params[] = $stage; }
    $params[] = $id;
    $sql = 'UPDATE statuses SET ' . implode(',', $fields) . ', updated_at = NOW() WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stmt = $pdo->prepare('SELECT id,name,stage FROM statuses WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) json_response(['error'=>'not found'],404);
    json_response($row);
}

function StatusController_delete($id) {
    $user = auth_user_or_401();
    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM statuses WHERE id = ?');
    $stmt->execute([$id]);
    json_response(['ok'=>true]);
}
