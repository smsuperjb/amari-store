<?php
ob_start();
require_once '../../config/database.php';

$success = '';
$error   = '';

// ══════════════════════════════════════════════════════════════
//  SAVE PRODUCT
// ══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price       = (float)($_POST['price']     ?? 0);
    $stock       = (int)($_POST['stock']       ?? 0);
    $status      = $_POST['status']            ?? 'active';
    $is_variable = isset($_POST['is_variable']) ? 1 : 0;

    if (empty($name)) {
        $error = 'Product name is required.';
    } elseif (!$category_id) {
        $error = 'Please select a category.';
    } elseif (!$is_variable && $price <= 0) {
        $error = 'Please enter a valid price.';
    } else {

        // ── Upload main image ─────────────────────────────────
        $main_image = null;
        if (!empty($_FILES['main_image']['name'])) {
            $allowed = ['jpg','jpeg','png','webp','gif'];
            $ext     = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $error = 'Invalid image format. Allowed: JPG, PNG, WEBP, GIF.';
            } elseif ($_FILES['main_image']['size'] > 5 * 1024 * 1024) {
                $error = 'Image must be under 5MB.';
            } else {
                $uploadDir = '../../uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename   = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['main_image']['name']);
                $targetPath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['main_image']['tmp_name'], $targetPath)) {
                    $main_image = $filename;
                } else {
                    $error = 'Failed to upload main image.';
                }
            }
        }

        if (empty($error)) {
            try {
                // ── Insert product ────────────────────────────
                $stmt = $pdo->prepare("
                    INSERT INTO products
                        (name, description, category_id, price, stock,
                         status, image, is_variable, created_at)
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $name, $description, $category_id,
                    $is_variable ? 0 : $price,
                    $is_variable ? 0 : $stock,
                    $status, $main_image, $is_variable,
                ]);
                $product_id = (int)$pdo->lastInsertId();

                // ── Gallery images ────────────────────────────
                if (!empty($_FILES['gallery']['name'][0])) {
                    $uploadDir = '../../uploads/products/gallery/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    foreach ($_FILES['gallery']['tmp_name'] as $i => $tmp) {
                        if (empty($tmp) || $_FILES['gallery']['error'][$i] !== UPLOAD_ERR_OK) continue;
                        $fn = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['gallery']['name'][$i]);
                        if (move_uploaded_file($tmp, $uploadDir . $fn)) {
                            try {
                                $pdo->prepare("INSERT INTO product_gallery (product_id, image) VALUES (?, ?)")
                                    ->execute([$product_id, $fn]);
                            } catch (Exception $e) { /* table may not exist — skip */ }
                        }
                    }
                }

                // ── Variants ──────────────────────────────────
                if ($is_variable && !empty($_POST['variants'])) {
                    $vStmt = $pdo->prepare("
                        INSERT INTO product_variants (product_id, sku, price, stock)
                        VALUES (?, ?, ?, ?)
                    ");
                    foreach ($_POST['variants'] as $v) {
                        $vsku   = trim($v['sku']   ?? '');
                        $vprice = (float)($v['price'] ?? 0);
                        $vstock = (int)($v['stock']   ?? 0);
                        if (empty($vsku) || $vprice <= 0) continue;
                        $vStmt->execute([$product_id, $vsku, $vprice, $vstock]);
                    }
                }

                // ── Absolute redirect (fixes 405 on nginx) ────
                $base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                $path = rtrim(dirname($_SERVER['REQUEST_URI']), '/');
                header("Location: $base$path/add-product.php?success=1");
                exit;

            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// ── Fetch categories ──────────────────────────────────────────
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// ══════════════════════════════════════════════════════════════
//  HTML OUTPUT
// ══════════════════════════════════════════════════════════════
include '../includes/header.php';
?>

<style>
/* ── Page layout ── */
.ap-wrap { max-width: 900px; margin: 0 auto; padding: 0 0 3rem; }

/* ── Section cards ── */
.ap-section {
  background: #fff;
  border: 1px solid #F0EBE8;
  border-radius: 16px;
  margin-bottom: 1.25rem;
  overflow: hidden;
}
.ap-section-hdr {
  display: flex; align-items: center; gap: .6rem;
  padding: 1.1rem 1.5rem;
  border-bottom: 1px solid #F5F0ED;
  background: #FDFAF8;
}
.ap-section-hdr .hdr-icon {
  width: 30px; height: 30px; border-radius: 8px;
  background: #F0EBE8;
  display: flex; align-items: center; justify-content: center;
  color: #6C3FC8; font-size: .85rem; flex-shrink: 0;
}
.ap-section-hdr h5 {
  font-size: .82rem; font-weight: 700;
  letter-spacing: .06em; text-transform: uppercase;
  color: #374151; margin: 0;
}
.ap-section-body { padding: 1.5rem; }

/* ── Form controls ── */
.field-group { margin-bottom: 1.1rem; }
.field-group:last-child { margin-bottom: 0; }
.field-label {
  display: block; margin-bottom: .4rem;
  font-size: .72rem; font-weight: 600;
  letter-spacing: .06em; text-transform: uppercase;
  color: #6B7280;
}
.field-required { color: #EF4444; margin-left: 2px; }
.field-ctrl {
  width: 100%;
  padding: .7rem 1rem;
  background: #FDFAF8;
  border: 1.5px solid #E5D8CF;
  border-radius: 10px;
  font-family: inherit; font-size: .88rem; color: #1F2937;
  outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.field-ctrl:focus {
  border-color: #6C3FC8;
  box-shadow: 0 0 0 3px rgba(108,63,200,.1);
}
.field-ctrl::placeholder { color: #D1C4BE; }
textarea.field-ctrl { resize: vertical; min-height: 96px; }
select.field-ctrl {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8'%3E%3Cpath fill='%236C3FC8' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right .9rem center;
  background-size: 10px; padding-right: 2.5rem; appearance: none;
}

/* ── Image upload zone ── */
.upload-zone {
  border: 2px dashed #E5D8CF; border-radius: 12px;
  padding: 2rem 1rem; text-align: center; cursor: pointer;
  background: #FDFAF8; transition: border-color .2s, background .2s;
  position: relative;
}
.upload-zone:hover, .upload-zone.drag-over {
  border-color: #6C3FC8; background: #F5F0FF;
}
.upload-zone input[type="file"] {
  position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.upload-zone .uz-icon {
  width: 44px; height: 44px; border-radius: 50%;
  background: #EDE9FF;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto .75rem; font-size: 1.1rem; color: #6C3FC8;
}
.upload-zone p { margin: 0; font-size: .8rem; color: #9CA3AF; }
.upload-zone strong { color: #6C3FC8; font-weight: 600; }
.image-preview {
  margin-top: .85rem; display: flex; flex-wrap: wrap; gap: .5rem;
}
.image-thumb {
  width: 72px; height: 72px; border-radius: 8px;
  object-fit: cover; border: 2px solid #E5D8CF;
  transition: border-color .2s;
}
.image-thumb:hover { border-color: #6C3FC8; }

/* ── Toggle switch ── */
.toggle-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: .9rem 1.1rem;
  background: #FDFAF8; border: 1.5px solid #E5D8CF;
  border-radius: 10px;
}
.toggle-row .toggle-info strong {
  display: block; font-size: .85rem; color: #1F2937; font-weight: 600;
}
.toggle-row .toggle-info span { font-size: .75rem; color: #9CA3AF; }
.switch { position: relative; display: inline-block; width: 44px; height: 24px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider {
  position: absolute; inset: 0; background: #D1D5DB;
  border-radius: 24px; cursor: pointer;
  transition: background .2s;
}
.slider::before {
  content: ''; position: absolute;
  width: 18px; height: 18px; border-radius: 50%;
  background: #fff; left: 3px; top: 3px;
  transition: transform .2s;
  box-shadow: 0 1px 4px rgba(0,0,0,.2);
}
input:checked + .slider { background: #6C3FC8; }
input:checked + .slider::before { transform: translateX(20px); }

/* ── Status pills ── */
.status-pills { display: flex; gap: .5rem; }
.status-pill {
  flex: 1; padding: .6rem; text-align: center;
  border: 1.5px solid #E5D8CF; border-radius: 8px;
  font-size: .75rem; font-weight: 600; cursor: pointer;
  transition: all .2s;
}
.status-pill input { display: none; }
.status-pill.active-pill   { border-color: #059669; background: #ECFDF5; color: #059669; }
.status-pill.inactive-pill { border-color: #DC2626; background: #FEF2F2; color: #DC2626; }
.status-pill.draft-pill    { border-color: #D97706; background: #FFFBEB; color: #D97706; }
.status-pill input:checked ~ span { font-weight: 700; }

/* ── Variant card ── */
.variant-card {
  background: #FDFAF8; border: 1.5px solid #E5D8CF;
  border-radius: 12px; padding: 1.1rem 1.25rem;
  margin-bottom: .75rem; position: relative;
}
.variant-card .remove-variant {
  position: absolute; top: .75rem; right: .75rem;
  width: 28px; height: 28px; border-radius: 50%;
  background: #FEE2E2; border: none; color: #DC2626;
  font-size: .75rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background .2s;
}
.variant-card .remove-variant:hover { background: #FCA5A5; }

/* ── Grid helpers ── */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }

/* ── Save bar ── */
.save-bar {
  position: sticky; bottom: 0; left: 0; right: 0;
  background: rgba(255,255,255,.95); backdrop-filter: blur(12px);
  border-top: 1px solid #F0EBE8;
  padding: 1rem 0;
  display: flex; align-items: center; justify-content: space-between;
  gap: 1rem; flex-wrap: wrap;
  z-index: 100;
}
.save-bar-hint { font-size: .78rem; color: #9CA3AF; }
.save-bar-actions { display: flex; gap: .75rem; }

/* ── Alerts ── */
.ap-alert {
  display: flex; align-items: flex-start; gap: .7rem;
  padding: .9rem 1.1rem; border-radius: 10px;
  font-size: .83rem; margin-bottom: 1.25rem;
}
.ap-alert-error   { background: #FFF1F2; color: #9F1239; border: 1px solid #FECDD3; }
.ap-alert-success { background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; }

@media (max-width: 640px) {
  .grid-2, .grid-3 { grid-template-columns: 1fr; }
  .ap-section-body { padding: 1rem; }
}
</style>

<div class="orders-wrap">

  <!-- Page header -->
  <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
      <div class="page-title">Add New Product</div>
      <div class="page-sub">Fill in the details below to list a new product</div>
    </div>
    <a href="product-list.php" class="btn-outline-c">
      <i class="fas fa-arrow-left"></i> Back to Products
    </a>
  </div>

  <!-- Alerts -->
  <?php if (!empty($error)): ?>
    <div class="ap-alert ap-alert-error">
      <i class="fas fa-circle-xmark" style="margin-top:1px;flex-shrink:0"></i>
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['success'])): ?>
    <div class="ap-alert ap-alert-success">
      <i class="fas fa-circle-check" style="margin-top:1px;flex-shrink:0"></i>
      Product saved successfully!
      <a href="add-product.php" style="margin-left:.5rem;font-weight:600;color:inherit">Add another</a> ·
      <a href="product-list.php" style="margin-left:.25rem;font-weight:600;color:inherit">View all products</a>
    </div>
  <?php endif; ?>

  <div class="ap-wrap">
  <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data" id="productForm">

    <!-- ── 1. BASIC INFO ───────────────────────────── -->
    <div class="ap-section">
      <div class="ap-section-hdr">
        <div class="hdr-icon"><i class="fas fa-tag"></i></div>
        <h5>Basic Information</h5>
      </div>
      <div class="ap-section-body">

        <div class="field-group">
          <label class="field-label">Product Name <span class="field-required">*</span></label>
          <input type="text" name="name" class="field-ctrl"
                 placeholder="e.g. Brightening Glow Serum 30ml" required
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>

        <div class="field-group">
          <label class="field-label">Description</label>
          <textarea name="description" class="field-ctrl"
                    placeholder="Describe what makes this product special — ingredients, benefits, how to use…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="grid-2">
          <div class="field-group">
            <label class="field-label">Category <span class="field-required">*</span></label>
            <select name="category_id" class="field-ctrl" required>
              <option value="">— Select Category —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"
                  <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label">Status</label>
            <div class="status-pills">
              <?php foreach (['active'=>['Active','active-pill','fa-check-circle'], 'inactive'=>['Inactive','inactive-pill','fa-ban'], 'draft'=>['Draft','draft-pill','fa-pencil']] as $val=>[$lbl,$cls,$ico]): ?>
                <label class="status-pill <?= $cls ?>" onclick="setPill(this)">
                  <input type="radio" name="status" value="<?= $val ?>"
                         <?= ($_POST['status'] ?? 'active') === $val ? 'checked' : '' ?>>
                  <span><i class="fas <?= $ico ?>" style="margin-right:.3rem"></i><?= $lbl ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ── 2. PRICING & STOCK ──────────────────────── -->
    <div class="ap-section">
      <div class="ap-section-hdr">
        <div class="hdr-icon"><i class="fas fa-peso-sign"></i></div>
        <h5>Pricing & Stock</h5>
      </div>
      <div class="ap-section-body">

        <!-- Variable toggle -->
        <div class="toggle-row" style="margin-bottom:1.25rem">
          <div class="toggle-info">
            <strong>Variable Product</strong>
            <span>Enable if this product has variants (e.g. sizes, shades)</span>
          </div>
          <label class="switch">
            <input type="checkbox" name="is_variable" id="isVariable"
                   onchange="toggleVariantMode()"
                   <?= isset($_POST['is_variable']) ? 'checked' : '' ?>>
            <span class="slider"></span>
          </label>
        </div>

        <!-- Simple pricing -->
        <div id="simplePricing" class="grid-3">
          <div class="field-group">
            <label class="field-label">Price (₱) <span class="field-required">*</span></label>
            <input type="number" step="0.01" min="0" name="price" class="field-ctrl"
                   id="simplePrice" placeholder="0.00"
                   value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
          </div>
          <div class="field-group">
            <label class="field-label">Compare Price (₱)</label>
            <input type="number" step="0.01" min="0" name="compare_price" class="field-ctrl"
                   placeholder="Original / slashed price"
                   value="<?= htmlspecialchars($_POST['compare_price'] ?? '') ?>">
          </div>
          <div class="field-group">
            <label class="field-label">Stock Quantity</label>
            <input type="number" min="0" name="stock" class="field-ctrl"
                   placeholder="0" value="<?= htmlspecialchars($_POST['stock'] ?? '0') ?>">
          </div>
        </div>

        <!-- Variants -->
        <div id="variantsArea" style="display:none">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.85rem">
            <p style="font-size:.8rem; color:#6B7280; margin:0">Add each variant with its own price and stock.</p>
            <button type="button" class="btn-primary-c" onclick="addVariantRow()" style="height:34px; font-size:12px">
              <i class="fas fa-plus"></i> Add Variant
            </button>
          </div>
          <div id="variantsContainer"></div>
        </div>

      </div>
    </div>

    <!-- ── 3. IMAGES ───────────────────────────────── -->
    <div class="ap-section">
      <div class="ap-section-hdr">
        <div class="hdr-icon"><i class="fas fa-image"></i></div>
        <h5>Product Images</h5>
      </div>
      <div class="ap-section-body">
        <div class="grid-2">

          <!-- Main image -->
          <div class="field-group">
            <label class="field-label">Main Image</label>
            <div class="upload-zone" id="mainZone"
                 ondragover="event.preventDefault(); this.classList.add('drag-over')"
                 ondragleave="this.classList.remove('drag-over')"
                 ondrop="handleDrop(event,'mainZone','mainPreview')">
              <input type="file" name="main_image" accept="image/*"
                     onchange="previewImages(this,'mainPreview',true)">
              <div class="uz-icon"><i class="fas fa-cloud-upload-alt"></i></div>
              <p><strong>Click to upload</strong> or drag & drop</p>
              <p style="margin-top:.25rem">PNG, JPG, WEBP — max 5MB</p>
            </div>
            <div class="image-preview" id="mainPreview"></div>
          </div>

          <!-- Gallery -->
          <div class="field-group">
            <label class="field-label">Gallery Images <span style="color:#aaa;font-weight:400">(optional, multiple)</span></label>
            <div class="upload-zone" id="galleryZone"
                 ondragover="event.preventDefault(); this.classList.add('drag-over')"
                 ondragleave="this.classList.remove('drag-over')"
                 ondrop="handleDrop(event,'galleryZone','galleryPreview')">
              <input type="file" name="gallery[]" accept="image/*" multiple
                     onchange="previewImages(this,'galleryPreview',false)">
              <div class="uz-icon"><i class="fas fa-images"></i></div>
              <p><strong>Click to upload</strong> or drag & drop</p>
              <p style="margin-top:.25rem">Upload multiple images for the product gallery</p>
            </div>
            <div class="image-preview" id="galleryPreview"></div>
          </div>

        </div>
      </div>
    </div>

    <!-- ── SAVE BAR ────────────────────────────────── -->
    <div class="save-bar">
      <p class="save-bar-hint">
        <i class="fas fa-circle-info" style="color:#6C3FC8; margin-right:.35rem"></i>
        All fields marked <span style="color:#EF4444">*</span> are required.
      </p>
      <div class="save-bar-actions">
        <a href="product-list.php" class="btn-outline-c">Discard</a>
        <button type="submit" class="btn-primary-c" id="saveBtn">
          <i class="fas fa-floppy-disk"></i> Save Product
        </button>
      </div>
    </div>

  </form>
  </div><!-- /ap-wrap -->
</div>

<script>
// ── Status pill highlight ───────────────────────────────────
function setPill(label) {
  document.querySelectorAll('.status-pill').forEach(p => { p.style.boxShadow = ''; });
  label.style.boxShadow = '0 0 0 3px rgba(108,63,200,.15)';
}
document.querySelectorAll('.status-pill input:checked').forEach(i => setPill(i.closest('.status-pill')));

// ── Toggle variant mode ─────────────────────────────────────
function toggleVariantMode() {
  const isVar = document.getElementById('isVariable').checked;
  document.getElementById('simplePricing').style.display = isVar ? 'none' : 'grid';
  document.getElementById('variantsArea').style.display  = isVar ? 'block' : 'none';
  document.getElementById('simplePrice').required        = !isVar;
  if (isVar && document.getElementById('variantsContainer').children.length === 0) {
    addVariantRow();
  }
}
if (document.getElementById('isVariable').checked) toggleVariantMode();

// ── Variant rows ────────────────────────────────────────────
let vCount = 0;
function addVariantRow() {
  vCount++;
  const c   = document.getElementById('variantsContainer');
  const div = document.createElement('div');
  div.className = 'variant-card';
  div.id = 'v_' + vCount;
  div.innerHTML = `
    <button type="button" class="remove-variant" onclick="this.closest('.variant-card').remove()">
      <i class="fas fa-xmark"></i>
    </button>
    <div class="grid-3">
      <div class="field-group">
        <label class="field-label">Variant Name / SKU <span class="field-required">*</span></label>
        <input type="text" name="variants[${vCount}][sku]" class="field-ctrl"
               placeholder="e.g. 30ml / Rose Gold" required>
      </div>
      <div class="field-group">
        <label class="field-label">Price (₱) <span class="field-required">*</span></label>
        <input type="number" step="0.01" min="0"
               name="variants[${vCount}][price]" class="field-ctrl"
               placeholder="0.00" required>
      </div>
      <div class="field-group">
        <label class="field-label">Stock</label>
        <input type="number" min="0"
               name="variants[${vCount}][stock]" class="field-ctrl" value="10">
      </div>
    </div>
  `;
  c.appendChild(div);
}

// ── Image preview ───────────────────────────────────────────
function previewImages(input, previewId, single) {
  const wrap = document.getElementById(previewId);
  if (single) wrap.innerHTML = '';
  Array.from(input.files).slice(0, single ? 1 : 10).forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.className = 'image-thumb';
      img.title = file.name;
      wrap.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}

function handleDrop(e, zoneId, previewId) {
  e.preventDefault();
  document.getElementById(zoneId).classList.remove('drag-over');
  const files = e.dataTransfer.files;
  if (!files.length) return;
  const wrap = document.getElementById(previewId);
  wrap.innerHTML = '';
  Array.from(files).slice(0, 10).forEach(file => {
    const reader = new FileReader();
    reader.onload = ev => {
      const img = document.createElement('img');
      img.src = ev.target.result;
      img.className = 'image-thumb';
      wrap.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}

// ── Save button feedback ────────────────────────────────────
document.getElementById('productForm').addEventListener('submit', function() {
  const btn = document.getElementById('saveBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';
});
</script>

<?php include '../includes/footer.php'; ?>