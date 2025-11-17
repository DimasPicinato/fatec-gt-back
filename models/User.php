<?php
class User
{
	private $conn;

	public function __construct($db)
	{
		$this->conn = $db;
	}

	private function generateUUID()
	{
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff)
		);
	}

	public function register($name, $password, $photo = null)
	{
		$query = "INSERT INTO users (id, name, password_hash, photo) VALUES (:id, :name, :password_hash, :photo)";
		$stmt = $this->conn->prepare($query);

		$id = $this->generateUUID();
		$password_hash = password_hash($password, PASSWORD_BCRYPT);

		$stmt->bindParam(':id', $id);
		$stmt->bindParam(':name', $name);
		$stmt->bindParam(':password_hash', $password_hash);
		$stmt->bindParam(':photo', $photo);

		if ($stmt->execute()) {
			return $id;
		}
		return false;
	}

	public function login($name, $password)
	{
		$query = "SELECT id, password_hash FROM users WHERE name = :name AND deleted_at IS NULL";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(':name', $name);
		$stmt->execute();

		if ($stmt->rowCount() > 0) {
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if (password_verify($password, $row['password_hash'])) {
				return $row['id'];
			}
		}
		return false;
	}

	public function update($user_id, $name = null, $password = null, $photo = null)
	{
		$fields = [];
		$params = ['user_id' => $user_id];

		if ($name !== null) {
			$fields[] = "name = :name";
			$params['name'] = $name;
		}
		if ($password !== null) {
			$fields[] = "password_hash = :password_hash";
			$params['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
		}
		if ($photo !== null) {
			$fields[] = "photo = :photo";
			$params['photo'] = $photo;
		}

		if (empty($fields)) {
			return false;
		}

		$query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :user_id AND deleted_at IS NULL";
		$stmt = $this->conn->prepare($query);

		foreach ($params as $key => $value) {
			$stmt->bindValue(':' . $key, $value);
		}

		return $stmt->execute();
	}

	public function delete($user_id, $password)
	{
		$query = "SELECT password_hash FROM users WHERE id = :user_id AND deleted_at IS NULL";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(':user_id', $user_id);
		$stmt->execute();

		if ($stmt->rowCount() === 0) {
			return false;
		}

		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!password_verify($password, $row['password_hash'])) {
			return false;
		}

		$this->conn->beginTransaction();
		try {
			$queryTasks = "UPDATE tasks SET deleted_at = NOW() WHERE user_id = :user_id AND deleted_at IS NULL";
			$stmtTasks = $this->conn->prepare($queryTasks);
			$stmtTasks->bindParam(':user_id', $user_id);
			$stmtTasks->execute();

			$queryUser = "UPDATE users SET deleted_at = NOW() WHERE id = :user_id";
			$stmtUser = $this->conn->prepare($queryUser);
			$stmtUser->bindParam(':user_id', $user_id);
			$stmtUser->execute();

			$this->conn->commit();
			return true;
		} catch (Exception $e) {
			$this->conn->rollBack();
			return false;
		}
	}
}
?>