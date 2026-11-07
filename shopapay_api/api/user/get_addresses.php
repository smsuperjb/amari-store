<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

$user_id = api_user_id($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

echo json_encode([
    "status" => "success",
    "addresses" => $addresses
]);
?>