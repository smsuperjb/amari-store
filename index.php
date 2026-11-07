<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$pageTitle = 'Amari Aesthetics';
$pageDesc  = 'Premium aesthetics & skincare — beauty rooted in science, defined by elegance.';

// ── DETECT ACTUAL TABLE COLUMNS ──────────────────────────────
$productCols  = getColumns($pdo, 'products');
$categoryCols = getColumns($pdo, 'categories');

$hasStatus        = in_array('status',         $productCols);
$hasDiscountPrice = in_array('discount_price', $productCols); // Preserved
$hasCategoryId    = in_array('category_id',    $productCols);
$hasImage         = in_array('image',          $productCols);
$hasCreatedAt     = in_array('created_at',     $productCols);
$hasStock         = in_array('stock',          $productCols); // Added to securely detect stock column
$hasProductsTable = !empty($productCols);

$featuredProducts = [];
$newArrivals      = [];
$categories       = [];
$banners          = [];

// ── FETCH BANNERS ─────────────────────────────────────────────
// desktop = 1 → show on website store
// status  = active → live
try {
    $banners = $pdo->query(
        "SELECT id, title, image, image_url, status
         FROM banners
         WHERE status = 'active'
           AND desktop = 1
         ORDER BY id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $banners = [];
}

// ── FETCH PRODUCTS ────────────────────────────────────────────
if ($hasProductsTable) {
    $sel = ['p.id', 'p.name', 'p.price'];
    if ($hasImage)         $sel[] = 'p.image';
    if ($hasDiscountPrice) $sel[] = 'p.discount_price'; // Preserved
    if ($hasCreatedAt)     $sel[] = 'p.created_at';
    if ($hasCategoryId)    $sel[] = 'p.category_id';
    if ($hasStock)         $sel[] = 'p.stock'; // Safely pull stock if column exists

    $joinPart  = '';
    $catSelect = '';
    if ($hasCategoryId && !empty($categoryCols)) {
        $catNameCol = in_array('name', $categoryCols) ? 'name' : ($categoryCols[1] ?? null);
        if ($catNameCol) {
            $joinPart  = "LEFT JOIN categories c ON p.category_id = c.id";
            $catSelect = ", c.`$catNameCol` AS category_name";
        }
    }
    $wherePart = $hasStatus ? "WHERE p.status = 'active'" : '';

    try {
        $featuredProducts = $pdo->query(
            "SELECT " . implode(', ', $sel) . "$catSelect
             FROM products p $joinPart
             $wherePart
             ORDER BY p.id DESC
             LIMIT 8"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $featuredProducts = []; }

    try {
        $newArrivals = $pdo->query(
            "SELECT " . implode(', ', $sel) . "$catSelect
             FROM products p $joinPart
             $wherePart
             ORDER BY p.id DESC
             LIMIT 4"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $newArrivals = []; }
}

// ── FETCH CATEGORIES ──────────────────────────────────────────
if (!empty($categoryCols)) {
    try {
        $cntJoin = $hasCategoryId
            ? "LEFT JOIN products p ON p.category_id = c.id" . ($hasStatus ? " AND p.status='active'" : '')
            : '';
        $cntSel = $hasCategoryId ? ", COUNT(p.id) AS product_count" : ", 0 AS product_count";
        $grpBy  = $hasCategoryId ? "GROUP BY c.id" : '';
        $categories = $pdo->query(
            "SELECT c.* $cntSel
             FROM categories c $cntJoin
             $grpBy
             ORDER BY c.sort_order ASC, product_count DESC
             LIMIT 6"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $categories = []; }
}

include __DIR__ . '/includes/header.php';
?>

<section class="hero" aria-label="Hero">
  <div class="hero-left">
    <span class="hero-eyebrow">
      <span class="hero-eyebrow-line"></span>
      New Collection 2026
    </span>
    <h1 class="hero-heading">
      <span>Reveal Your</span>
      <em>Most Radiant</em>
      <strong>Skin</strong>
    </h1>
    <p class="hero-desc">
      Science-backed formulations crafted for the modern Filipino skin.
      Clinically tested. Elegantly designed. Beautifully effective.
    </p>
    <div class="hero-ctas">
      <a href="<?= STORE_URL ?>/shop" class="btn btn-primary btn-lg">
        Shop the Collection <i class="fas fa-arrow-right"></i>
      </a>
      <a href="<?= STORE_URL ?>/about" class="btn btn-ghost btn-lg">Our Story</a>
    </div>
    <div class="hero-trust">
      <div class="trust-item">
        <span class="trust-num">5,000+</span>
        <span class="trust-label">Happy Clients</span>
      </div>
      <div class="trust-item">
        <span class="trust-num"><?= max(count($featuredProducts), 1) ?>+</span>
        <span class="trust-label">Products</span>
      </div>
      <div class="trust-item">
        <span class="trust-num">100%</span>
        <span class="trust-label">Derma-Tested</span>
      </div>
    </div>
  </div>

  <div class="hero-right">
    <?php
      $heroImg   = '';
      $heroTitle = '';
      if (!empty($banners)) {
          $heroImg   = bannerImageUrl($banners[0]);
          $heroTitle = $banners[0]['title'] ?? '';
      }
    ?>

    <?php if ($heroImg): ?>
      <?php if (count($banners) === 1): ?>
        <div class="hero-img-wrap">
          <img src="<?= e($heroImg) ?>"
               alt="<?= e($heroTitle) ?>"
               class="hero-banner-img"
               loading="eager">
        </div>
      <?php else: ?>
        <div class="hero-slider" id="heroSlider">
          <?php foreach ($banners as $i => $b):
            $bUrl = bannerImageUrl($b);
            if (!$bUrl) continue;
          ?>
            <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>">
              <img src="<?= e($bUrl) ?>"
                   alt="<?= e($b['title'] ?? '') ?>"
                   loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
            </div>
          <?php endforeach; ?>

          <?php if (count($banners) > 1): ?>
            <div class="hero-slider-dots">
              <?php foreach ($banners as $i => $b): ?>
                <button class="hsd <?= $i === 0 ? 'active' : '' ?>"
                        onclick="heroGoSlide(<?= $i ?>)"
                        aria-label="Slide <?= $i + 1 ?>"></button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="hero-visual-placeholder">
        <i class="fas fa-spa"></i>
        <p>Upload a banner in admin<br>and set desktop = 1</p>
      </div>
    <?php endif; ?>

    <div class="hero-float-card">
      <div class="float-icon"><i class="fas fa-star"></i></div>
      <div class="float-text">
        <div class="float-title">Top Rated This Month</div>
        <div class="float-sub">
          <?= !empty($featuredProducts[0]['name'])
              ? e(mb_strimwidth($featuredProducts[0]['name'], 0, 28, '…'))
              : 'Premium Glow Serum' ?>
        </div>
      </div>
    </div>

    <div class="hero-badge" aria-hidden="true">
      <span>Free</span><span>Shipping</span><span>₱1,500+</span>
    </div>
  </div>

  <div class="hero-scroll" aria-hidden="true">
    <i class="fas fa-chevron-down"></i> Scroll
  </div>
</section>


<?php if (count($banners) > 1): ?>
<section class="banner-strip-section">
  <div class="banner-strip-track" id="bannerStrip">
    <?php foreach ($banners as $i => $b):
      $bUrl = bannerImageUrl($b);
      if (!$bUrl) continue;
    ?>
      <div class="banner-strip-slide <?= $i === 0 ? 'active' : '' ?>">
        <img src="<?= e($bUrl) ?>"
             alt="<?= e($b['title'] ?? '') ?>"
             loading="lazy">
        <?php if (!empty($b['title'])): ?>
          <div class="banner-strip-caption"><?= e($b['title']) ?></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <button class="bstrip-btn bstrip-prev" onclick="stripSlide(-1)" aria-label="Previous">
      <i class="fas fa-chevron-left"></i>
    </button>
    <button class="bstrip-btn bstrip-next" onclick="stripSlide(1)" aria-label="Next">
      <i class="fas fa-chevron-right"></i>
    </button>

    <div class="bstrip-dots">
      <?php foreach ($banners as $i => $b): ?>
        <button class="bsd <?= $i === 0 ? 'active' : '' ?>"
                onclick="stripGoSlide(<?= $i ?>)"
                aria-label="Go to slide <?= $i + 1 ?>"></button>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>


<div class="ticker" aria-hidden="true">
  <div class="ticker-track">
    <?php $tickers = [
      'Free shipping on orders ₱1,500+', 'Dermatologist Tested',
      'Natural & Effective Ingredients',  'Made for Filipino Skin',
      'Clinically Proven Results',        'Cruelty-Free & Safe',
      'Free shipping on orders ₱1,500+', 'Dermatologist Tested',
      'Natural & Effective Ingredients',  'Made for Filipino Skin',
      'Clinically Proven Results',        'Cruelty-Free & Safe',
    ];
    foreach ($tickers as $t): ?>
      <span class="ticker-item">
        <i class="fas fa-diamond ticker-dot"></i>
        <?= e($t) ?>
      </span>
    <?php endforeach; ?>
  </div>
</div>


<div class="feature-strip">
  <?php $features = [
    ['fa-truck-fast',    'Free Nationwide Shipping', 'On orders over ₱1,500'],
    ['fa-shield-halved', 'Derma-Tested & Safe',      'Clinically validated formulas'],
    ['fa-rotate-left',   'Easy 14-Day Returns',      'Hassle-free, no questions'],
    ['fa-headset',       'Expert Skin Support',      'Mon–Sat, 9am–6pm PHT'],
  ];
  foreach ($features as $i => [$icon, $title, $sub]): ?>
    <div class="feature-item reveal reveal-delay-<?= $i ?>">
      <div class="feature-icon"><i class="fas <?= $icon ?>"></i></div>
      <div class="feature-text">
        <strong><?= e($title) ?></strong>
        <span><?= e($sub) ?></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>


<?php if (!empty($categories)): ?>
<section class="categories-section section-pad">
  <div class="container">
    <div class="section-header reveal">
      <span class="section-eyebrow">Browse By</span>
      <h2>Shop by Category</h2>
      <p>Discover curated collections designed for every step of your ritual.</p>
    </div>
    <div class="categories-scroll">
      <?php foreach ($categories as $i => $cat):
        $catName = $cat['name'] ?? 'Category';
        $catImg  = categoryImageUrl($cat);
      ?>
        <a href="<?= STORE_URL ?>/shop?category=<?= (int)$cat['id'] ?>"
           class="cat-card reveal reveal-delay-<?= min($i + 1, 4) ?>">

          <?php if ($catImg): ?>
            <img src="<?= e($catImg) ?>"
                 alt="<?= e($catName) ?>"
                 class="cat-img"
                 loading="lazy">
          <?php else: ?>
            <div class="cat-placeholder"><i class="fas fa-spa"></i></div>
          <?php endif; ?>

          <div class="cat-overlay"></div>
          <div class="cat-info">
            <span class="cat-label"><?= e($catName) ?></span>
            <span class="cat-count"><?= (int)($cat['product_count'] ?? 0) ?> products</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>


<section class="section-pad" style="background:var(--ivory-deep)">
  <div class="container">
    <div class="section-header reveal">
      <span class="section-eyebrow">Curated For You</span>
      <h2>Featured Products</h2>
      <p>Our most-loved formulations, trusted by thousands across the Philippines.</p>
    </div>

    <?php if (!empty($featuredProducts)): ?>
      <div class="products-grid">
        <?php foreach ($featuredProducts as $i => $p):
          $imgUrl      = !empty($p['image']) ? productImageUrl($p['image']) : '';
          $hasDiscount = $hasDiscountPrice && !empty($p['discount_price']) && (float)$p['discount_price'] > 0 && (float)$p['discount_price'] < (float)$p['price'];
          
          // Added: Evaluates if stock is explicitly empty
          $isOutOfStock = $hasStock && isset($p['stock']) && (int)$p['stock'] <= 0;
        ?>
          <article class="product-card reveal reveal-delay-<?= ($i % 4) + 1 ?> <?= $isOutOfStock ? 'pc-out-of-stock' : '' ?>">
            <div class="pc-img-wrap">
              <?php if ($imgUrl): ?>
                <img src="<?= e($imgUrl) ?>"
                     alt="<?= e($p['name']) ?>"
                     class="pc-img" loading="lazy">
              <?php else: ?>
                <div class="pc-placeholder"><i class="fas fa-spa"></i></div>
              <?php endif; ?>

              <div class="pc-badges">
                <?php if ($isOutOfStock): ?>
                  <!-- Added: Out of Stock Badge prioritized -->
                  <span class="pc-badge pc-badge-soldout" style="background:#555;color:#fff">Sold Out</span>
                <?php endif; ?>
                
                <?php if ($hasDiscount): ?>
                  <span class="pc-badge pc-badge-sale">
                    <?= round((1 - (float)$p['discount_price'] / (float)$p['price']) * 100) ?>% Off
                  </span>
                <?php else: ?>
                  <span class="pc-badge pc-badge-new">New</span>
                <?php endif; ?>
              </div>

              <button class="pc-wishlist" aria-label="Add to wishlist">
                <i class="far fa-heart"></i>
              </button>

              <div class="pc-actions">
                <?php if ($isOutOfStock): ?>
                  <button class="pc-add-btn" disabled style="opacity:0.6;cursor:not-allowed">
                    <i class="fas fa-ban"></i> Out of Stock
                  </button>
                <?php else: ?>
                  <button class="pc-add-btn"
                          data-add-cart="<?= (int)$p['id'] ?>"
                          data-qty="1">
                    <i class="fas fa-bag-shopping"></i> Quick Add
                  </button>
                <?php endif; ?>
              </div>
            </div>

            <div class="pc-body">
              <?php if (!empty($p['category_name'])): ?>
                <div class="pc-category"><?= e($p['category_name']) ?></div>
              <?php endif; ?>
              <a href="<?= STORE_URL ?>/product?id=<?= (int)$p['id'] ?>">
                <h3 class="pc-name"><?= e($p['name']) ?></h3>
              </a>
              <div class="pc-footer">
                <div>
                  <span class="pc-price-current"><?= formatPrice($hasDiscount ? (float)$p['discount_price'] : (float)$p['price']) ?></span>
                  <?php if ($hasDiscount): ?>
                    <span class="pc-price-old"><?= formatPrice((float)$p['price']) ?></span>
                  <?php endif; ?>
                </div>
                <div class="pc-stars" aria-label="5 stars">★★★★★</div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <div style="text-align:center;margin-top:3rem" class="reveal">
        <a href="<?= STORE_URL ?>/shop" class="btn btn-outline btn-lg">
          View All Products <i class="fas fa-arrow-right"></i>
        </a>
      </div>

    <?php else: ?>
      <div style="text-align:center;padding:4rem 1rem;color:var(--ink-soft)">
        <i class="fas fa-spa"
           style="font-size:3rem;color:var(--blush);margin-bottom:1rem;display:block"></i>
        <h3 style="margin-bottom:.5rem">Products coming soon</h3>
        <p style="font-size:.9rem">
          Add products in your admin dashboard to see them here.
        </p>
      </div>
    <?php endif; ?>
  </div>
</section>


<section class="story-section reveal">
  <div class="story-visual">
    <div style="width:100%;height:100%;min-height:500px;
                background:linear-gradient(160deg,var(--blush) 0%,var(--gold-pale) 60%,var(--ivory-deep) 100%);
                display:flex;align-items:center;justify-content:center;
                flex-direction:column;gap:1rem;color:var(--rose-mid)">
      <i class="fas fa-leaf" style="font-size:4rem;opacity:.25"></i>
      <p style="font-family:var(--font-display);font-size:1.1rem;font-style:italic;opacity:.4">
        Brand image here
      </p>
    </div>
    <div class="story-visual-overlay"></div>
  </div>
  <div class="story-content">
    <span class="section-eyebrow">Our Philosophy</span>
    <blockquote class="story-quote">
      "True beauty isn't covered — it's uncovered, nurtured, and revealed."
    </blockquote>
    <p class="story-body">
      Amari Aesthetics was born from a simple belief: every Filipino deserves access to
      premium, science-backed skincare without compromise. Our formulations are crafted
      with ingredients validated by dermatologists and tested on diverse skin tones across
      the Philippines.
    </p>
    <div style="margin-top:2.5rem;display:flex;gap:1rem;flex-wrap:wrap">
      <a href="<?= STORE_URL ?>/about" class="btn btn-primary">Read Our Story</a>
      <a href="<?= STORE_URL ?>/shop"  class="btn btn-outline-rose">Shop Now</a>
    </div>
  </div>
</section>


<?php if (!empty($newArrivals)): ?>
<section class="section-pad">
  <div class="container">
    <div class="section-header reveal">
      <span class="section-eyebrow">Just Landed</span>
      <h2>New Arrivals</h2>
      <p>Fresh formulations, just added to our collection.</p>
    </div>
    <div class="products-grid">
      <?php foreach ($newArrivals as $i => $p):
        $imgUrl      = !empty($p['image']) ? productImageUrl($p['image']) : '';
        $hasDiscount = $hasDiscountPrice && !empty($p['discount_price']) && (float)$p['discount_price'] > 0 && (float)$p['discount_price'] < (float)$p['price'];
        
        // Added: Evaluates if stock is explicitly empty
        $isOutOfStock = $hasStock && isset($p['stock']) && (int)$p['stock'] <= 0;
      ?>
        <article class="product-card reveal reveal-delay-<?= $i + 1 ?> <?= $isOutOfStock ? 'pc-out-of-stock' : '' ?>">
          <div class="pc-img-wrap">
            <?php if ($imgUrl): ?>
              <img src="<?= e($imgUrl) ?>"
                   alt="<?= e($p['name']) ?>"
                   class="pc-img" loading="lazy">
            <?php else: ?>
              <div class="pc-placeholder"><i class="fas fa-spa"></i></div>
            <?php endif; ?>
            
            <div class="pc-badges">
              <?php if ($isOutOfStock): ?>
                <!-- Added: Out of Stock Badge prioritized -->
                <span class="pc-badge pc-badge-soldout" style="background:#555;color:#fff">Sold Out</span>
              <?php endif; ?>

              <?php if ($hasDiscount): ?>
                <span class="pc-badge pc-badge-sale">
                  <?= round((1 - (float)$p['discount_price'] / (float)$p['price']) * 100) ?>% Off
                </span>
              <?php else: ?>
                <span class="pc-badge pc-badge-new">New</span>
              <?php endif; ?>
            </div>
            
            <button class="pc-wishlist" aria-label="Add to wishlist">
              <i class="far fa-heart"></i>
            </button>
            
            <div class="pc-actions">
              <?php if ($isOutOfStock): ?>
                <button class="pc-add-btn" disabled style="opacity:0.6;cursor:not-allowed">
                  <i class="fas fa-ban"></i> Out of Stock
                </button>
              <?php else: ?>
                <button class="pc-add-btn"
                        data-add-cart="<?= (int)$p['id'] ?>"
                        data-qty="1">
                  <i class="fas fa-bag-shopping"></i> Quick Add
                </button>
              <?php endif; ?>
            </div>
          </div>
          <div class="pc-body">
            <?php if (!empty($p['category_name'])): ?>
              <div class="pc-category"><?= e($p['category_name']) ?></div>
            <?php endif; ?>
            <a href="<?= STORE_URL ?>/product?id=<?= (int)$p['id'] ?>">
              <h3 class="pc-name"><?= e($p['name']) ?></h3>
            </a>
            <div class="pc-footer">
              <div>
                <span class="pc-price-current"><?= formatPrice($hasDiscount ? (float)$p['discount_price'] : (float)$p['price']) ?></span>
                <?php if ($hasDiscount): ?>
                  <span class="pc-price-old"><?= formatPrice((float)$p['price']) ?></span>
                <?php endif; ?>
              </div>
              <div class="pc-stars" aria-label="5 stars">★★★★★</div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>


<section class="testimonials-section section-pad">
  <div class="container">
    <div class="section-header reveal">
      <span class="section-eyebrow">Real Results</span>
      <h2>What Our Clients Say</h2>
      <p>Thousands of Filipinos trust Amari for their daily rituals.</p>
    </div>
    <div class="testimonials-grid">
      <?php $testimonials = [
        ['name' => 'Maria Santos',  'initial' => 'M', 'location' => 'Manila',
         'text'  => "I've tried so many brightening serums but nothing worked until Amari. My dark spots are noticeably lighter in just 3 weeks. Sobrang ganda ng results!"],
        ['name' => 'Jasmine Reyes', 'initial' => 'J', 'location' => 'Cebu',
         'text'  => "The packaging is so luxurious and the products smell amazing. More importantly they actually work. My skin has never looked this hydrated and glowy."],
        ['name' => 'Ana Cruz',      'initial' => 'A', 'location' => 'Davao',
         'text'  => "As someone with sensitive skin I was nervous to try new products. But Amari is so gentle yet effective. Dermatologist recommended and I can see why!"],
      ];
      foreach ($testimonials as $i => $t): ?>
        <div class="testi-card reveal reveal-delay-<?= $i + 1 ?>">
          <div class="testi-stars" aria-label="5 stars">★★★★★</div>
          <p class="testi-text">"<?= e($t['text']) ?>"</p>
          <div class="testi-author">
            <div class="testi-avatar"><?= $t['initial'] ?></div>
            <div>
              <div class="testi-name"><?= e($t['name']) ?></div>
              <div class="testi-verified">
                <i class="fas fa-circle-check"
                   style="color:var(--rose);font-size:.65rem"></i>
                Verified · <?= e($t['location']) ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<section class="section-pad"
         style="background:linear-gradient(135deg,var(--mocha) 0%,var(--rose-deep) 100%);
                text-align:center">
  <div class="container">
    <div class="reveal">
      <span class="section-eyebrow" style="color:var(--gold-light)">Limited Time</span>
      <h2 style="color:#fff;margin-block:.75rem 1rem">Get 10% Off Your First Order</h2>
      <p style="color:rgba(255,255,255,.7);margin:0 auto 2.5rem;
                max-width:440px;font-size:.95rem">
        Sign up and receive an exclusive welcome discount plus early access to new launches.
      </p>
      <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
        <a href="<?= STORE_URL ?>/signup" class="btn btn-ghost btn-lg">
          Create Account
        </a>
        <a href="<?= STORE_URL ?>/shop"
           class="btn btn-lg"
           style="background:transparent;border-color:rgba(255,255,255,.3);color:#fff">
          Browse Products
        </a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<style>
/* ── Hero image wrap ── */
.hero-img-wrap {
    width: 100%; height: 100%; border-radius: 24px; overflow: hidden;
}
.hero-banner-img {
    width: 100%; height: 100%; object-fit: cover; display: block;
}

/* ── Hero inline slider ── */
.hero-slider {
    width: 100%; height: 100%; border-radius: 24px; overflow: hidden;
    position: relative;
}
.hero-slide {
    position: absolute; inset: 0; opacity: 0; transition: opacity .6s ease;
}
.hero-slide.active { opacity: 1; }
.hero-slide img { width: 100%; height: 100%; object-fit: cover; }
.hero-slider-dots {
    position: absolute; bottom: 14px; left: 50%; transform: translateX(-50%);
    display: flex; gap: 6px; z-index: 5;
}
.hsd {
    width: 8px; height: 8px; border-radius: 50%;
    background: rgba(255,255,255,.5); border: none; cursor: pointer;
    transition: background .2s, width .2s;
}
.hsd.active { background: #fff; width: 20px; border-radius: 4px; }

/* ── Banner strip (full-width secondary slider) ── */
.banner-strip-section { width: 100%; overflow: hidden; }
.banner-strip-track {
    position: relative; width: 100%; aspect-ratio: 16/5;
    background: var(--ivory-deep);
}
.banner-strip-slide {
    position: absolute; inset: 0; opacity: 0; transition: opacity .5s ease;
}
.banner-strip-slide.active { opacity: 1; }
.banner-strip-slide img { width: 100%; height: 100%; object-fit: cover; }
.banner-strip-caption {
    position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%);
    background: rgba(0,0,0,.45); color: #fff;
    padding: 8px 20px; border-radius: 30px;
    font-size: .9rem; white-space: nowrap;
}
.bstrip-btn {
    position: absolute; top: 50%; transform: translateY(-50%);
    width: 40px; height: 40px; border-radius: 50%;
    background: rgba(255,255,255,.85); border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; color: #333; z-index: 10;
    transition: background .2s;
}
.bstrip-btn:hover { background: #fff; }
.bstrip-prev { left: 16px; }
.bstrip-next { right: 16px; }
.bstrip-dots {
    position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%);
    display: flex; gap: 6px; z-index: 5;
}
.bsd {
    width: 8px; height: 8px; border-radius: 50%;
    background: rgba(255,255,255,.5); border: none; cursor: pointer;
    transition: background .2s, width .2s;
}
.bsd.active { background: #fff; width: 20px; border-radius: 4px; }

/* ── Category image ── */
.cat-card { position: relative; overflow: hidden; }
.cat-img {
    width: 100%; height: 100%; object-fit: cover;
    position: absolute; inset: 0; border-radius: inherit;
    transition: transform .4s ease;
}
.cat-card:hover .cat-img { transform: scale(1.06); }
</style>

<script>
// ── Hero slider ──────────────────────────────────────────────
let heroIdx = 0;
const heroSlides = document.querySelectorAll('.hero-slide');
const heroDots   = document.querySelectorAll('.hsd');

function heroGoSlide(n) {
    if (!heroSlides.length) return;
    heroSlides[heroIdx].classList.remove('active');
    if (heroDots[heroIdx]) heroDots[heroIdx].classList.remove('active');
    heroIdx = (n + heroSlides.length) % heroSlides.length;
    heroSlides[heroIdx].classList.add('active');
    if (heroDots[heroIdx]) heroDots[heroIdx].classList.add('active');
}
if (heroSlides.length > 1) {
    setInterval(() => heroGoSlide(heroIdx + 1), 5000);
}

// ── Banner strip slider ──────────────────────────────────────
let stripIdx = 0;
const stripSlides = document.querySelectorAll('.banner-strip-slide');
const stripDots   = document.querySelectorAll('.bsd');

function stripGoSlide(n) {
    if (!stripSlides.length) return;
    stripSlides[stripIdx].classList.remove('active');
    if (stripDots[stripIdx]) stripDots[stripIdx].classList.remove('active');
    stripIdx = (n + stripSlides.length) % stripSlides.length;
    stripSlides[stripIdx].classList.add('active');
    if (stripDots[stripIdx]) stripDots[stripIdx].classList.add('active');
}
function stripSlide(dir) { stripGoSlide(stripIdx + dir); }
if (stripSlides.length > 1) {
    setInterval(() => stripGoSlide(stripIdx + 1), 6000);
}
</script>