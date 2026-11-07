<?php
// api/auth/forgot_password.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../config/database.php';

// ============================================================
// 🔧 SMTP CONFIG - Hostinger
// ============================================================
define('SMTP_HOST',      'smtp.hostinger.com');
define('SMTP_PORT',      465);
define('SMTP_USER',      'admin@amariaestheticsph.com');
require_once __DIR__ . '/../../config/secrets.php'; // SMTP_PASS lives here
define('SMTP_FROM',      'admin@amariaestheticsph.com');
define('SMTP_FROM_NAME', 'Amari');
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data  = json_decode(file_get_contents("php://input"), true);
    $email = trim($data['email'] ?? '');

    if (empty($email)) {
        echo json_encode(["status" => "error", "message" => "Email is required"]);
        exit;
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(["status" => "error", "message" => "No account found with this email"]);
        exit;
    }

    // Generate 6-digit NUMERIC OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        otp VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Delete ALL previous records for this email (including used ones)
    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

    // ✅ Use MySQL NOW() for expiry to avoid PHP/MySQL timezone mismatch
    $stmt = $pdo->prepare("
        INSERT INTO password_resets (email, otp, expires_at)
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
    ");
    $stmt->execute([$email, $otp]);

    // 🔧 TEMPORARY DEBUG — remove after testing is confirmed working
    $db_now_stmt = $pdo->query("SELECT NOW() as db_now");
    $db_now = $db_now_stmt->fetch()['db_now'];

    echo json_encode([
        "status"    => "success",
        "message"   => "OTP sent to your email",
        "debug_otp" => $otp,
        "debug_now" => $db_now,
    ]);
    exit;

    // ⬇️ Uncomment this block and remove debug above once email is confirmed working
    /*
    $sent = sendSmtpMail($email, $otp);
    if ($sent === true) {
        echo json_encode(["status" => "success", "message" => "OTP sent to your email"]);
    } else {
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        echo json_encode(["status" => "error", "message" => "Failed to send email: " . $sent]);
    }
    */

} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}

// ============================================================
// SMTP mailer — SSL port 465 (Hostinger)
// ============================================================
function sendSmtpMail($to, $otp) {
    $subject = "Your Password Reset OTP - Amari";
    $body    = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Password Reset Request</h2>
        <p>Your OTP code is:</p>
        <h1 style='letter-spacing: 8px; color: #2563EB;'>$otp</h1>
        <p>This code expires in <strong>10 minutes</strong>.</p>
        <p>If you did not request this, please ignore this email.</p>
    </body>
    </html>";

    try {
        $socket = fsockopen('ssl://' . SMTP_HOST, SMTP_PORT, $errno, $errstr, 15);
        if (!$socket) {
            return "Cannot connect to SMTP: $errstr ($errno)";
        }

        smtp_read($socket);
        smtp_send($socket, "EHLO " . gethostname());
        smtp_read($socket);
        smtp_send($socket, "AUTH LOGIN");
        smtp_read($socket);
        smtp_send($socket, base64_encode(SMTP_USER));
        smtp_read($socket);
        smtp_send($socket, base64_encode(SMTP_PASS));
        $authResponse = smtp_read($socket);

        if (strpos($authResponse, '235') === false) {
            return "SMTP Auth failed: $authResponse";
        }

        smtp_send($socket, "MAIL FROM: <" . SMTP_FROM . ">");
        smtp_read($socket);
        smtp_send($socket, "RCPT TO: <$to>");
        smtp_read($socket);
        smtp_send($socket, "DATA");
        smtp_read($socket);

        $headers  = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        smtp_send($socket, $headers . "\r\n" . $body . "\r\n.");
        smtp_read($socket);
        smtp_send($socket, "QUIT");
        fclose($socket);

        return true;

    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function smtp_send($socket, $cmd) {
    fwrite($socket, $cmd . "\r\n");
}

function smtp_read($socket) {
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (substr($line, 3, 1) === ' ') break;
    }
    return $response;
}
?>