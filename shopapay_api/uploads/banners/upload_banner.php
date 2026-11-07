<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_FILES['image'])) {
    echo json_encode(["status" => "error", "message" => "No image"]);
    exit;
}

$title = $_POST['title'] ?? '';

// ✅ Use absolute path instead of relative
$targetDir = __DIR__ . "/../../uploads/banners/";

if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$fileName = time() . "_" . basename($_FILES["image"]["name"]);
$targetFile = $targetDir . $fileName;

// ✅ Add debug to verify path
error_log("Target directory: " . $targetDir);
error_log("Target file: " . $targetFile);

if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
    $imageUrl = "https://amariaestheticsph.com/store/shopapay_api/uploads/banners/" . $fileName;
    
    $stmt = $pdo->prepare("INSERT INTO banners (image_url, title) VALUES (:image_url, :title)");
    $stmt->execute([
        ":image_url" => $imageUrl,
        ":title" => $title
    ]);
    
    echo json_encode([
        "status" => "success",
        "image_url" => $imageUrl
    ]);
} else {
    // ✅ Show exact error why upload failed
    $error = error_get_last();
    echo json_encode([
        "status" => "error", 
        "message" => "Upload failed",
        "detail" => $error['message'] ?? 'Unknown error',
        "tmp_name" => $_FILES["image"]["tmp_name"],
        "target" => $targetFile
    ]);
}
?>