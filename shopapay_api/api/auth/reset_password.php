<?php
// api/auth/reset_password.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data         = json_decode(file_get_contents("php://input"), true);
    $reset_token  = trim($data['reset_token'] ?? '');
    $new_password = $data['new_password'] ?? '';

    if (empty($reset_token) || empty($new_password)) {
        echo json_encode(["status" => "error", "message" => "Reset token and new password are required"]);
        exit;
    }

    if (strlen($new_password) < 8) {
        echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters"]);
        exit;
    }

    // Find valid reset token
    $stmt = $pdo->prepare("
        SELECT * FROM password_resets
        WHERE otp = ?
          AND expires_at > NOW()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$reset_token]);
    $record = $stmt->fetch();

    if (!$record) {
        echo json_encode(["status" => "error", "message" => "Invalid or expired reset token"]);
        exit;
    }

    // Hash new password
    $hashed = password_hash($new_password, PASSWORD_BCRYPT);

    // Update user's password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$hashed, $record['email']]);

    // Delete all reset records for this email
    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")
        ->execute([$record['email']]);

    echo json_encode([
        "status"  => "success",
        "message" => "Password reset successfully"
    ]);

} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>