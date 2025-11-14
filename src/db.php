<?php
$config = require __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    global $config;
    if ($pdo) return $pdo;
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config->db->host, $config->db->port, $config->db->name);
    $pdo = new PDO($dsn, $config->db->user, $config->db->pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    return $pdo;
}
