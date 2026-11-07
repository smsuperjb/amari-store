<?php
// api/categories/get_all.php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT id, name, image FROM categories ORDER BY sort_order ASC, name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "categories" => $categories
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>