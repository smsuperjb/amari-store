<?php
// store/includes/header.php
// Call AFTER all redirect logic in each page
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';

$_cartCount   = cartCount();
$_currentPage = basename($_SERVER['PHP_SELF']);
$_user        = currentUser();
$_pageTitle   = ($pageTitle ?? SITE_NAME) . ' — ' . SITE_TAGLINE;
$_pageDesc    = $pageDesc ?? 'Premium aesthetics & skincare — beauty rooted in science, defined by elegance.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($_pageTitle) ?></title>
  <meta name="description" content="<?= e($_pageDesc) ?>">
  <meta property="og:title"       content="<?= e($pageTitle ?? SITE_NAME) ?>">
  <meta property="og:description" content="<?= e($_pageDesc) ?>">
  <meta property="og:type"        content="website">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <script>const STORE_URL = '<?= STORE_URL ?>';</script>

  <!-- Store CSS -->
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/store.css?v=<?= filemtime(__DIR__ . '/../assets/css/store.css') ?>">
</head>


<style>
.mobile-bottom-nav {
  display: none; /* shown only on mobile via media query below */
  position: fixed;
  bottom: 35px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 999;

  width: calc(100% - 32px);
  max-width: 420px;

  background: var(--ivory-warm, #fff);
  border-radius: 999px;
  box-shadow:
    0 8px 30px rgba(0, 0, 0, 0.12),
    0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid var(--mist, rgba(0,0,0,.06));

  padding: 8px 6px;
  align-items: center;
  justify-content: space-around;

  /* respect iPhone home-indicator safe area without pushing bar off-screen */
  padding-bottom: calc(8px + env(safe-area-inset-bottom, 0px) * 0);
}

.mbn-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
  flex: 1;
  padding: 8px 4px;
  border-radius: 999px;
  text-decoration: none;
  color: var(--ink-soft, #8a8580);
  font-family: var(--font-body, 'Jost', sans-serif);
  font-size: .62rem;
  font-weight: 500;
  letter-spacing: .02em;
  transition: color .25s ease, background .25s ease, transform .2s ease;
  position: relative;
}

.mbn-item i {
  font-size: 1.05rem;
  transition: transform .25s ease;
}

.mbn-item:active {
  transform: scale(0.92);
}

.mbn-item.is-active {
  color: var(--mocha, #6b4a3d);
  background: var(--blush, #f7e9e4);
}

.mbn-item.is-active i {
  transform: translateY(-1px);
}

/* Cart badge */
.mbn-cart-icon-wrap {
  position: relative;
  display: inline-flex;
}

.mbn-cart-dot {
  position: absolute;
 top: -6px;
  right: -8px;
  min-width: 15px;
  height: 15px;
  padding: 0 3px;
  border-radius: 999px;
  background: var(--rose, #c97a63);
  color: #fff;
  font-size: .55rem;
  font-weight: 700;
  line-height: 15px;
  text-align: center;
  display: none;
}

.mbn-cart-dot.visible {
  display: block;
}

/* Show only on mobile, hide desktop header's mobile relevance conflicts */
@media (max-width: 768px) {
  .mobile-bottom-nav {
    display: flex;
  }

  /* Push page content up so the floating bar never covers footer content */
  main#main-content {
    padding-bottom: 90px;
  }
}
</style> 
<body>

<!-- ══════════ HEADER ══════════ -->
<header class="site-header" id="siteHeader">
  <div class="header-inner">

    <!-- Logo -->
    <a href="<?= STORE_URL ?>/" class="site-logo" aria-label="<?= SITE_NAME ?> Home">
        <img src="<?= STORE_URL ?>/assets/images/logo.png">
      <!--<span class="logo-mark"></span>-->
      <!--<div class="logo-text-wrap">-->
      <!--  <span class="logo-name">Amaria</span>-->
      <!--  <span class="logo-sub">Aesthetics</span>-->
      <!--</div>-->
    </a>

    <!-- Desktop Nav -->
    <nav class="main-nav" id="mainNav" aria-label="Main navigation">
      <a href="<?= STORE_URL ?>/"   class="nav-link <?= $_currentPage===''   ?'is-active':'' ?>">Home</a>
      <a href="<?= STORE_URL ?>/shop"    class="nav-link <?= $_currentPage==='shop'    ?'is-active':'' ?>">Shop</a>
      <a href="<?= STORE_URL ?>/about"   class="nav-link <?= $_currentPage==='about'   ?'is-active':'' ?>">About</a>
      <a href="https://amariaestheticsph.com/booking/"   class="nav-link <?= $_currentPage==='about'   ?'is-active':'' ?>">Booking</a>
      <a href="<?= STORE_URL ?>/contact" class="nav-link <?= $_currentPage==='contact' ?'is-active':'' ?>">Contact</a>
    </nav>
    



    <!-- Actions -->
    <div class="header-actions">

      <button class="hdr-btn" id="searchToggle" aria-label="Search" title="Search">
        <i class="fas fa-magnifying-glass"></i>
      </button>

      <?php if (isLoggedIn()): ?>
        <a href="<?= STORE_URL ?>/profile" class="hdr-btn" aria-label="My account" title="My Account">
          <i class="fas fa-user"></i>
        </a>
      <?php else: ?>
        <a href="<?= STORE_URL ?>/login" class="hdr-btn" aria-label="Login" title="Login">
          <i class="far fa-user"></i>
        </a>
      <?php endif; ?>

      <a href="<?= STORE_URL ?>/cart" class="hdr-btn cart-btn" aria-label="Cart (<?= $_cartCount ?> items)" title="Cart">
        <i class="fas fa-bag-shopping"></i>
        <span class="cart-dot <?= $_cartCount > 0 ? 'visible' : '' ?>" id="cartDot">
          <?= $_cartCount > 0 ? $_cartCount : '' ?>
        </span>
      </a>

      <!-- Hamburger -->
      <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">
        <span class="ham-line"></span>
        <span class="ham-line"></span>
        <span class="ham-line ham-short"></span>
      </button>

    </div>
  </div>

  <!-- Search Drawer -->
  <div class="search-drawer" id="searchDrawer" aria-hidden="true">
    <div class="search-drawer-inner">
      <form action="<?= STORE_URL ?>/shop" method="GET" class="search-form" role="search">
        <i class="fas fa-magnifying-glass search-icon"></i>
        <input type="text" name="q" id="searchInput"
               placeholder="Search for products, treatments, skincare…"
               autocomplete="off" aria-label="Search products">
        <button type="submit" class="search-submit">Search</button>
        <button type="button" class="search-close" id="searchClose" aria-label="Close search">
          <i class="fas fa-xmark"></i>
        </button>
      </form>
    </div>
  </div>
</header>

    <!-- ══════════ MOBILE BOTTOM NAV (floating, rounded) ══════════ -->
<nav class="mobile-bottom-nav" aria-label="Mobile navigation">
  <a href="<?= STORE_URL ?>/" class="mbn-item <?= $_currentPage === '' ? 'is-active' : '' ?>">
    <i class="fas fa-house"></i>
    <span>Home</span>
  </a>
  <a href="<?= STORE_URL ?>/shop" class="mbn-item <?= $_currentPage === 'shop' ? 'is-active' : '' ?>">
    <i class="fas fa-store"></i>
    <span>Shop</span>
  </a>
  <a href="<?= STORE_URL ?>/cart" class="mbn-item mbn-cart <?= $_currentPage === 'cart' ? 'is-active' : '' ?>">
    <span class="mbn-cart-icon-wrap">
      <i class="fas fa-bag-shopping"></i>
      <span class="mbn-cart-dot <?= $_cartCount > 0 ? 'visible' : '' ?>" id="mbnCartDot">
        <?= $_cartCount > 0 ? $_cartCount : '' ?>
      </span>
    </span>
    <span>Cart</span>
  </a>
  <a href="<?= isLoggedIn() ? STORE_URL . '/profile' : STORE_URL . '/login' ?>"
     class="mbn-item <?= in_array($_currentPage, ['profile', 'login']) ? 'is-active' : '' ?>">
    <i class="<?= isLoggedIn() ? 'fas' : 'far' ?> fa-user"></i>
    <span>Profile</span>
  </a>
</nav>

<!-- Mobile nav overlay -->
<div class="nav-overlay" id="navOverlay" aria-hidden="true"></div>

<!-- Toast container -->
<div class="toast-stack" id="toastStack" aria-live="polite"></div>

<!-- Main content starts -->
<main id="main-content">
