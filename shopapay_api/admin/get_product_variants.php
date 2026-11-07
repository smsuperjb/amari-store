<?php
// admin/get_product_variants.php
require_once '../config/database.php';

$product_id = (int)($_GET['product_id'] ?? 0);

if ($product_id) {
    $stmt = $pdo->prepare("SELECT id, sku, price FROM product_variants WHERE product_id = ? ORDER BY sku ASC");
    $stmt->execute([$product_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo json_encode([]);
}