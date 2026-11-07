<?php
header("Content-Type: application/json");
error_reporting(0); // 🚨 prevent warnings breaking JSON

require_once '../../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    $data = $_POST;
}

$user_id = $data['user_id'] ?? null;
$address_id = $data['address_id'] ?? null;

if (!$user_id || !$address_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing parameters"
    ]);
    exit;
}

try {
    // reset all
    $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // set selected
    $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$address_id, $user_id]);

    echo json_encode([
        "status" => "success",
        "message" => "Default updated"
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
    exit;
}

?>