<?php
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

try {
    $user_id = api_user_id($_POST['user_id'] ?? 0);
    if ($user_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid user_id"]);
        exit;
    }

    // Get current user data
    $stmt = $pdo->prepare("SELECT email, phone, profile_image FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current) {
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }

    $new_name  = trim($_POST['name'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_phone = trim($_POST['phone'] ?? '');

    $updates = [];
    $params = [];

    if ($new_name !== '') {
        $updates[] = "name = ?";
        $params[] = $new_name;
    }

    if ($new_email !== '' && strtolower($new_email) !== strtolower($current['email'] ?? '')) {
        $updates[] = "email = ?";
        $params[] = $new_email;
    }

    if ($new_phone !== '') {
        $updates[] = "phone = ?";
        $params[] = $new_phone;
    }

    // ================= IMAGE UPLOAD =================
    $image_path = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        
        $upload_dir = "../../uploads/profile/";   // ← Your desired folder

        // Create folder if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid('profile_') . '.' . $file_ext;
        $target_file = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            $image_path = $new_filename;
            $updates[] = "profile_image = ?";
            $params[] = $image_path;
        }
    }

    if (empty($updates)) {
        echo json_encode(["status" => "success", "message" => "No changes detected"]);
        exit;
    }

    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    $params[] = $user_id;

    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute($params);

    if ($success) {
        // Return updated user
        $stmt = $pdo->prepare("SELECT id, name, email, phone, profile_image FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => "success",
            "message" => "Profile updated successfully",
            "user" => $user
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update profile"]);
    }

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Server error"
    ]);
}
?>