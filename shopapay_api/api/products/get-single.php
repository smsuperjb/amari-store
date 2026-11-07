<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../config/database.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid product ID"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(["status" => "error", "message" => "Product not found"]);
        exit;
    }

    $product['is_variable'] = (bool)$product['is_variable'];

    // Variants
    if ($product['is_variable']) {
        $varStmt = $pdo->prepare("
            SELECT v.*, GROUP_CONCAT(CONCAT(pa.name, ': ', av.value) SEPARATOR ', ') as attributes
            FROM product_variants v
            LEFT JOIN variant_attribute_values vav ON v.id = vav.variant_id
            LEFT JOIN attribute_values av ON vav.attribute_value_id = av.id
            LEFT JOIN product_attributes pa ON av.attribute_id = pa.id
            WHERE v.product_id = ? AND v.is_active = 1
            GROUP BY v.id
        ");
        $varStmt->execute([$product_id]);
        $product['variants'] = $varStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $product['variants'] = [];
    }

    // Images
    $imgStmt = $pdo->prepare("SELECT image FROM product_images WHERE product_id = ? ORDER BY sort_order");
    $imgStmt->execute([$product_id]);
    $product['images'] = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        "status" => "success",
        "product" => $product
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}