<?php
header('Content-Type: application/json');
try {
    require_once '../../config/database.php';

    // ✅ Optional category filter
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;

    $sql = "
        SELECT 
            p.*,
            c.name as category_name
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id
    ";

    // ✅ Filter by category if provided
    if ($categoryId) {
        $sql .= " WHERE p.category_id = :category_id";
    }

    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($sql);

    if ($categoryId) {
        $stmt->execute([':category_id' => $categoryId]);
    } else {
        $stmt->execute();
    }

    $products = [];
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $productId = $product['id'];

        $variants = [];
        if (isset($product['is_variable']) && $product['is_variable'] == 1) {
            $vStmt = $pdo->prepare("
                SELECT * FROM product_variants 
                WHERE product_id = ? AND is_active = 1
                ORDER BY price ASC
            ");
            $vStmt->execute([$productId]);
            $variants = $vStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $product['variants'] = $variants;
        $products[] = $product;
    }

    echo json_encode([
        "status"   => "success",
        "products" => $products,
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage(),
    ]);
}
?>