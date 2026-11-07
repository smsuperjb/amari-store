<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

$user_id = api_user_id($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid user_id"]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name, email, phone, profile_image FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            "status" => "success",
            "user" => $user
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error"]);
}
?>