<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

$data    = json_decode(file_get_contents('php://input'), true);
$userId  = api_user_id($data['user_id'] ?? 0);
$items   = $data['items']   ?? [];

if (!$userId) {
    echo json_encode(["status" => "error", "message" => "user_id required"]);
    exit;
}

try {
    // ✅ Upsert — one row per user
    $stmt = $pdo->prepare("
        INSERT INTO user_carts (user_id, items, updated_at)
        VALUES (:uid, :items, NOW())
        ON DUPLICATE KEY UPDATE items = :items2, updated_at = NOW()
    ");
    $stmt->execute([
        ':uid'   => $userId,
        ':items' => json_encode($items),
        ':items2'=> json_encode($items),
    ]);

    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Could not save the cart"]);
}
?>