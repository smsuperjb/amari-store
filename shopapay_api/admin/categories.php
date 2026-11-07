<?php
require_once 'includes/header.php';
require_once '../config/database.php';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    header("Location: categories.php?success=deleted");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    try {
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../uploads/";
            $image = "cat_" . time() . "." . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image);
        }

        if ($id > 0) {
            $sql    = "UPDATE categories SET name = ?";
            $params = [$name];
            if ($image !== null) { $sql .= ", image = ?"; $params[] = $image; }
            $sql .= " WHERE id = ?";
            $params[] = $id;
            $pdo->prepare($sql)->execute($params);
            $msg = "updated";
        } else {
            $pdo->prepare("INSERT INTO categories (name, image) VALUES (?, ?)")->execute([$name, $image]);
            $msg = "added";
        }
        header("Location: categories.php?success=" . $msg);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$total = count($categories);
?>

<style>
.cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
.cat-card {
    background: #fff; border-radius: 16px; overflow: hidden;
    box-shadow: 0 1px 6px rgba(0,0,0,.06); transition: box-shadow .2s;
    display: flex; flex-direction: column;
}
.cat-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.1); }
.cat-img-wrap {
    height: 140px; background: #F3F4F6;
    display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.cat-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
.cat-initials {
    width: 60px; height: 60px; border-radius: 14px;
    background: linear-gradient(135deg, #6C3FC8, #9B72EF);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 22px; font-weight: 700;
}
.cat-body { padding: 14px; flex: 1; display: flex; flex-direction: column; gap: 10px; }
.cat-name { font-weight: 600; font-size: 14px; color: #111827; }
.cat-meta { font-size: 11px; color: #9CA3AF; }
.cat-actions { display: flex; gap: 8px; margin-top: auto; }
.empty-state { text-align: center; padding: 60px 20px; color: #9CA3AF; }
.empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }
</style>

<div class="orders-wrap">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <h1 class="page-title">Categories</h1>
            <p class="page-sub">Organize your products into categories</p>
        </div>
        <button class="btn-primary-c" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </div>

    <!-- ALERTS -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert-s alert-success-c mb-4">
            <i class="fas fa-check-circle me-2"></i>
            Category <?= $_GET['success'] === 'deleted' ? 'deleted' :
                ($_GET['success'] === 'updated' ? 'updated' : 'added') ?> successfully!
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert-s alert-danger-c mb-4">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- STAT -->
    <div class="stat-cards mb-4">
        <div class="stat-card">
            <div class="stat-num"><?= $total ?></div>
            <div class="stat-label">Total Categories</div>
        </div>
    </div>

    <!-- GRID -->
    <?php if (empty($categories)): ?>
        <div class="tbl-card">
            <div class="empty-state">
                <i class="fas fa-tags"></i>
                <p style="font-size:15px; font-weight:600; color:#374151;">No categories yet</p>
                <p style="font-size:13px;">Click "Add Category" to create your first one.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="cat-grid">
            <?php foreach ($categories as $cat): ?>
                <div class="cat-card">
                    <div class="cat-img-wrap">
                        <?php if (!empty($cat['image'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
                        <?php else: ?>
                            <div class="cat-initials"><?= strtoupper(substr($cat['name'], 0, 1)) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="cat-body">
                        <div>
                            <div class="cat-name"><?= htmlspecialchars($cat['name']) ?></div>
                            <div class="cat-meta">
                                Added <?= date('M d, Y', strtotime($cat['created_at'])) ?>
                            </div>
                        </div>
                        <div class="cat-actions">
                            <button onclick="editCategory(<?= $cat['id'] ?>, '<?= addslashes(htmlspecialchars($cat['name'])) ?>')"
                                class="btn-outline-c" style="flex:1;justify-content:center">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="?delete=<?= $cat['id'] ?>"
                               class="icon-btn" style="border-color:#EF4444;color:#EF4444"
                               onclick="return confirm('Delete this category?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content modal-card">
            <div class="modal-hdr">
                <h5 id="modalTitle">Add New Category</h5>
            </div>
            <form method="POST" enctype="multipart/form-data" id="categoryForm">
                <input type="hidden" name="id" id="categoryId" value="">
                <div class="modal-bdy">

                    <div class="mb-3">
                        <label class="form-lbl">Category Name <span style="color:#EF4444">*</span></label>
                        <input type="text" name="name" id="categoryName" class="form-inp" required placeholder="e.g. Skincare">
                    </div>

                    <div class="mb-3">
                        <label class="form-lbl">Category Image <span style="color:#9CA3AF">(optional)</span></label>
                        <input type="file" name="image" id="categoryImage" class="form-inp" accept="image/*"
                               style="height:auto;padding:8px" onchange="previewCatImg(this)">
                        <img id="catPreview" style="width:100%;height:100px;object-fit:cover;border-radius:10px;margin-top:8px;display:none" alt="preview">
                    </div>

                </div>
                <div class="modal-ftr">
                    <button type="button" class="btn-outline-c" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-c" id="saveButton">
                        <i class="fas fa-save"></i> Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('modalTitle').textContent = 'Add New Category';
    document.getElementById('saveButton').innerHTML = '<i class="fas fa-save"></i> Save Category';
    document.getElementById('catPreview').style.display = 'none';
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}

function editCategory(id, name) {
    document.getElementById('categoryId').value = id;
    document.getElementById('categoryName').value = name;
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('saveButton').innerHTML = '<i class="fas fa-save"></i> Update Category';
    document.getElementById('catPreview').style.display = 'none';
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}

function previewCatImg(input) {
    const preview = document.getElementById('catPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>