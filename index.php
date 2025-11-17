<?php
header("Content-Type: application/json");
echo json_encode([
	'message' => 'API is running',
	'endpoints' => [
		'auth' => [
			'POST /api/auth/register.php' => 'Register new user',
			'POST /api/auth/login.php' => 'Login user'
		],
		'user' => [
			'PUT /api/user/update.php' => 'Update user (requires auth)',
			'DELETE /api/user/delete.php' => 'Delete user (requires auth + password)'
		],
		'status' => [
			'POST /api/status/create.php' => 'Create status (requires auth)',
			'GET /api/status/read.php' => 'List all statuses (requires auth)',
			'PUT /api/status/update.php' => 'Update status (requires auth)',
			'DELETE /api/status/delete.php' => 'Delete status (requires auth)'
		],
		'task' => [
			'POST /api/task/create.php' => 'Create task (requires auth)',
			'GET /api/task/read_one.php?id={id}' => 'Get task by ID (requires auth)',
			'GET /api/task/read.php?search={term}&order_by={field}&order_dir={ASC|DESC}' => 'List tasks (requires auth)',
			'PUT /api/task/update.php' => 'Update task (requires auth)',
			'DELETE /api/task/delete.php' => 'Delete task (requires auth)'
		]
	]
]);
?>