<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ================= GET INPUT =================
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid payload"]);
    exit;
}

// ================= DEBUG =================
file_put_contents("debug_order.txt", json_encode($data, JSON_PRETTY_PRINT));

// ================= EXTRACT VALUES =================
$order_id = "ORD-" . str_pad(
    $pdo->query("SELECT id FROM orders ORDER BY id DESC LIMIT 1")->fetchColumn() + 1,
    5,
    "0",
    STR_PAD_LEFT
);

$user_id        = $data["user_id"]        ?? 0;
$total_amount   = $data["total_amount"]   ?? 0;
$items_array    = $data["items_summary"]  ?? [];
$address        = $data["address"]        ?? "";
$payment_method = $data["payment_method"] ?? "";
$customer_name  = $data["customer_name"]  ?? "";
$phone          = $data["phone"]          ?? "";
$email          = $data["email"]          ?? "";
$province       = $data["province"]       ?? "";
$city           = $data["city"]           ?? "";
$barangay       = $data["barangay"]       ?? "";

// ================= ENRICH ITEMS WITH IMAGE =================
foreach ($items_array as &$item) {
    if (!empty($item['image'])) continue; // already has image, skip

    $imgStmt = $pdo->prepare("SELECT image FROM products WHERE name = ? LIMIT 1");
    $imgStmt->execute([$item['name']]);
    $product = $imgStmt->fetch(PDO::FETCH_ASSOC);

    $item['image'] = $product['image'] ?? '';
}
unset($item); // ✅ break reference

$items_summary = json_encode($items_array, JSON_UNESCAPED_UNICODE);

// ================= INSERT =================
$sql = "INSERT INTO orders (
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
    province,
    city,
    barangay
) VALUES (
    :order_id,
    :user_id,
    :total_amount,
    :items_summary,
    'Processing',
    :address,
    :payment_method,
    'Pending',
    NOW(),
    :customer_name,
    :phone,
    :email,
    :province,
    :city,
    :barangay
)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":order_id"      => $order_id,
    ":user_id"       => $user_id,
    ":total_amount"  => $total_amount,
    ":items_summary" => $items_summary,
    ":address"       => $address,
    ":payment_method"=> $payment_method,
    ":customer_name" => $customer_name,
    ":phone"         => $phone,
    ":email"         => $email,
    ":province"      => $province,
    ":city"          => $city,
    ":barangay"      => $barangay,
]);

// ================= RESPONSE =================
echo json_encode([
    "status"         => "success",
    "order_id"       => $order_id,
    "total_amount"   => $total_amount,
    "payment_method" => $payment_method,
    "customer_name"  => $customer_name,
    "phone"          => $phone,
    "email"          => $email,
    "address"        => $address,
    "province"       => $province,
    "city"           => $city,
    "barangay"       => $barangay,
    "items_summary"  => $items_array, // ✅ returned with image included
]);
exit;
?>