<?php
// config/database.php

$host = 'CHANGE_ME';           // Usually 'localhost' on Hostinger
$dbname = 'CHANGE_ME';   // ← CHANGE THIS
$username = 'CHANGE_ME';   // ← CHANGE THIS
$password = 'CHANGE_ME';   // ← CHANGE THIS

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit;
}
?>