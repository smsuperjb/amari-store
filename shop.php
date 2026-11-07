<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$pageTitle = 'Shop — ' . SITE_NAME;
$pageDesc  = 'Browse our full collection of premium aesthetics & skincare products.';

// ── DETECT COLUMNS ────────────────────────────────────────────
$productCols      = getColumns($pdo, 'products');
$categoryCols     = getColumns($pdo, 'categories');
$hasStatus        = in_array('status',         $productCols);
$hasDiscountPrice = in_array('discount_price', $productCols);
$hasCategoryId    = in_array('category_id',    $productCols);
$hasImage         = in_array('image',          $productCols);
$hasDescription   = in_array('description',    $productCols);

// Detect stock inventory column variants
$stockCol = false;
if (in_array('stock', $productCols)) {
    $stockCol = 'stock';
} elseif (in_array('quantity', $productCols)) {
    $stockCol = 'quantity';
}

// ── FILTERS FROM GET ──────────────────────────────────────────
$categoryId  = isset($_GET['category'])  ? (int)$_GET['category']  : 0;
$minPrice    = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice    = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sort        = $_GET['sort']  ?? 'newest';
$search      = trim($_GET['q'] ?? '');
$view        = in_array($_GET['view'] ?? '', ['list','grid']) ? $_GET['view'] : 'grid';
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 12;
$offset      = ($page - 1) * $perPage;

// ── BUILD QUERY ───────────────────────────────────────────────
$where  = $hasStatus ? ["p.status = 'active'"] : [];
$params = [];

if ($categoryId && $hasCategoryId) { $where[] = 'p.category_id = ?'; $params[] = $categoryId; }
if ($search)     { $where[] = 'p.name LIKE ?'; $params[] = '%' . $search . '%'; }
if ($minPrice > 0) { $where[] = 'p.price >= ?'; $params[] = $minPrice; }
if ($maxPrice > 0) { $where[] = 'p.price <= ?'; $params[] = $maxPrice; }

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderSQL = match($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name_asc'   => 'p.name ASC',
    default      => 'p.id DESC',
};

$joinSQL = ($hasCategoryId && !empty($categoryCols))
    ? 'LEFT JOIN categories c ON p.category_id = c.id'
    : '';
$catSel = ($hasCategoryId && !empty($categoryCols)) ? ', c.name AS category_name' : '';

// Count total
try {
    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $joinSQL $whereSQL");
    $cntStmt->execute($params);
    $totalProducts = (int)$cntStmt->fetchColumn();
} catch (Exception $e) { $totalProducts = 0; }

$totalPages = max(1, ceil($totalProducts / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Fetch products
$products = [];
try {
    $sel = 'p.id, p.name, p.price' .
           ($hasImage         ? ', p.image'          : '') .
           ($hasDiscountPrice ? ', p.discount_price' : '') .
           ($hasCategoryId    ? ', p.category_id'    : '') .
           ($hasDescription   ? ', p.description'    : '') .
           ($stockCol         ? ", p.{$stockCol}"    : '') .
           $catSel;
    $stmt = $pdo->prepare("SELECT $sel FROM products p $joinSQL $whereSQL ORDER BY $orderSQL LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $products = []; }

// Fetch categories for filter
$categories = [];
try {
    if (!empty($categoryCols)) {
        $categories = $pdo->query("SELECT c.id, c.name" .
            ($hasCategoryId ? ", COUNT(p.id) AS cnt FROM categories c LEFT JOIN products p ON p.category_id = c.id" . ($hasStatus ? " AND p.status='active'" : '') . " GROUP BY c.id" : " FROM categories c") .
            " ORDER BY c.name"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { $categories = []; }

// Price range for slider
try {
    $priceRange = $pdo->query("SELECT MIN(price) AS mn, MAX(price) AS mx FROM products" . ($hasStatus ? " WHERE status='active'" : ''))->fetch(PDO::FETCH_ASSOC);
    $priceMin = (float)($priceRange['mn'] ?? 0);
    $priceMax = (float)($priceRange['mx'] ?? 10000);
} catch (Exception $e) { $priceMin = 0; $priceMax = 10000; }

include __DIR__ . '/includes/header.php';
?>

<div class="page-hero" style="padding:calc(var(--header-h) + 2.5rem) var(--gutter) 2.5rem; text-align:left">
  <div class="container" style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:1rem">
    <div>
      <div class="breadcrumb" style="margin-bottom:.75rem">
        <a href="<?= STORE_URL ?>/index.php">Home</a>
        <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
        <span>Shop</span>
        <?php if ($categoryId && !empty($categories)): ?>
          <?php $activeCat = array_filter($categories, fn($c) => $c['id'] == $categoryId); ?>
          <?php if ($activeCat): $activeCat = reset($activeCat); ?>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span><?= e($activeCat['name']) ?></span>
          <?php endif; ?>
        <?php endif; ?>
      </div>
      <h1 style="font-size:clamp(1.8rem,4vw,3rem); position:relative; z-index:1">
        <?= $search ? 'Results for "' . e($search) . '"' : ($categoryId && isset($activeCat) ? e($activeCat['name']) : 'All Products') ?>
      </h1>
    </div>
    <p style="color:var(--ink-soft); font-size:.85rem; position:relative; z-index:1">
      <?= $totalProducts ?> product<?= $totalProducts !== 1 ? 's' : '' ?> found
    </p>
  </div>
</div>

<div class="container section-pad-sm">
  <div style="display:grid; grid-template-columns:240px 1fr; gap:2.5rem; align-items:start">

    <aside class="shop-sidebar" id="shopSidebar">
      <form method="GET" id="filterForm">
        <?php if ($search): ?><input type="hidden" name="q" value="<?= e($search) ?>"><?php endif; ?>
        <input type="hidden" name="view" value="<?= e($view) ?>">

        <p class="filter-title">Filters</p>

        <?php if (!empty($categories)): ?>
        <div class="filter-group">
          <div class="filter-group-title">Category</div>
          <label class="filter-check">
            <input type="radio" name="category" value="0" <?= !$categoryId ? 'checked' : '' ?> onchange="this.form.submit()">
            All Categories
          </label>
          <?php foreach ($categories as $cat): ?>
            <label class="filter-check">
              <input type="radio" name="category" value="<?= (int)$cat['id'] ?>"
                     <?= $categoryId == $cat['id'] ? 'checked' : '' ?> onchange="this.form.submit()">
              <?= e($cat['name']) ?>
              <?php if (isset($cat['cnt'])): ?><span style="margin-left:auto;color:#aaa;font-size:.75rem">(<?= (int)$cat['cnt'] ?>)</span><?php endif; ?>
            </label>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="filter-group">
          <div class="filter-group-title">Price Range</div>
          <div style="display:flex; gap:.5rem; align-items:center; font-size:.8rem; color:var(--ink-soft); margin-bottom:.75rem">
            <span><?= formatPrice($priceMin) ?></span>
            <span style="flex:1; height:1px; background:var(--mist)"></span>
            <span><?= formatPrice($priceMax) ?></span>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:.5rem">
            <div>
              <label class="form-label" style="font-size:.65rem">Min (₱)</label>
              <input type="number" name="min_price" class="form-control" style="padding:.5rem .75rem; font-size:.82rem"
                     value="<?= $minPrice ?: '' ?>" min="0" max="<?= $priceMax ?>" placeholder="0">
            </div>
            <div>
              <label class="form-label" style="font-size:.65rem">Max (₱)</label>
              <input type="number" name="max_price" class="form-control" style="padding:.5rem .75rem; font-size:.82rem"
                     value="<?= $maxPrice ?: '' ?>" min="0" max="<?= $priceMax ?>" placeholder="Any">
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-sm" style="width:100%; margin-top:.75rem">Apply Filter</button>
        </div>

        <div class="filter-group">
          <div class="filter-group-title">Sort By</div>
          <?php $sorts = ['newest'=>'Newest First','price_asc'=>'Price: Low to High','price_desc'=>'Price: High to Low','name_asc'=>'Name A–Z']; ?>
          <?php foreach ($sorts as $val => $label): ?>
            <label class="filter-check">
              <input type="radio" name="sort" value="<?= $val ?>"
                     <?= $sort === $val ? 'checked' : '' ?> onchange="this.form.submit()">
              <?= $label ?>
            </label>
          <?php endforeach; ?>
        </div>

        <a href="<?= STORE_URL ?>/shop.php" class="btn btn-outline-rose btn-sm" style="width:100%; text-align:center; display:block">
          <i class="fas fa-xmark"></i> Clear All Filters
        </a>
      </form>
    </aside>

    <div>
      <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap">
        <p style="font-size:.82rem; color:var(--ink-soft)">
          Showing <?= $totalProducts > 0 ? $offset + 1 : 0 ?>–<?= min($offset + $perPage, $totalProducts) ?> of <?= $totalProducts ?>
        </p>
        <div style="display:flex; gap:.5rem; align-items:center">
          <form method="GET" style="display:flex; gap:.35rem">
            <?php if ($categoryId): ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
            <input type="text" name="q" value="<?= e($search) ?>"
                   placeholder="Search products…"
                   style="padding:.45rem .85rem; border:1.5px solid var(--mist); border-radius:var(--r-sm); font-size:.8rem; background:var(--ivory-warm); outline:none; width:180px">
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
          </form>
          <div style="display:flex; gap:.3rem">
            <a href="?<?= http_build_query(array_merge($_GET, ['view'=>'grid'])) ?>"
               class="view-btn <?= $view==='grid'?'active':'' ?>" title="Grid view">
              <i class="fas fa-grip"></i>
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['view'=>'list'])) ?>"
               class="view-btn <?= $view==='list'?'active':'' ?>" title="List view">
              <i class="fas fa-list"></i>
            </a>
          </div>
        </div>
      </div>

      <?php if (!empty($products)): ?>
        <div class="products-grid <?= $view==='list' ? 'products-list' : '' ?>"
             style="<?= $view==='list' ? 'grid-template-columns:1fr' : '' ?>">
          <?php foreach ($products as $i => $p):
            $imgUrl      = !empty($p['image']) ? productImageUrl($p['image']) : '';
            $hasDiscount = $hasDiscountPrice && !empty($p['discount_price']) && (float)$p['discount_price'] > 0 && (float)$p['discount_price'] < (float)$p['price'];
            
            // Core Stock / Inventory Verification
            $isSoldOut = ($stockCol && isset($p[$stockCol]) && (int)$p[$stockCol] <= 0);
          ?>
            <article class="product-card reveal reveal-delay-<?= ($i % 4) + 1 ?>"
                     <?= $view==='list' ? 'style="grid-template-columns:160px 1fr; display:grid"' : '' ?>>
              
              <div class="pc-img-wrap" <?= $view==='list' ? 'style="aspect-ratio:unset; height:100%; min-height:160px"' : '' ?>>
                <?php if ($imgUrl): ?>
                  <img src="<?= e($imgUrl) ?>" alt="<?= e($p['name']) ?>" class="pc-img" loading="lazy" style="<?= $isSoldOut ? 'filter: grayscale(80%) opacity(70%);' : '' ?>">
                <?php else: ?>
                  <div class="pc-placeholder"><i class="fas fa-spa"></i></div>
                <?php endif; ?>
                
                <div class="pc-badges">
                  <?php if ($isSoldOut): ?>
                    <span class="pc-badge pc-badge-soldout" style="background: var(--ink-dark, #222); color: #fff;">Sold Out</span>
                  <?php elseif ($hasDiscount): ?>
                    <span class="pc-badge pc-badge-sale">
                      <?= round((1 - (float)$p['discount_price'] / (float)$p['price']) * 100) ?>% Off
                    </span>
                  <?php else: ?>
                    <span class="pc-badge pc-badge-new">New</span>
                  <?php endif; ?>
                </div>
                
                <button class="pc-wishlist" aria-label="Wishlist"><i class="far fa-heart"></i></button>
                
                <?php if (!$isSoldOut): ?>
                  <div class="pc-actions">
                    <button class="pc-add-btn" data-add-cart="<?= (int)$p['id'] ?>" data-qty="1">
                      <i class="fas fa-bag-shopping"></i> Quick Add
                    </button>
                  </div>
                <?php endif; ?>
              </div>

              <div class="pc-body" <?= $view==='list' ? 'style="display:flex;flex-direction:column;justify-content:center"' : '' ?>>
                <?php if (!empty($p['category_name'])): ?>
                  <div class="pc-category"><?= e($p['category_name']) ?></div>
                <?php endif; ?>
                <a href="<?= STORE_URL ?>/product.php?id=<?= (int)$p['id'] ?>">
                  <h3 class="pc-name" style="<?= $isSoldOut ? 'color:var(--ink-soft);' : '' ?>"><?= e($p['name']) ?></h3>
                </a>
                
                <?php if ($view === 'list' && $hasDescription && !empty($p['description'])): ?>
                  <p style="font-size:.82rem; color:var(--ink-soft); margin-bottom:.75rem; line-height:1.6">
                    <?= e(mb_strimwidth($p['description'], 0, 120, '…')) ?>
                  </p>
                <?php endif; ?>
                
                <div class="pc-footer">
                  <div>
                    <span class="pc-price-current"><?= formatPrice($hasDiscount ? (float)$p['discount_price'] : (float)$p['price']) ?></span>
                    <?php if ($hasDiscount): ?>
                      <span class="pc-price-old"><?= formatPrice((float)$p['price']) ?></span>
                    <?php endif; ?>
                  </div>
                  
                  <?php if ($view === 'list'): ?>
                    <?php if ($isSoldOut): ?>
                      <button class="btn btn-sm disabled" style="background:var(--mist); color:var(--ink-soft); border:none;" disabled>
                        Out of Stock
                      </button>
                    <?php else: ?>
                      <button class="btn btn-primary btn-sm" data-add-cart="<?= (int)$p['id'] ?>" data-qty="1">
                        <i class="fas fa-bag-shopping"></i> Add to Cart
                      </button>
                    <?php endif; ?>
                  <?php else: ?>
                    <div class="pc-stars">★★★★★</div>
                  <?php endif; ?>
                </div>
              </div>

            </article>
          <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
          <nav class="pagination-wrap" aria-label="Product Selection Pagination">
            <div class="pagination-container">
              
              <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-btn page-arrow" aria-label="Previous Page">
                  <i class="fas fa-chevron-left"></i> Prev
                </a>
              <?php else: ?>
                <span class="page-btn page-arrow disabled" aria-disabled="true">
                  <i class="fas fa-chevron-left"></i> Prev
                </span>
              <?php endif; ?>

              <div class="page-numbers-group">
                <?php 
                $range = 2;
                $start = max(1, $page - $range);
                $end   = min($totalPages, $page + $range);

                if ($start > 1): ?>
                  <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="page-btn">1</a>
                  <?php if ($start > 2): ?>
                    <span class="page-dots">...</span>
                  <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                  <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                     class="page-btn <?= $i === $page ? 'active' : '' ?>" 
                     <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($end < $totalPages): ?>
                  <?php if ($end < $totalPages - 1): ?>
                    <span class="page-dots">...</span>
                  <?php endif; ?>
                  <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="page-btn"><?= $totalPages ?></a>
                <?php endif; ?>
              </div>

              <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-btn page-arrow" aria-label="Next Page">
                  Next <i class="fas fa-chevron-right"></i>
                </a>
              <?php else: ?>
                <span class="page-btn page-arrow disabled" aria-disabled="true">
                  Next <i class="fas fa-chevron-right"></i>
                </span>
              <?php endif; ?>

            </div>
          </nav>
        <?php endif; ?>

      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-spa"></i>
          <h3>No products found</h3>
          <p><?= $search ? 'Try a different search term or' : 'Try adjusting your filters or' ?> browse all products.</p>
          <a href="<?= STORE_URL ?>/shop.php" class="btn btn-primary">View All Products</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>