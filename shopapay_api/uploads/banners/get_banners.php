<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$stmt = $pdo->query("SELECT * FROM banners ORDER BY id DESC");
$banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => "success",
    "banners" => $banners
]);