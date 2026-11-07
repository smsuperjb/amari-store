<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "user_id required"]);
    exit;
}

try {
    // Total orders
    $total = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $total->execute([$user_id]);
    $total_orders = $total->fetchColumn();

    // Ongoing (not delivered or cancelled)
    $ongoing = $pdo->prepare("
        SELECT COUNT(*) FROM orders 
        WHERE user_id = ? 
        AND status NOT IN ('delivered', 'cancelled')
    ");
    $ongoing->execute([$user_id]);
    $ongoing_orders = $ongoing->fetchColumn();

    // Completed
    $completed = $pdo->prepare("
        SELECT COUNT(*) FROM orders 
        WHERE user_id = ? 
        AND status = 'delivered'
    ");
    $completed->execute([$user_id]);
    $completed_orders = $completed->fetchColumn();

    echo json_encode([
        "status" => "success",
        "total_orders" => (int)$total_orders,
        "ongoing_orders" => (int)$ongoing_orders,
        "completed_orders" => (int)$completed_orders,
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>