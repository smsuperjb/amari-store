<?php
require_once '../../config/database.php';
include '../includes/header.php';

$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid Product ID</div>";
    include '../includes/footer.php';
    exit;
}

// Fetch product to confirm it exists and show name
$stmt = $pdo->prepare("SELECT name, image FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div class='alert alert-danger'>Product not found!</div>";
    include '../includes/footer.php';
    exit;
}

// ================= HANDLE DELETE CONFIRMATION =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {

    try {
        $pdo->beginTransaction();

        // Delete variants
        $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$product_id]);

        // Delete gallery images
        $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$product_id]);

        // Delete main product
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$product_id]);

        $pdo->commit();

        echo "<div class='alert alert-success'>Product <strong>" . htmlspecialchars($product['name']) . "</strong> has been deleted successfully!</div>";
        echo "<a href='https://amariaestheticsph.com/store/shopapay_api/admin/product-list.php' class='btn btn-primary mt-3'>Back to Products</a>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='alert alert-danger'>Error deleting product: " . htmlspecialchars($e->getMessage()) . "</div>";
    }

    include '../includes/footer.php';
    exit;
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">⚠️ Confirm Delete</h5>
                </div>
                <div class="card-body">
                    <p class="lead">Are you sure you want to delete this product?</p>
                    
                    <div class="alert alert-warning">
                        <strong>Product:</strong> <?= htmlspecialchars($product['name']) ?><br>
                        <strong>ID:</strong> <?= $product_id ?>
                    </div>

                    <p class="text-danger"><strong>This action cannot be undone!</strong></p>

                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="confirm_delete" value="1">
                        <button type="submit" class="btn btn-danger btn-lg px-5">Yes, Delete Product</button>
                    </form>

                    <a href="index.php" class="btn btn-secondary btn-lg px-5">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>