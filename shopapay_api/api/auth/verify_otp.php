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

    // 🔧 DEBUG — log what we're comparing
    $debug_stmt = $pdo->prepare("SELECT otp, used, expires_at FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
    $debug_stmt->execute([$email]);
    $debug = $debug_stmt->fetch();

    $db_now_stmt = $pdo->query("SELECT NOW() as db_now");
    $db_now = $db_now_stmt->fetch()['db_now'];

    $debug_info = [
        "db_otp"      => $debug['otp']       ?? 'NOT FOUND',
        "db_used"     => $debug['used']       ?? 'NOT FOUND',
        "db_expires"  => $debug['expires_at'] ?? 'NOT FOUND',
        "db_now"      => $db_now,
        "php_now"     => date('Y-m-d H:i:s'),
        "input_otp"   => $otp,
        "input_email" => $email,
        "otp_match"   => isset($debug['otp']) ? ($debug['otp'] === $otp ? 'YES' : 'NO') : 'NO RECORD',
    ];

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
            "debug"   => $debug_info,   // 🔧 TEMPORARY — remove after testing
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
        "debug"       => $debug_info,   // 🔧 TEMPORARY — remove after testing
    ]);

} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>