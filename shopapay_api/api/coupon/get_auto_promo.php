<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$subtotal   = (float)($_GET['subtotal']   ?? 0);
$item_count = (int)($_GET['item_count']   ?? 0);

try {
    $stmt = $pdo->prepare("
        SELECT * FROM coupons
        WHERE auto_apply    = 1
          AND is_active     = 1
          AND (expiry_date IS NULL OR expiry_date >= CURDATE())
          AND (usage_limit  = 0 OR used_count < usage_limit)
          AND min_order_amount <= :subtotal
          AND (min_quantity = 0 OR min_quantity <= :item_count)
        ORDER BY value DESC
        LIMIT 1
    ");
    $stmt->execute([':subtotal' => $subtotal, ':item_count' => $item_count]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        echo json_encode(["status" => "none"]);
        exit;
    }

    // Calculate discount
    $discount     = 0;
    $freeShipping = false;
    $label        = "";

    switch ($coupon['type']) {
        case 'percentage':
            $discount = round($subtotal * ($coupon['value'] / 100), 2);
            $label    = $coupon['value'] . "% off applied automatically!";
            break;
        case 'fixed':
            $discount = min((float)$coupon['value'], $subtotal);
            $label    = "₱" . number_format($discount, 0) . " off applied automatically!";
            break;
        case 'free_shipping':
            $freeShipping = true;
            $label        = "Free shipping applied automatically!";
            break;
        case 'bxgy':
            $discount = (float)$coupon['value'];
            $label    = "Buy X Get Y discount applied!";
            break;
    }

    echo json_encode([
        "status"       => "success",
        "coupon_id"    => $coupon['id'],
        "code"         => $coupon['code'] ?? "AUTO",
        "discount"     => $discount,
        "free_shipping"=> $freeShipping,
        "label"        => $label,
        "min_order"    => $coupon['min_order_amount'],
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>