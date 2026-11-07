<?php
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');
ini_set('memory_limit', '128M');

require_once '../../config/database.php';
include '../includes/header.php';

$product_id = (int)($_GET['id'] ?? 0);
if ($product_id <= 0) {
    echo "<div class='alert-s alert-danger-c'>Invalid Product ID</div>";
    include '../includes/footer.php'; exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div class='alert-s alert-danger-c'>Product not found</div>";
    include '../includes/footer.php'; exit;
}

$vStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY sku ASC");
$vStmt->execute([$product_id]);
$variants = $vStmt->fetchAll();

$gStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
$gStmt->execute([$product_id]);
$gallery = $gStmt->fetchAll();

$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && empty($_FILES)) {
        $errorMsg = "Upload failed: file is too large. Check your server upload limits.";
    } else {
        $name           = trim($_POST['name']);
        $description    = trim($_POST['description'] ?? '');
        $category_id    = (int)$_POST['category_id'];
        $is_variable    = isset($_POST['is_variable']) ? 1 : 0;
        $regular_price  = (float)($_POST['regular_price'] ?? 0);
        $discount_price = $_POST['discount_price'] !== '' ? (float)$_POST['discount_price'] : null; // Captured Discount Price
        $regular_stock  = (int)($_POST['regular_stock'] ?? 0);
        $status         = trim($_POST['status'] ?? 'draft'); // Captured Status Form Value

        try {
            // Added discount_price column string to backend update query mapping
            $pdo->prepare("UPDATE products SET name=?, description=?, category_id=?, is_variable=?, price=?, discount_price=?, stock=?, status=? WHERE id=?")
                ->execute([$name, $description, $category_id, $is_variable, $regular_price, $discount_price, $regular_stock, $status, $product_id]);

            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
                $ext    = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
                $target = "../../uploads/" . uniqid('img_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['main_image']['tmp_name'], $target)) {
                    $pdo->prepare("UPDATE products SET image = ? WHERE id = ?")
                        ->execute([basename($target), $product_id]);
                }
            }

            if (isset($_FILES['gallery']) && !empty($_FILES['gallery']['name'][0])) {
                foreach ($_FILES['gallery']['name'] as $key => $filename) {
                    if ($_FILES['gallery']['error'][$key] == 0) {
                        $ext    = pathinfo($filename, PATHINFO_EXTENSION);
                        $target = "../../uploads/" . uniqid('gal_', true) . '.' . $ext;
                        if (move_uploaded_file($_FILES['gallery']['tmp_name'][$key], $target)) {
                            $pdo->prepare("INSERT INTO product_images (product_id, image, sort_order) VALUES (?, ?, ?)")
                                ->execute([$product_id, basename($target), $key]);
                        }
                    }
                }
            }

            $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$product_id]);

            if ($is_variable && isset($_POST['variants'])) {
                foreach ($_POST['variants'] as $v) {
                    if (!empty($v['sku']) && !empty($v['price'])) {
                        $pdo->prepare("INSERT INTO product_variants (product_id, sku, price, stock) VALUES (?, ?, ?, ?)")
                            ->execute([$product_id, trim($v['sku']), (float)$v['price'], (int)($v['stock'] ?? 0)]);
                    }
                }
            }

            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            $vStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY sku ASC");
            $vStmt->execute([$product_id]);
            $variants = $vStmt->fetchAll();

            $gStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
            $gStmt->execute([$product_id]);
            $gallery = $gStmt->fetchAll();

            $successMsg = "Product updated successfully!";
        } catch (Exception $e) {
            $errorMsg = "Error: " . htmlspecialchars($e->getMessage());
        }
    }
}

$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<style>
.edit-wrap { max-width: 960px; margin: 0 auto; padding: 28px 0; }

/* Two-column layout */
.edit-grid { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }

/* Section cards */
.edit-section {
    background: #fff; border-radius: 16px;
    box-shadow: 0 1px 6px rgba(0,0,0,.06);
    margin-bottom: 18px; overflow: hidden;
}
.edit-section-hdr {
    padding: 16px 20px; border-bottom: 1px solid #F3F4F6;
    display: flex; align-items: center; gap: 10px;
}
.edit-section-hdr-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: #F3F0FF; color: #6C3FC8;
    display: flex; align-items: center; justify-content: center; font-size: 13px;
}
.edit-section-hdr h6 { margin: 0; font-weight: 700; font-size: 14px; color: #111827; }
.edit-section-body { padding: 20px; }

/* Form fields */
.field-group { margin-bottom: 18px; }
.field-group:last-child { margin-bottom: 0; }
.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

/* Image upload */
.img-upload-zone {
    border: 2px dashed #E5E7EB; border-radius: 12px;
    padding: 20px; text-align: center; cursor: pointer;
    transition: border-color .2s; position: relative;
}
.img-upload-zone:hover { border-color: #6C3FC8; }
.img-upload-zone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.img-upload-zone i { font-size: 24px; color: #9CA3AF; margin-bottom: 8px; }
.img-current {
    width: 100%; height: 180px; object-fit: cover;
    border-radius: 10px; margin-bottom: 12px; display: block;
}
.img-placeholder {
    width: 100%; height: 180px; border-radius: 10px;
    background: #F3F4F6; display: flex; align-items: center;
    justify-content: center; margin-bottom: 12px;
}

/* Gallery */
.gallery-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 12px; }
.gallery-thumb { aspect-ratio: 1; border-radius: 8px; overflow: hidden; position: relative; }
.gallery-thumb img { width: 100%; height: 100%; object-fit: cover; }
.gallery-del {
    position: absolute; top: 4px; right: 4px;
    width: 20px; height: 20px; border-radius: 50%;
    background: rgba(239,68,68,.85); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 9px; cursor: pointer; text-decoration: none;
}

/* Toggle switch */
.toggle-wrap { display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: #F9FAFB; border-radius: 10px; }
.toggle-info h6 { margin: 0; font-size: 13px; font-weight: 600; color: #111827; }
.toggle-info p { margin: 0; font-size: 11px; color: #9CA3AF; }
.toggle-sw { position: relative; width: 44px; height: 24px; flex-shrink: 0; margin-left: auto; }
.toggle-sw input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute; inset: 0; background: #E5E7EB;
    border-radius: 24px; cursor: pointer; transition: .25s;
}
.toggle-slider:before {
    content: ''; position: absolute; width: 18px; height: 18px;
    border-radius: 50%; background: #fff; left: 3px; top: 3px; transition: .25s;
}
.toggle-sw input:checked + .toggle-slider { background: #6C3FC8; }
.toggle-sw input:checked + .toggle-slider:before { transform: translateX(20px); }

/* Variant rows */
.variant-row {
    background: #F9FAFB; border-radius: 12px; padding: 14px;
    border: 1px solid #E5E7EB; margin-bottom: 10px; position: relative;
}
.variant-row:last-child { margin-bottom: 0; }
.variant-del {
    position: absolute; top: 10px; right: 10px;
    width: 28px; height: 28px; border-radius: 7px;
    background: #FEE2E2; color: #EF4444; border: none;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 12px;
}

/* Sticky sidebar bottom */
.edit-sidebar { position: sticky; top: 20px; }

/* Status badge */
.stock-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
}
.stock-ok  { background: #D1FAE5; color: #065F46; }
.stock-low { background: #FEF3C7; color: #92400E; }
.stock-out { background: #FEE2E2; color: #991B1B; }

/* Status indicators */
.status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: capitalize; margin-left: auto;}
.status-active { background: #D1FAE5; color: #065F46; }
.status-inactive { background: #F3F4F6; color: #4B5563; }
.status-draft { background: #DBEAFE; color: #1E40AF; }
</style>

<div class="edit-wrap">

    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <a href="https://amariaestheticsph.com/store/shopapay_api/admin/orders.php" class="btn-outline-c" style="height:36px;padding:0 14px;text-decoration:none">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <div>
            <h1 class="page-title" style="font-size:20px">Edit Product</h1>
            <p class="page-sub">#<?= $product_id ?> — <?= htmlspecialchars($product['name']) ?></p>
        </div>
        <div class="ms-auto d-flex gap-2 align-items-center">
            <?php 
                $currStatus = $product['status'] ?? 'draft';
                $statusClass = 'status-' . $currStatus;
            ?>
            <span class="status-badge <?= $statusClass ?>"><?= $currStatus ?></span>

            <?php
                $stock = (int)$product['stock'];
                $badgeClass = $stock <= 0 ? 'stock-out' : ($stock <= 5 ? 'stock-low' : 'stock-ok');
                $badgeLabel = $stock <= 0 ? 'Out of Stock' : ($stock <= 5 ? 'Low Stock' : 'In Stock');
            ?>
            <span class="stock-badge <?= $badgeClass ?>">
                <i class="fas fa-circle" style="font-size:7px"></i> <?= $badgeLabel ?> (<?= $stock ?>)
            </span>
        </div>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert-s alert-success-c mb-4">
            <i class="fas fa-check-circle me-2"></i> <?= $successMsg ?>
        </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert-s alert-danger-c mb-4">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $errorMsg ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="editForm">
        <div class="edit-grid">

            <div>

                <div class="edit-section">
                    <div class="edit-section-hdr">
                        <div class="edit-section-hdr-icon"><i class="fas fa-info"></i></div>
                        <h6>Basic Information</h6>
                    </div>
                    <div class="edit-section-body">

                        <div class="field-group">
                            <label class="form-lbl">Product Name <span style="color:#EF4444">*</span></label>
                            <input type="text" name="name" class="form-inp"
                                   value="<?= htmlspecialchars($product['name']) ?>" required
                                   placeholder="Enter product name">
                        </div>

                        <div class="field-group">
                            <label class="form-lbl">Description</label>
                            <textarea name="description" class="form-inp"
                                      style="height:120px;resize:vertical;padding:10px"
                                      placeholder="Describe your product..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                        </div>

                        <div class="field-group">
                            <label class="form-lbl">Category <span style="color:#EF4444">*</span></label>
                            <select name="category_id" class="form-inp" required>
                                <option value="">Select a category</option>
                                <?php foreach ($cats as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($c['id'] == $product['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>
                </div>

                <div class="edit-section" id="pricingSection"
                     style="display:<?= $product['is_variable'] ? 'none' : 'block' ?>">
                    <div class="edit-section-hdr">
                        <div class="edit-section-hdr-icon"><i class="fas fa-tag"></i></div>
                        <h6>Pricing & Stock</h6>
                    </div>
                    <div class="edit-section-body">
                        <div class="field-row" style="grid-template-columns: 1fr 1fr 1fr;">
                            <div class="field-group">
                                <label class="form-lbl">Regular Price (₱) <span style="color:#EF4444">*</span></label>
                                <input type="number" step="0.01" min="0" name="regular_price" class="form-inp"
                                       value="<?= $product['price'] ?? '' ?>" placeholder="0.00">
                            </div>
                            <div class="field-group">
                                <label class="form-lbl">Discount Price (₱)</label>
                                <input type="number" step="0.01" min="0" name="discount_price" class="form-inp"
                                       value="<?= $product['discount_price'] ?? '' ?>" placeholder="0.00">
                            </div>
                            <div class="field-group">
                                <label class="form-lbl">Stock Quantity</label>
                                <input type="number" min="0" name="regular_stock" class="form-inp"
                                       value="<?= $product['stock'] ?? 0 ?>" placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="edit-section">
                    <div class="edit-section-hdr">
                        <div class="edit-section-hdr-icon"><i class="fas fa-layer-group"></i></div>
                        <h6>Product Variants</h6>
                    </div>
                    <div class="edit-section-body">

                        <div class="toggle-wrap mb-4">
                            <div class="toggle-info">
                                <h6>Variable Product</h6>
                                <p>Enable for different sizes, colors, or options</p>
                            </div>
                            <label class="toggle-sw">
                                <input type="checkbox" name="is_variable" id="is_variable"
                                       <?= $product['is_variable'] ? 'checked' : '' ?>
                                       onchange="toggleVariantMode()">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div id="variantsArea"
                             style="display:<?= $product['is_variable'] ? 'block' : 'none' ?>">
                            <div id="variantsContainer">
                                <?php foreach ($variants as $i => $v): ?>
                                    <div class="variant-row">
                                        <button type="button" class="variant-del" onclick="removeVariant(this)" title="Remove">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <div class="field-row" style="grid-template-columns:1fr 1fr 1fr">
                                            <div>
                                                <label class="form-lbl">SKU / Label</label>
                                                <input type="text" name="variants[<?= $i ?>][sku]" class="form-inp"
                                                       value="<?= htmlspecialchars($v['sku'] ?? '') ?>"
                                                       placeholder="e.g. Small" required>
                                            </div>
                                            <div>
                                                <label class="form-lbl">Price (₱)</label>
                                                <input type="number" step="0.01" name="variants[<?= $i ?>][price]"
                                                       class="form-inp" value="<?= $v['price'] ?>"
                                                       placeholder="0.00" required>
                                            </div>
                                            <div>
                                                <label class="form-lbl">Stock</label>
                                                <input type="number" name="variants[<?= $i ?>][stock]"
                                                       class="form-inp" value="<?= $v['stock'] ?>"
                                                       placeholder="0" required>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn-outline-c mt-3" onclick="addVariantRow()"
                                    style="width:100%;justify-content:center">
                                <i class="fas fa-plus"></i> Add Variant
                            </button>
                        </div>

                        <?php if (empty($variants) && !$product['is_variable']): ?>
                            <p style="font-size:13px;color:#9CA3AF;margin:0">
                                Enable variable product to add size/color options.
                            </p>
                        <?php endif; ?>

                    </div>
                </div>

            </div>

            <div class="edit-sidebar">

                <div class="edit-section">
                    <div class="edit-section-hdr">
                        <div class="edit-section-hdr-icon"><i class="fas fa-toggle-on"></i></div>
                        <h6>Visibility Status</h6>
                    </div>
                    <div class="edit-section-body">
                        <div class="field-group">
                            <label class="form-lbl">Product Status</label>
                            <select name="status" class="form-inp" style="width: 100%;">
                                <option value="active" <?= (($product['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= (($product['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                <option value="draft" <?= (($product['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Draft</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="edit-section">
                    <div class="edit-section-hdr">
                        <div class="edit-section-hdr-icon"><i class="fas fa-image"></i></div>
                        <h6>Main Image</h6>
                    </div>
                    <div class="edit-section-body">

                        <?php if ($product['image']): ?>
                            <img src="../../uploads/<?= htmlspecialchars($product['image']) ?>"
                                 class="img-current" id="mainPreview" alt="main">
                        <?php else: ?>
                            <div class="img-placeholder" id="mainPlaceholder">
                                <i class="fas fa-image fa-2x" style="color:#D1D5DB"></i>
                            </div>
                            <img id="mainPreview" class="img-current" style="display:none" alt="preview">
                        <?php endif; ?>

                        <div class="img-upload-zone">
                            <input type="file" name="main_image" accept="image/*"
                                   onchange="previewMain(this)">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p style="margin:4px 0 0;font-size:12px;color:#6B7280">Click to change image</p>
                            <p style="margin:2px 0 0;font-size:11px;color:#9CA3AF">PNG, JPG up to 20MB</p>
                        </div>
                    </div>
                </div>

                <div class="edit-section">
                    <div class="edit-section-hdr">
                        <div class="edit-section-hdr-icon"><i class="fas fa-images"></i></div>
                        <h6>Product Gallery</h6>
                        <?php if (!empty($gallery)): ?>
                            <span style="margin-left:auto;font-size:11px;color:#9CA3AF"><?= count($gallery) ?> images</span>
                        <?php endif; ?>
                    </div>
                    <div class="edit-section-body">

                        <?php if (!empty($gallery)): ?>
                            <div class="gallery-grid">
                                <?php foreach ($gallery as $img): ?>
                                    <div class="gallery-thumb">
                                        <img src="../../uploads/<?= htmlspecialchars($img['image']) ?>" alt="gallery">
                                        <a href="?id=<?= $product_id ?>&del_gallery=<?= $img['id'] ?>"
                                           class="gallery-del"
                                           onclick="return confirm('Remove this image?')">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align:center;padding:20px 0;color:#9CA3AF">
                                <i class="fas fa-images fa-2x mb-2 d-block"></i>
                                <p style="font-size:12px;margin:0">No gallery images</p>
                            </div>
                        <?php endif; ?>

                        <div class="img-upload-zone mt-2">
                            <input type="file" name="gallery[]" accept="image/*" multiple
                                   onchange="previewGallery(this)">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p style="margin:4px 0 0;font-size:12px;color:#6B7280">Add more images</p>
                            <p style="margin:2px 0 0;font-size:11px;color:#9CA3AF">Select multiple files</p>
                        </div>

                        <div id="galleryPreviewRow" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px"></div>
                    </div>
                </div>

                <button type="submit" class="btn-primary-c"
                        style="width:100%;height:48px;font-size:14px;border-radius:12px;justify-content:center">
                    <i class="fas fa-save"></i> Save Changes
                </button>

                <a href="index.php" class="btn-outline-c mt-2"
                   style="width:100%;height:44px;font-size:13px;border-radius:12px;justify-content:center;text-decoration:none">
                    Cancel
                </a>

            </div>
        </div>
    </form>
</div>

<script>
// ── Variant Mode Toggle ──
function toggleVariantMode() {
    const isVar = document.getElementById('is_variable').checked;
    document.getElementById('variantsArea').style.display    = isVar ? 'block' : 'none';
    document.getElementById('pricingSection').style.display  = isVar ? 'none'  : 'block';
}

// ── Add Variant Row ──
let variantIndex = <?= count($variants) ?>;
function addVariantRow() {
    const container = document.getElementById('variantsContainer');
    const div = document.createElement('div');
    div.className = 'variant-row';
    div.innerHTML = `
        <button type="button" class="variant-del" onclick="removeVariant(this)" title="Remove">
            <i class="fas fa-times"></i>
        </button>
        <div class="field-row" style="grid-template-columns:1fr 1fr 1fr">
            <div>
                <label class="form-lbl">SKU / Label</label>
                <input type="text" name="variants[${variantIndex}][sku]" class="form-inp" placeholder="e.g. Small" required>
            </div>
            <div>
                <label class="form-lbl">Price (₱)</label>
                <input type="number" step="0.01" name="variants[${variantIndex}][price]" class="form-inp" placeholder="0.00" required>
            </div>
            <div>
                <label class="form-lbl">Stock</label>
                <input type="number" name="variants[${variantIndex}][stock]" class="form-inp" placeholder="0" required>
            </div>
        </div>`;
    container.appendChild(div);
    variantIndex++;
}

function removeVariant(btn) {
    btn.closest('.variant-row').remove();
}

// ── Image Previews ──
function previewMain(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('mainPreview');
        const placeholder = document.getElementById('mainPlaceholder');
        if (placeholder) placeholder.style.display = 'none';
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}

function previewGallery(input) {
    const row = document.getElementById('galleryPreviewRow');
    row.innerHTML = '';
    if (!input.files) return;
    Array.from(input.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = 'width:56px;height:56px;object-fit:cover;border-radius:8px;border:1.5px solid #E5E7EB';
            row.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}

window.onload = toggleVariantMode;
</script>

<?php
// Handle gallery image delete
if (isset($_GET['del_gallery'])) {
    $gid = (int)$_GET['del_gallery'];
    $gi = $pdo->prepare("SELECT image FROM product_images WHERE id=? AND product_id=?");
    $gi->execute([$gid, $product_id]);
    $gf = $gi->fetchColumn();
    if ($gf && file_exists("../../uploads/$gf")) unlink("../../uploads/$gf");
    $pdo->prepare("DELETE FROM product_images WHERE id=?")->execute([$gid]);
    header("Location: edit.php?id=$product_id&success=gallery_deleted");
    exit;
}

include '../includes/footer.php';
?>