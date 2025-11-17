<?php
class Status
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

	public function create($name, $stage)
	{
		$query = "INSERT INTO statuses (id, name, stage) VALUES (:id, :name, :stage)";
		$stmt = $this->conn->prepare($query);

		$id = $this->generateUUID();

		$stmt->bindParam(':id', $id);
		$stmt->bindParam(':name', $name);
		$stmt->bindParam(':stage', $stage);

		if ($stmt->execute()) {
			return $id;
		}
		return false;
	}

	public function getAll()
	{
		$query = "SELECT * FROM statuses ORDER BY created_at ASC";
		$stmt = $this->conn->prepare($query);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function update($id, $name, $stage)
	{
		$query = "UPDATE statuses SET name = :name, stage = :stage WHERE id = :id";
		$stmt = $this->conn->prepare($query);

		$stmt->bindParam(':id', $id);
		$stmt->bindParam(':name', $name);
		$stmt->bindParam(':stage', $stage);

		return $stmt->execute();
	}

	public function delete($id)
	{
		$query = "DELETE FROM statuses WHERE id = :id";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(':id', $id);
		return $stmt->execute();
	}
}
?>