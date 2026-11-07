<?php
ob_start();
include('../config/database.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ ALL redirects FIRST
if (isset($_POST['upload'])) {
    $title = $_POST['title'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $category = $_POST['category'] ?? 'hero-image'; // Added Category
    $imageName = null;

    if (!empty($_FILES['image']['name'])) {
        $uploadDir = "../uploads/banners/";
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $imageName = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
    }

    // Updated query to include category
    $stmt = $pdo->prepare("INSERT INTO banners (title, image, category, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $imageName, $category, $status]);
    header("Location: banners.php?success=added");
    exit;
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $img = $pdo->prepare("SELECT image FROM banners WHERE id=?");
    $img->execute([$id]);
    $file = $img->fetchColumn();
    if ($file && file_exists("../uploads/banners/" . $file)) unlink("../uploads/banners/" . $file);
    $pdo->prepare("DELETE FROM banners WHERE id=?")->execute([$id]);
    header("Location: banners.php?success=deleted");
    exit;
}

if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $status = $_POST['status'];
    $category = $_POST['category']; // Added Category

    // Updated query to include category modification
    $pdo->prepare("UPDATE banners SET title=?, status=?, category=? WHERE id=?")->execute([$title, $status, $category, $id]);
    header("Location: banners.php?success=updated");
    exit;
}

// ✅ Fetch data before header outputs HTML
$banners = $pdo->query("SELECT * FROM banners ORDER BY id DESC")->fetchAll();
$total    = count($banners);
$active   = count(array_filter($banners, fn($b) => $b['status'] === 'active'));
$inactive = $total - $active;

// Helper array for pretty labels
$category_labels = [
    'hero-image'  => 'Hero Image',
    'brand-image' => 'Brand Image',
    'about-image' => 'About Image',
    'ceo-image'   => 'CEO Image'
];

// ✅ NOW include header — HTML output starts here, no more redirects needed
include('includes/header.php');
?>
<style>
.banner-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; }
.banner-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 6px rgba(0,0,0,.06); transition: box-shadow .2s; }
.banner-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.1); }
.banner-img-wrap { position: relative; height: 180px; background: #f3f4f6; overflow: hidden; }
.banner-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
.banner-status-badge {
    position: absolute; top: 10px; right: 10px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
}
.banner-category-badge {
    position: absolute; top: 10px; left: 10px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
    background: rgba(108, 63, 200, 0.1); color: #6C3FC8;
}
.badge-active { background: #D1FAE5; color: #065F46; }
.badge-inactive { background: #FEE2E2; color: #991B1B; }
.banner-body { padding: 16px; }
.banner-title { font-weight: 600; font-size: 14px; color: #111827; margin-bottom: 4px; }
.banner-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 12px; }
.banner-actions-buttons { display: flex; gap: 8px; grid-column: span 2; justify-content: flex-end; margin-top: 4px; }
.img-preview { width: 100%; height: 140px; object-fit: cover; border-radius: 10px; margin-top: 8px; display: none; }
.upload-zone { border: 2px dashed #E5E7EB; border-radius: 10px; padding: 24px; text-align: center; cursor: pointer; transition: border-color .2s; }
.upload-zone:hover { border-color: #6C3FC8; }
.upload-zone i { font-size: 28px; color: #9CA3AF; margin-bottom: 8px; }
.empty-state { text-align: center; padding: 60px 20px; color: #9CA3AF; }
.empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }
</style>

<div class="orders-wrap">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <h1 class="page-title">Banner Management</h1>
            <p class="page-sub">Manage promotional banners shown in the app</p>
        </div>
        <button class="btn-primary-c" data-bs-toggle="modal" data-bs-target="#addBannerModal">
            <i class="fas fa-plus"></i> Add Banner
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-s alert-success-c mb-4">
            <i class="fas fa-check-circle me-2"></i>
            <?= $_GET['success'] === 'added' ? 'Banner uploaded successfully!' :
                ($_GET['success'] === 'updated' ? 'Banner updated successfully!' : 'Banner deleted.') ?>
        </div>
    <?php endif; ?>

    <div class="stat-cards mb-4">
        <div class="stat-card">
            <div class="stat-num"><?= $total ?></div>
            <div class="stat-label">Total Banners</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#065F46"><?= $active ?></div>
            <div class="stat-label">Active</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#991B1B"><?= $inactive ?></div>
            <div class="stat-label">Inactive</div>
        </div>
    </div>

    <?php if (empty($banners)): ?>
        <div class="tbl-card">
            <div class="empty-state">
                <i class="fas fa-image"></i>
                <p style="font-size:15px; font-weight:600; color:#374151;">No banners yet</p>
                <p style="font-size:13px;">Click "Add Banner" to upload your first banner.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="banner-grid">
            <?php foreach ($banners as $b): ?>
                <div class="banner-card">
                    <div class="banner-img-wrap">
                        <?php if ($b['image']): ?>
                            <img src="../uploads/banners/<?= htmlspecialchars($b['image']) ?>" alt="banner">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <span class="banner-category-badge">
                            <?= $category_labels[$b['category']] ?? 'Unassigned' ?>
                        </span>
                        <span class="banner-status-badge <?= $b['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                            <?= ucfirst($b['status']) ?>
                        </span>
                    </div>
                    <div class="banner-body">
                        <div class="banner-title"><?= htmlspecialchars($b['title'] ?: 'Untitled Banner') ?></div>
                        
                        <form method="POST" class="banner-actions">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            
                            <input type="text" name="title" value="<?= htmlspecialchars($b['title']) ?>"
                                class="form-inp" placeholder="Banner title" style="grid-column: span 2">
                            
                            <select name="category" class="form-inp">
                                <option value="hero-image" <?= $b['category']==='hero-image'?'selected':'' ?>>Hero Image</option>
                                <option value="brand-image" <?= $b['category']==='brand-image'?'selected':'' ?>>Brand Image</option>
                                <option value="about-image" <?= $b['category']==='about-image'?'selected':'' ?>>About Image</option>
                                <option value="ceo-image" <?= $b['category']==='ceo-image'?'selected':'' ?>>CEO Image</option>
                            </select>

                            <select name="status" class="form-inp">
                                <option value="active" <?= $b['status']==='active'?'selected':'' ?>>Active</option>
                                <option value="inactive" <?= $b['status']==='inactive'?'selected':'' ?>>Inactive</option>
                            </select>

                            <div class="banner-actions-buttons">
                                <button name="update" class="icon-btn" title="Save" style="border-color:#6C3FC8;color:#6C3FC8">
                                    <i class="fas fa-save"></i>
                                </button>
                                <a href="banners.php?delete=<?= $b['id'] ?>"
                                   class="icon-btn" style="border-color:#EF4444;color:#EF4444; text-decoration:none; display:inline-flex; align-items:center; padding:0 10px;"
                                   title="Delete"
                                   onclick="return confirm('Delete this banner?')">
                                    <i class="fas fa-trash me-1"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="addBannerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content modal-card">
            <div class="modal-hdr">
                <h5>Upload New Banner</h5>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-bdy">

                    <div class="mb-3">
                        <label class="form-lbl">Banner Title</label>
                        <input type="text" name="title" class="form-inp" placeholder="e.g. Summer Sale">
                    </div>

                    <div class="mb-3">
                        <label class="form-lbl">Category <span style="color:#EF4444">*</span></label>
                        <select name="category" class="form-inp" required>
                            <option value="hero-image">Hero Image</option>
                            <option value="brand-image">Brand Image</option>
                            <option value="about-image">About Image</option>
                            <option value="ceo-image">CEO Image</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-lbl">Banner Image <span style="color:#EF4444">*</span></label>
                        <label class="upload-zone d-block" for="bannerFileInput">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p style="margin:0;font-size:13px;color:#6B7280">Click to upload or drag & drop</p>
                            <p style="margin:4px 0 0;font-size:11px;color:#9CA3AF">PNG, JPG, WEBP up to 5MB</p>
                        </label>
                        <input type="file" name="image" id="bannerFileInput" accept="image/*" required
                               style="display:none" onchange="previewBanner(this)">
                        <img id="bannerPreview" class="img-preview" alt="preview">
                    </div>

                    <div class="mb-3">
                        <label class="form-lbl">Status</label>
                        <select name="status" class="form-inp">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                </div>
                <div class="modal-ftr">
                    <button type="button" class="btn-outline-c" data-bs-dismiss="modal">Cancel</button>
                    <button name="upload" class="btn-primary-c">
                        <i class="fas fa-upload"></i> Upload Banner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewBanner(input) {
    const preview = document.getElementById('bannerPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
<?php ob_end_flush(); ?>
<?php include('includes/footer.php'); ?>