<?php
// api/orders/apply_promo.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data          = json_decode(file_get_contents("php://input"), true);
    $code          = strtoupper(trim($data['code']          ?? ''));
    $subtotal      = (float)($data['subtotal']              ?? 0);
    $item_count    = (int)($data['item_count']              ?? 0);

    if (empty($code)) {
        echo json_encode(["status" => "error", "message" => "Promo code is required"]);
        exit;
    }

    // Find coupon
    $stmt = $pdo->prepare("
        SELECT * FROM coupons
        WHERE code = ?
          AND is_active = 1
          AND (expiry_date IS NULL OR expiry_date >= CURDATE())
          AND (usage_limit = 0 OR used_count < usage_limit)
        LIMIT 1
    ");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        echo json_encode(["status" => "error", "message" => "Invalid or expired promo code"]);
        exit;
    }

    // Check minimum order amount
    if ($subtotal < $coupon['min_order_amount']) {
        echo json_encode([
            "status"  => "error",
            "message" => "Minimum order of ₱" . number_format($coupon['min_order_amount'], 2) . " required"
        ]);
        exit;
    }

    // Check minimum quantity
    if ($coupon['min_quantity'] > 0 && $item_count < $coupon['min_quantity']) {
        echo json_encode([
            "status"  => "error",
            "message" => "Minimum of {$coupon['min_quantity']} items required"
        ]);
        exit;
    }

    // Calculate discount
    $discount = 0;
    $label    = "";

    switch ($coupon['type']) {
        case 'percentage':
            $discount = $subtotal * ($coupon['value'] / 100);
            $label    = $coupon['value'] . "% off";
            break;
        case 'fixed':
            $discount = min((float)$coupon['value'], $subtotal);
            $label    = "₱" . number_format($coupon['value'], 2) . " off";
            break;
        case 'free_shipping':
            $discount = 0; // handled separately in Flutter
            $label    = "Free Shipping";
            break;
        case 'bxgy':
            $discount = (float)$coupon['value'];
            $label    = "Buy X Get Y";
            break;
        default:
            $discount = 0;
    }

    echo json_encode([
        "status"        => "success",
        "message"       => "Promo applied! $label",
        "coupon_id"     => $coupon['id'],
        "type"          => $coupon['type'],
        "discount"      => round($discount, 2),
        "free_shipping" => $coupon['type'] === 'free_shipping',
        "label"         => $label,
    ]);

} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>