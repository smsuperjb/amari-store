<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$user_id = api_user_id($_GET['user_id'] ?? 0);
if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "user_id required"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            order_id,
            user_id,
            total_amount,
            items_summary,
            status,
            address,
            payment_method,
            payment_status,
            created_at,
            customer_name,
            phone,
            email,
            full_address,
            province,
            city,
            barangay
        FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as &$order) {
        // Decode items_summary if it's a JSON string
        if (isset($order['items_summary']) && is_string($order['items_summary'])) {
            $order['items_summary'] = json_decode($order['items_summary'], true) ?? [];
        }

        // ✅ Enrich each item with image from products table
        foreach ($order['items_summary'] as &$item) {
            if (!empty($item['image'])) continue; // already has image, skip

            $imgStmt = $pdo->prepare("
                SELECT image FROM products 
                WHERE name = ? 
                LIMIT 1
            ");
            $imgStmt->execute([$item['name']]);
            $product = $imgStmt->fetch(PDO::FETCH_ASSOC);

            $item['image'] = $product['image'] ?? '';
        }
        unset($item); // ✅ break reference
    }
    unset($order); // ✅ break reference

    echo json_encode($orders);

} catch (Exception $e) {
    echo json_encode([
        "status"  => "error",
        "message" => "Could not load orders"
    ]);
}
?>