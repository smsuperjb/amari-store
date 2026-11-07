<?php
// api/auth/verify_otp.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data  = json_decode(file_get_contents("php://input"), true);
    $email = trim($data['email'] ?? '');
    $otp   = trim($data['otp'] ?? '');

    if (empty($email) || empty($otp)) {
        echo json_encode(["status" => "error", "message" => "Email and OTP are required"]);
        exit;
    }

    // Find valid unused OTP
    $stmt = $pdo->prepare("
        SELECT * FROM password_resets
        WHERE email = ?
          AND otp = ?
          AND used = 0
          AND expires_at > NOW()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$email, $otp]);
    $record = $stmt->fetch();

    if (!$record) {
        echo json_encode([
            "status"  => "error",
            "message" => "Invalid or expired OTP",
        ]);
        exit;
    }

    // Mark OTP as used
    $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?")
        ->execute([$record['id']]);

    // Generate a secure reset token
    $reset_token = bin2hex(random_bytes(32));

    // ✅ Use MySQL NOW() for expiry to avoid PHP/MySQL timezone mismatch
    $pdo->prepare("
        UPDATE password_resets
        SET otp = ?, expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE)
        WHERE id = ?
    ")->execute([$reset_token, $record['id']]);

    echo json_encode([
        "status"      => "success",
        "message"     => "OTP verified successfully",
        "reset_token" => $reset_token,
    ]);

} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>