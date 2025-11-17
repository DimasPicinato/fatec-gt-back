<?php
class Task
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

	public function create($user_id, $status_id, $title, $description = null, $due_date = null)
	{
		$query = "INSERT INTO tasks (id, user_id, status_id, title, description, due_date) VALUES (:id, :user_id, :status_id, :title, :description, :due_date)";
		$stmt = $this->conn->prepare($query);

		$id = $this->generateUUID();

		$stmt->bindParam(':id', $id);
		$stmt->bindParam(':user_id', $user_id);
		$stmt->bindParam(':status_id', $status_id);
		$stmt->bindParam(':title', $title);
		$stmt->bindParam(':description', $description);
		$stmt->bindParam(':due_date', $due_date);

		if ($stmt->execute()) {
			return $id;
		}
		return false;
	}

	public function getById($id, $user_id)
	{
		$query = "SELECT t.*, s.id as status_id, s.name as status_name 
                  FROM tasks t 
                  JOIN statuses s ON t.status_id = s.id 
                  WHERE t.id = :id AND t.user_id = :user_id AND t.deleted_at IS NULL";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(':id', $id);
		$stmt->bindParam(':user_id', $user_id);
		$stmt->execute();

		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function getAll($user_id, $search = null, $orderBy = 'created_at', $orderDir = 'DESC')
	{
		$allowedOrderBy = ['id', 'title', 'description', 'due_date', 'created_at', 'updated_at', 'status_id', 'status_name'];
		$allowedOrderDir = ['ASC', 'DESC'];

		if (!in_array($orderBy, $allowedOrderBy)) {
			$orderBy = 'created_at';
		}
		if (!in_array(strtoupper($orderDir), $allowedOrderDir)) {
			$orderDir = 'DESC';
		}

		$query = "SELECT t.*, s.id as status_id, s.name as status_name 
                  FROM tasks t 
                  JOIN statuses s ON t.status_id = s.id 
                  WHERE t.user_id = :user_id AND t.deleted_at IS NULL";

		if ($search) {
			$query .= " AND (t.title LIKE :search OR t.description LIKE :search OR s.name LIKE :search)";
		}

		$orderByField = $orderBy === 'status_name' ? 's.name' : 't.' . $orderBy;
		$query .= " ORDER BY " . $orderByField . " " . strtoupper($orderDir);

		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(':user_id', $user_id);

		if ($search) {
			$searchParam = '%' . $search . '%';
			$stmt->bindParam(':search', $searchParam);
		}

		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function update($id, $user_id, $status_id = null, $title = null, $description = null, $due_date = null)
	{
		$fields = [];
		$params = ['id' => $id, 'user_id' => $user_id];

		if ($status_id !== null) {
			$fields[] = "status_id = :status_id";
			$params['status_id'] = $status_id;
		}
		if ($title !== null) {
			$fields[] = "title = :title";
			$params['title'] = $title;
		}
		if ($description !== null) {
			$fields[] = "description = :description";
			$params['description'] = $description;
		}
		if ($due_date !== null) {
			$fields[] = "due_date = :due_date";
			$params['due_date'] = $due_date;
		}

		if (empty($fields)) {
			return false;
		}

		$query = "UPDATE tasks SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL";
		$stmt = $this->conn->prepare($query);

		foreach ($params as $key => $value) {
			$stmt->bindValue(':' . $key, $value);
		}

		return $stmt->execute();
	}

	public function delete($id, $user_id)
	{
		$query = "UPDATE tasks SET deleted_at = NOW() WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(':id', $id);
		$stmt->bindParam(':user_id', $user_id);
		return $stmt->execute();
	}
}
?>