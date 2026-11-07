<?php
header("Content-Type: application/json");
require_once '../../config/database.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

ini_set('display_errors', 0);



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

$user_id = api_user_id($_POST['user_id'] ?? ($input['user_id'] ?? 0));

if ($user_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid user ID"
    ]);
    exit;
}

try {

    // 🔥 CHECK IF USER EXISTS
    $check = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $check->execute([$user_id]);

    if ($check->rowCount() === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "User not found"
        ]);
        exit;
    }

    // 🔥 DISABLE FK CHECK TEMPORARILY (THIS IS IMPORTANT)
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    // 🔥 DELETE EVERYTHING
    $pdo->prepare("DELETE FROM orders WHERE user_id = ?")->execute([$user_id]);
    $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);
    $pdo->prepare("DELETE FROM user_addresses WHERE user_id = ?")->execute([$user_id]);
    
    

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    $deleted = $stmt->rowCount();

    // 🔥 RE-ENABLE FK CHECK
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    

    echo json_encode([
        "status" => $deleted > 0 ? "success" : "error",
        "message" => $deleted > 0 ? "Deleted successfully" : "No rows deleted",
        "deleted_rows" => $deleted,
        "user_id" => $user_id,
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => "Could not delete the account"
    ]);
}



?>