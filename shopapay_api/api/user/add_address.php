<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

// 🔥 Accept BOTH JSON and form-data
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

if (!$input) {
    $input = $_POST; // fallback
}

// Debug (optional - remove later)
// echo json_encode(["input" => $input]); exit;

// Assign values
$user_id = api_user_id($input['user_id'] ?? 0);
$label = trim($input['label'] ?? 'Home');
$full_address = trim($input['full_address'] ?? '');

$province_code = $input['province_code'] ?? '';
$province_name = $input['province_name'] ?? '';

$city_code = $input['city_code'] ?? '';
$city_name = $input['city_name'] ?? '';

$barangay_code = $input['barangay_code'] ?? '';
$barangay_name = $input['barangay_name'] ?? '';

$is_default = intval($input['is_default'] ?? 0);

// Validation
if ($user_id == 0 || empty($full_address)) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields"
    ]);
    exit;
}

try {

    // Reset default if needed
    if ($is_default == 1) {
        $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")
            ->execute([$user_id]);
    }

    // Insert
    $stmt = $pdo->prepare("
        INSERT INTO user_addresses 
        (user_id, label, full_address, province_code, province_name, city_code, city_name, barangay_code, barangay_name, is_default, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $stmt->execute([
        $user_id,
        $label,
        $full_address,
        $province_code,
        $province_name,
        $city_code,
        $city_name,
        $barangay_code,
        $barangay_name,
        $is_default
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Address saved successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Could not save the address"
    ]);
}
?>