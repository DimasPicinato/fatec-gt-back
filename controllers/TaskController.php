<?php
function TaskController_create() {
    $user = auth_user_or_401();
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $title = $input['title'] ?? null;
    $description = $input['description'] ?? null;
    $status_id = $input['status_id'] ?? null;
    $due_date = $input['due_date'] ?? null;
    if (!$title || !$status_id) json_response(['error'=>'title and status_id required'],400);

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM statuses WHERE id = ? LIMIT 1');
    $stmt->execute([$status_id]);
    if (!$stmt->fetch()) json_response(['error'=>'status not found'],400);

    $id = uuid_v4();
    $stmt = $pdo->prepare('INSERT INTO tasks (id,user_id,status_id,title,description,due_date) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$id, $user['id'], $status_id, $title, $description, $due_date]);
    $task = fetch_task_by_id($id, $user['id']);
    json_response($task,201);
}

function TaskController_get($id) {
    $user = auth_user_or_401();
    $task = fetch_task_by_id($id, $user['id']);
    if (!$task) json_response(['error'=>'not found'],404);
    json_response($task);
}

function TaskController_list() {
    $user = auth_user_or_401();
    $pdo = db();

    $queryParams = $_GET;
    $where = ['t.user_id = ?','t.deleted_at IS NULL'];
    $params = [$user['id']];

    foreach ($queryParams as $k => $v) {
        if (strpos($k,'filter[') === 0) {
            $field = substr($k,7,-1);
            $allowed = ['id','status_id','title','description','due_date','created_at','updated_at'];
            if (in_array($field, $allowed)) {
                $where[] = "t.`$field` = ?";
                $params[] = $v;
            }
        }
    }

    if (!empty($queryParams['search'])) {
        $s = '%'.$queryParams['search'].'%';
        $where[] = '(t.title LIKE ? OR t.description LIKE ?)';
        $params[] = $s; $params[] = $s;
    }

    $order_by = 't.created_at';
    $order_dir = 'DESC';
    if (!empty($queryParams['order_by'])) {
        $candidate = $queryParams['order_by'];
        $allowedOrder = ['id','title','description','due_date','created_at','updated_at','status_name'];
        if (in_array($candidate, $allowedOrder)) {
            if ($candidate === 'status_name') $order_by = 's.name';
            else $order_by = 't.`'.$candidate.'`';
        }
    }
    if (!empty($queryParams['order_dir']) && in_array(strtoupper($queryParams['order_dir']), ['ASC','DESC'])) {
        $order_dir = strtoupper($queryParams['order_dir']);
    }

    $sql = 'SELECT t.id,t.user_id,t.status_id,t.title,t.description,t.due_date,t.created_at,t.updated_at,
                   s.id AS status_id_return, s.name AS status_name
            FROM tasks t
            JOIN statuses s ON s.id = t.status_id
            WHERE ' . implode(' AND ', $where) .
            " ORDER BY $order_by $order_dir";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $res = array_map(function($r){
        return [
            'id'=>$r['id'],
            'user_id'=>$r['user_id'],
            'status_id'=>$r['status_id'],
            'title'=>$r['title'],
            'description'=>$r['description'],
            'due_date'=>$r['due_date'],
            'created_at'=>$r['created_at'],
            'updated_at'=>$r['updated_at'],
            'status'=>['id'=>$r['status_id_return'],'name'=>$r['status_name']]
        ];
    }, $rows);
    json_response($res);
}

function TaskController_update($id) {
    $user = auth_user_or_401();
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
    $stmt->execute([$id, $user['id']]);
    if (!$stmt->fetch()) json_response(['error'=>'not found'],404);

    $fields = []; $params = [];
    $allowed = ['status_id','title','description','due_date'];
    foreach ($allowed as $f) {
        if (array_key_exists($f, $input)) {
            $fields[] = "$f = ?";
            $params[] = $input[$f];
        }
    }
    if (count($fields) === 0) json_response(['error'=>'nothing to update'],400);

    if (isset($input['status_id'])) {
        $s = $pdo->prepare('SELECT id FROM statuses WHERE id = ? LIMIT 1');
        $s->execute([$input['status_id']]);
        if (!$s->fetch()) json_response(['error'=>'status not found'],400);
    }

    $params[] = $id; $params[] = $user['id'];
    $sql = 'UPDATE tasks SET ' . implode(',', $fields) . ', updated_at = NOW() WHERE id = ? AND user_id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $task = fetch_task_by_id($id, $user['id']);
    json_response($task);
}

function TaskController_delete($id) {
    $user = auth_user_or_401();
    $pdo = db();
    $stmt = $pdo->prepare('UPDATE tasks SET deleted_at = NOW() WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
    $stmt->execute([$id,$user['id']]);
    if ($stmt->rowCount() === 0) json_response(['error'=>'not found'],404);
    json_response(['ok'=>true]);
}

function fetch_task_by_id($id, $user_id) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT t.id,t.user_id,t.status_id,t.title,t.description,t.due_date,t.created_at,t.updated_at,
                                  s.id AS status_id_return,s.name AS status_name
                           FROM tasks t
                           JOIN statuses s ON s.id = t.status_id
                           WHERE t.id = ? AND t.user_id = ? AND t.deleted_at IS NULL LIMIT 1');
    $stmt->execute([$id,$user_id]);
    $r = $stmt->fetch();
    if (!$r) return null;
    return [
        'id'=>$r['id'],
        'user_id'=>$r['user_id'],
        'status_id'=>$r['status_id'],
        'title'=>$r['title'],
        'description'=>$r['description'],
        'due_date'=>$r['due_date'],
        'created_at'=>$r['created_at'],
        'updated_at'=>$r['updated_at'],
        'status'=>['id'=>$r['status_id_return'],'name'=>$r['status_name']]
    ];
}
