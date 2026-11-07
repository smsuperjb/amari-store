<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . STORE_URL . '/shop.php'); exit; }

// ── DETECT COLUMNS ────────────────────────────────────────────
$productCols     = getColumns($pdo, 'products');
$hasStatus       = in_array('status',        $productCols);
$hasDiscountPrice = in_array('discount_price', $productCols); // Updated
$hasCategoryId   = in_array('category_id',   $productCols);
$hasDescription  = in_array('description',   $productCols);
$hasStock        = in_array('stock',         $productCols);
$hasImage        = in_array('image',         $productCols);

$categoryCols = getColumns($pdo, 'categories');

// ── FETCH PRODUCT ─────────────────────────────────────────────
$sel = 'p.id, p.name, p.price' .
       ($hasImage         ? ', p.image'          : '') .
       ($hasDiscountPrice ? ', p.discount_price' : '') . // Updated
       ($hasDescription   ? ', p.description'    : '') .
       ($hasStock         ? ', p.stock'          : '') .
       ($hasCategoryId    ? ', p.category_id'    : '') .
       (($hasCategoryId && !empty($categoryCols)) ? ', c.name AS category_name, c.id AS cat_id' : '');

$joinSQL = ($hasCategoryId && !empty($categoryCols)) ? 'LEFT JOIN categories c ON p.category_id = c.id' : '';
$where   = 'WHERE p.id = ?' . ($hasStatus ? " AND p.status = 'active'" : '');

try {
    $stmt = $pdo->prepare("SELECT $sel FROM products p $joinSQL $where");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $product = null; }

if (!$product) { header('Location: ' . STORE_URL . '/shop.php'); exit; }

// ── FETCH PRODUCT GALLERY IMAGES ──────────────────────────────
$galleryImages = [];
try {
    $gStmt = $pdo->prepare("SELECT image FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
    $gStmt->execute([$id]);
    $galleryImages = $gStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $galleryImages = []; }

// ── RELATED PRODUCTS ──────────────────────────────────────────
$related = [];
if ($hasCategoryId && !empty($product['category_id'])) {
    try {
        $rSel = 'p.id, p.name, p.price' .
                ($hasImage         ? ', p.image'          : '') .
                ($hasDiscountPrice ? ', p.discount_price' : ''); // Updated
        $rStmt = $pdo->prepare("SELECT $rSel FROM products p WHERE p.id != ? AND p.category_id = ?" .
                               ($hasStatus ? " AND p.status='active'" : '') . " ORDER BY RAND() LIMIT 4");
        $rStmt->execute([$id, $product['category_id']]);
        $related = $rStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $related = []; }
}

$imgUrl      = !empty($product['image']) ? productImageUrl($product['image']) : '';
$inStock     = !$hasStock || ($product['stock'] ?? 1) > 0;

// ── NEW DISCOUNT LOGIC (Using discount_price) ─────────────────
// Discount is active if discount_price is set, greater than 0, and lower than the base price
$hasDiscount = $hasDiscountPrice && !empty($product['discount_price']) && (float)$product['discount_price'] > 0 && (float)$product['discount_price'] < (float)$product['price'];

$pageTitle = e($product['name']) . ' — ' . SITE_NAME;
$pageDesc  = !empty($product['description']) ? mb_strimwidth(strip_tags($product['description']), 0, 160, '…') : 'Shop ' . $product['name'] . ' at Amaria Aesthetics.';

include __DIR__ . '/includes/header.php';
?>

<div class="container section-pad-sm">

  <div class="breadcrumb" style="margin-bottom:2rem">
    <a href="<?= STORE_URL ?>/index.php">Home</a>
    <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
    <a href="<?= STORE_URL ?>/shop.php">Shop</a>
    <?php if (!empty($product['category_name'])): ?>
      <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
      <a href="<?= STORE_URL ?>/shop.php?category=<?= (int)$product['cat_id'] ?>">
        <?= e($product['category_name']) ?>
      </a>
    <?php endif; ?>
    <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
    <span><?= e(mb_strimwidth($product['name'], 0, 40, '…')) ?></span>
  </div>

  <div style="display:grid; grid-template-columns:1fr 1fr; gap:4rem; align-items:start">

    <div>
      <div style="aspect-ratio:4/5; border-radius:var(--r-lg); overflow:hidden; background:var(--ivory-deep); margin-bottom:1rem; position:relative">
        <?php if ($imgUrl): ?>
          <img id="mainProductImg" src="<?= e($imgUrl) ?>" alt="<?= e($product['name']) ?>"
               style="width:100%; height:100%; object-fit:cover; transition:transform .5s var(--ease)">
        <?php else: ?>
          <div class="pc-placeholder" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:.5rem;color:var(--rose-mid)">
            <i class="fas fa-spa" style="font-size:4rem;opacity:.2"></i>
          </div>
        <?php endif; ?>

        <div style="position:absolute; top:1rem; left:1rem; display:flex; flex-direction:column; gap:.4rem">
          <?php if ($hasDiscount): ?>
            <span class="pc-badge pc-badge-sale">
              <?= round((1 - (float)$product['discount_price'] / (float)$product['price']) * 100) ?>% Off
            </span>
          <?php else: ?>
            <span class="pc-badge pc-badge-new">New</span>
          <?php endif; ?>
          <?php if (!$inStock): ?>
            <span class="pc-badge" style="background:#aaa;color:#fff">Out of Stock</span>
          <?php endif; ?>
        </div>
      </div>

      <div style="display:flex; gap:.65rem; flex-wrap:wrap;">
        <?php if ($imgUrl): ?>
          <img src="<?= e($imgUrl) ?>" alt="Main Featured Image"
               data-thumb="<?= e($imgUrl) ?>"
               style="width:72px;height:72px;object-fit:cover;border-radius:var(--r-sm);
                      border:2px solid var(--rose);cursor:pointer;transition:.2s"
               class="product-gallery-thumb is-active"
               onclick="updateProductImage(this)">
        <?php endif; ?>

        <?php foreach ($galleryImages as $gal): 
          $galUrl = !empty($gal['image']) ? productImageUrl($gal['image']) : '';
          if ($galUrl):
        ?>
          <img src="<?= e($galUrl) ?>" alt="Gallery Image"
               data-thumb="<?= e($galUrl) ?>"
               style="width:72px;height:72px;object-fit:cover;border-radius:var(--r-sm);
                      border:2px solid transparent;cursor:pointer;transition:.2s"
               class="product-gallery-thumb"
               onclick="updateProductImage(this)">
        <?php 
          endif;
        endforeach; 
        ?>
      </div>
    </div>

    <div>
      <?php if (!empty($product['category_name'])): ?>
        <div class="pc-category" style="margin-bottom:.5rem; font-size:.7rem">
          <?= e($product['category_name']) ?>
        </div>
      <?php endif; ?>

      <h1 style="font-family:var(--font-display); font-size:clamp(1.8rem,3.5vw,2.8rem);
                 font-weight:400; line-height:1.15; margin-bottom:1rem">
        <?= e($product['name']) ?>
      </h1>

      <div style="display:flex; align-items:center; gap:.75rem; margin-bottom:1.25rem">
        <span style="color:var(--gold); font-size:1rem; letter-spacing:2px">★★★★★</span>
        <span style="font-size:.78rem; color:var(--ink-soft)">4.9 · Verified Reviews</span>
      </div>

      <div style="margin-bottom:1.75rem">
        <span style="font-size:2rem; font-weight:500; color:var(--mocha)">
          <?= formatPrice($hasDiscount ? (float)$product['discount_price'] : (float)$product['price']) ?>
        </span>
        
        <?php if ($hasDiscount): ?>
          <span style="font-size:1.1rem; color:#aaa; text-decoration:line-through; margin-left:.75rem">
            <?= formatPrice((float)$product['price']) ?>
          </span>
          <span style="display:inline-block; margin-left:.5rem; padding:.2rem .7rem;
                       background:var(--rose); color:#fff; border-radius:var(--r-pill);
                       font-size:.7rem; font-weight:600">
            Save <?= formatPrice((float)$product['price'] - (float)$product['discount_price']) ?>
          </span>
        <?php endif; ?>
      </div>

      <?php if (!empty($product['description'])): ?>
        <div style="font-size:.9rem; line-height:1.8; color:var(--ink-soft); margin-bottom:2rem;
                    padding-bottom:2rem; border-bottom:1px solid var(--mist)">
          <?= nl2br(e($product['description'])) ?>
        </div>
      <?php else: ?>
        <div style="font-size:.9rem; line-height:1.8; color:var(--ink-soft); margin-bottom:2rem;
                    padding-bottom:2rem; border-bottom:1px solid var(--mist)">
          Premium quality product from Amaria Aesthetics — crafted with care for your skin.
        </div>
      <?php endif; ?>

      <?php if ($inStock): ?>
        <div style="display:flex; gap:1rem; align-items:center; margin-bottom:1.25rem; flex-wrap:wrap">
          <div style="display:flex; align-items:center; gap:.5rem;
                      border:1.5px solid var(--mist); border-radius:var(--r-sm); padding:.25rem">
            <button onclick="changeQty(-1)"
                    style="width:36px;height:36px;border:none;background:none;font-size:1.1rem;
                           color:var(--mocha);cursor:pointer;display:flex;align-items:center;justify-content:center">−</button>
            <input type="number" id="productQty" value="1" min="1" max="99"
                   style="width:48px;text-align:center;border:none;background:none;
                          font-size:.95rem;font-weight:500;outline:none;padding:0">
            <button onclick="changeQty(1)"
                    style="width:36px;height:36px;border:none;background:none;font-size:1.1rem;
                           color:var(--mocha);cursor:pointer;display:flex;align-items:center;justify-content:center">+</button>
          </div>
          <button id="addToCartBtn"
                  data-add-cart="<?= (int)$product['id'] ?>"
                  style="flex:1; padding:.85rem 1.5rem; background:var(--mocha); color:var(--ivory);
                         border:none; border-radius:var(--r-xs); font-family:var(--font-body);
                         font-size:.78rem; font-weight:500; letter-spacing:.1em; text-transform:uppercase;
                         cursor:pointer; transition:all .25s var(--ease); display:flex; align-items:center;
                         justify-content:center; gap:.6rem; min-width:180px">
            <i class="fas fa-bag-shopping"></i> Add to Cart
          </button>
          <button class="pc-wishlist" style="position:static;width:48px;height:48px;flex-shrink:0" aria-label="Wishlist">
            <i class="far fa-heart"></i>
          </button>
        </div>

        <a href="<?= STORE_URL ?>/checkout.php?buy_now=<?= (int)$product['id'] ?>"
           class="btn btn-rose btn-lg" style="width:100%; justify-content:center; margin-bottom:1.5rem">
          <i class="fas fa-bolt"></i> Buy Now
        </a>

      <?php else: ?>
        <div class="alert alert-info" style="margin-bottom:1.5rem">
          <i class="fas fa-circle-info"></i> This product is currently out of stock.
        </div>
      <?php endif; ?>

      <div style="display:flex; flex-wrap:wrap; gap:1rem; padding:1.25rem;
                  background:var(--ivory-deep); border-radius:var(--r-md)">
        <?php foreach ([
          ['fa-truck-fast',    'Free shipping over ₱1,500'],
          ['fa-rotate-left',   '14-day easy returns'],
          ['fa-shield-halved', 'Dermatologist tested'],
        ] as [$icon, $label]): ?>
          <div style="display:flex;align-items:center;gap:.5rem;font-size:.75rem;color:var(--ink-soft)">
            <i class="fas <?= $icon ?>" style="color:var(--rose)"></i> <?= $label ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php
  $hasIngredients = in_array('ingredients', $productCols);
  $hasUsage       = in_array('usage_instructions', $productCols);

  $ingredientsVal = $hasIngredients ? ($product['ingredients'] ?? '') : '';
  $usageVal       = $hasUsage ? ($product['usage_instructions'] ?? '') : '';
  ?>

  <div class="product-tabs-wrapper">
    <div class="tabs-nav-bar">
      <button class="tab-trigger active" onclick="switchTab('details', this)">Description</button>
      <button class="tab-trigger" onclick="switchTab('ingredients', this)">Ingredients</button>
      <button class="tab-trigger" onclick="switchTab('usage', this)">How To Use</button>
    </div>
    
    <div class="tabs-content-container">
      <div id="tab-details" class="tab-panel active">
        <?php if (!empty($product['description'])): ?>
          <?= nl2br(e($product['description'])) ?>
        <?php else: ?>
          <p class="empty-tab-text">Premium product details coming soon. Contact us for more information.</p>
        <?php endif; ?>
      </div>

      <div id="tab-ingredients" class="tab-panel">
        <?php if (!empty($ingredientsVal)): ?>
          <div class="ingredients-highlight">
            <?= nl2br(e($ingredientsVal)) ?>
          </div>
        <?php else: ?>
          <div class="empty-tab-state">
            <i class="fas fa-flask"></i>
            <p>Detailed ingredient breakdown for <?= e($product['name']) ?> is being prepared. Our formulas are clean, effective, and dermatologically conscious.</p>
          </div>
        <?php endif; ?>
      </div>

      <div id="tab-usage" class="tab-panel">
        <?php if (!empty($usageVal)): ?>
          <div class="usage-steps-wrapper">
            <?= nl2br(e($usageVal)) ?>
          </div>
        <?php else: ?>
          <div class="empty-tab-state">
            <i class="fas fa-wand-magic-sparkles"></i>
            <p>Apply as directed. For best results, incorporate systematically into your daily Amaria Aesthetics skin ritual.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if (!empty($related)): ?>
    <div style="margin-top:4rem; padding-top:3rem; border-top:1px solid var(--mist)">
      <div class="section-header" style="text-align:left; margin-bottom:2rem">
        <span class="section-eyebrow">You May Also Like</span>
        <h2 style="font-size:1.8rem">Related Products</h2>
      </div>
      <div class="products-grid">
        <?php foreach ($related as $i => $r):
          $rImg = !empty($r['image']) ? productImageUrl($r['image']) : '';
          // Updated related product discount check
          $rDisc = $hasDiscountPrice && !empty($r['discount_price']) && (float)$r['discount_price'] < (float)$r['price'];
        ?>
          <article class="product-card reveal reveal-delay-<?= $i + 1 ?>">
            <div class="pc-img-wrap">
              <?php if ($rImg): ?>
                <img src="<?= e($rImg) ?>" alt="<?= e($r['name']) ?>" class="pc-img" loading="lazy">
              <?php else: ?>
                <div class="pc-placeholder"><i class="fas fa-spa"></i></div>
              <?php endif; ?>
              <div class="pc-actions">
                <button class="pc-add-btn" data-add-cart="<?= (int)$r['id'] ?>" data-qty="1">
                  <i class="fas fa-bag-shopping"></i> Quick Add
                </button>
              </div>
            </div>
            <div class="pc-body">
              <a href="<?= STORE_URL ?>/product.php?id=<?= (int)$r['id'] ?>">
                <h3 class="pc-name"><?= e($r['name']) ?></h3>
              </a>
              <div class="pc-footer">
                <span class="pc-price-current"><?= formatPrice($rDisc ? (float)$r['discount_price'] : (float)$r['price']) ?></span>
                <?php if ($rDisc): ?>
                  <span class="pc-price-old"><?= formatPrice((float)$r['price']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
function changeQty(delta) {
  const input = document.getElementById('productQty');
  input.value = Math.max(1, (parseInt(input.value)||1) + delta);
}

function updateProductImage(element) {
  const mainImg = document.getElementById('mainProductImg');
  if (mainImg && element.dataset.thumb) {
    mainImg.src = element.dataset.thumb;
    document.querySelectorAll('.product-gallery-thumb').forEach(thumb => {
      thumb.style.borderColor = 'transparent';
    });
    element.style.borderColor = 'var(--rose)';
  }
}

document.getElementById('addToCartBtn')?.addEventListener('click', async function(e) {
  e.preventDefault();
  e.stopPropagation();
  const qty = parseInt(document.getElementById('productQty').value) || 1;
  const pid = this.dataset.addCart;
  this.disabled = true;
  const orig = this.innerHTML;
  this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding…';
  try {
    const res  = await fetch(STORE_URL + '/api/cart.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: `action=add&product_id=${pid}&qty=${qty}`
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      const dot = document.getElementById('cartDot');
      if (dot) { dot.textContent = data.cart_count; dot.classList.add('visible'); }
      this.innerHTML = '<i class="fas fa-check"></i> Added!';
      setTimeout(() => { this.innerHTML = orig; this.disabled = false; }, 2200);
    } else {
      showToast(data.message, 'error');
      this.innerHTML = orig; this.disabled = false;
    }
  } catch { showToast('Something went wrong.','error'); this.innerHTML = orig; this.disabled = false; }
});

function switchTab(name, btn) {
  document.querySelectorAll('.tab-panel').forEach(panel => {
    panel.classList.remove('active');
  });
  const targetPanel = document.getElementById('tab-' + name);
  if(targetPanel) { targetPanel.classList.add('active'); }
  document.querySelectorAll('.tab-trigger').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}
</script>

<style>
.product-tabs-wrapper { margin-top: 4rem; border-top: 1px solid var(--mist); padding-top: 3rem; }
.tabs-nav-bar { display: flex; gap: 2.5rem; border-bottom: 1px solid var(--mist); margin-bottom: 2.5rem; }
.tab-trigger { background: none; border: none; padding: 0 0 1rem 0; font-family: var(--font-body); font-size: 0.95rem; font-weight: 500; color: var(--ink-soft); cursor: pointer; position: relative; letter-spacing: 0.05em; transition: color 0.3s ease; }
.tab-trigger:hover, .tab-trigger.active { color: var(--mocha); }
.tab-trigger.active::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 100%; height: 2px; background-color: var(--rose); }
.tab-panel { display: none; font-size: .92rem; line-height: 1.8; color: var(--ink-soft); animation: fadeInTab 0.4s ease forwards; }
.tab-panel.active { display: block; }
.empty-tab-state { text-align: center; padding: 3rem 1.5rem; background: var(--ivory-deep); border-radius: var(--r-md); max-width: 600px; margin: 0 auto; }
.empty-tab-state i { font-size: 2rem; color: var(--rose-mid); opacity: 0.4; margin-bottom: 1rem; }
.empty-tab-state p { font-size: 0.88rem; color: var(--ink-soft); margin: 0; }
@keyframes fadeInTab { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
@media(max-width:768px){
  .container > div[style*="grid-template-columns:1fr 1fr"]{grid-template-columns:1fr!important; gap:2rem!important}
  .tabs-nav-bar { gap: 1.25rem; }
  .tab-trigger { font-size: 0.85rem; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>