<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

$userId = api_user_id($_GET['user_id'] ?? 0);
if (!$userId) {
    echo json_encode(["status" => "error", "message" => "user_id required"]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT items FROM user_carts WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(["status" => "success", "items" => []]);
        exit;
    }

    $items = json_decode($row['items'], true) ?? [];
    echo json_encode(["status" => "success", "items" => $items]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Could not load the cart"]);
}
?>