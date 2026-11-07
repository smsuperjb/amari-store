<?php
// admin/includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();

$current_page = basename($_SERVER['PHP_SELF']);

$site_name_file = "../uploads/site_name.txt";
$site_title = file_exists($site_name_file) ? trim(file_get_contents($site_name_file)) : "ShopaPay";

$logo_path = "../uploads/logo.txt";
$current_logo = "";
if (file_exists($logo_path)) {
    $logo_filename = trim(file_get_contents($logo_path));
    if (!empty($logo_filename)) {
        $current_logo = "../uploads/" . $logo_filename;
    }
}

$nav_items = [
    ['file' => 'dashboard.php',      'icon' => 'fa-house',          'label' => 'Dashboard'],
    ['file' => 'orders.php',         'icon' => 'fa-bag-shopping',   'label' => 'Orders'],
    ['file' => 'product-list.php',   'icon' => 'fa-box',            'label' => 'Products'],
    ['file' => 'categories.php',     'icon' => 'fa-tags',           'label' => 'Categories'],
    ['file' => 'customers.php',      'icon' => 'fa-users',          'label' => 'Customers'],
    ['file' => 'shipping-zones.php', 'icon' => 'fa-truck',          'label' => 'Shipping'],
    ['file' => 'coupons.php',        'icon' => 'fa-percent',        'label' => 'Discounts'],
    ['file' => 'banners.php',        'icon' => 'fa-image',          'label' => 'Banners'],
    ['file' => 'settings.php',       'icon' => 'fa-gear',           'label' => 'Settings'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#6C3FC8">
    <title><?= htmlspecialchars($site_title) ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="/store/shopapay_api/admin/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>

<!-- ══ MOBILE TOGGLE BUTTON ══════════════════════════════════ -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false">
    <i class="fas fa-bars" id="toggleIcon"></i>
</button>

<div class="layout-wrap">

    <!-- ══ SIDEBAR ══════════════════════════════════════════════ -->
    <aside class="sidebar" id="sidebar" aria-label="Main navigation">

        <!-- Brand -->
        <div class="sidebar-brand">
            <?php if ($current_logo): ?>
                <img src="<?= htmlspecialchars($current_logo) ?>" alt="Logo" class="brand-logo">
            <?php else: ?>
                <div class="brand-icon"><i class="fas fa-store"></i></div>
            <?php endif; ?>
            <span class="brand-name"><?= htmlspecialchars($site_title) ?></span>
        </div>

        <div class="sidebar-divider"></div>

        <!-- Nav -->
        <nav class="sidebar-nav">
            <span class="nav-section-label">Main Menu</span>
            <?php foreach ($nav_items as $item):
                $isActive = $current_page === $item['file'];
            ?>
            <a href="/store/shopapay_api/admin/<?= $item['file'] ?>"
               class="nav-item <?= $isActive ? 'active' : '' ?>"
               aria-current="<?= $isActive ? 'page' : 'false' ?>">
                <span class="nav-icon"><i class="fas <?= $item['icon'] ?>"></i></span>
                <span class="nav-label"><?= $item['label'] ?></span>
                <?php if ($isActive): ?><span class="nav-pip" aria-hidden="true"></span><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- Footer: admin info + logout -->
        <div class="sidebar-footer">
            <div class="sidebar-divider"></div>

            <!-- Logged-in user -->
            <?php if (!empty($_SESSION['admin_name'])): ?>
            <div style="
                display: flex; align-items: center; gap: 10px;
                padding: 12px 14px 8px;
                font-size: 12px; color: #9CA3AF;
            ">
                <div class="avatar" style="
                    width: 30px; height: 30px; min-width: 30px;
                    border-radius: 50%; display: flex; align-items: center; justify-content: center;
                    color: #fff; font-size: 12px; font-weight: 700;
                ">
                    <?= strtoupper(substr($_SESSION['admin_name'], 0, 1)) ?>
                </div>
                <div style="overflow:hidden">
                    <div style="font-weight:600; color:#374151; font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?= htmlspecialchars($_SESSION['admin_name']) ?>
                    </div>
                    <div style="font-size:10px; color:#9CA3AF;">Administrator</div>
                </div>
            </div>
            <?php endif; ?>

            <a href="/store/shopapay_api/admin/logout.php" class="nav-item nav-item-danger" style="margin-top:4px">
                <span class="nav-icon"><i class="fas fa-arrow-right-from-bracket"></i></span>
                <span class="nav-label">Logout</span>
            </a>
        </div>
    </aside>

    <!-- ══ OVERLAY ═══════════════════════════════════════════════ -->
    <div class="sidebar-overlay" id="sidebarOverlay" role="presentation" aria-hidden="true"></div>

    <!-- ══ MAIN CONTENT ═══════════════════════════════════════════ -->
    <div class="main-content">

        <!-- ── Mobile top bar (visible only on mobile) ── -->
        <div id="mobileTopBar" style="
            display: none;
            position: sticky; top: 0; z-index: 200;
            background: #fff; border-bottom: 1px solid #F0F0F0;
            padding: 0 14px 0 62px;
            height: 56px;
            align-items: center; justify-content: space-between;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        ">
            <span style="font-size:14px; font-weight:700; color:#111827;">
                <?= htmlspecialchars($site_title) ?>
            </span>
            <div style="display:flex; gap:8px; align-items:center;">
                <a href="/store/shopapay_api/admin/orders.php"
                   style="width:36px;height:36px;border-radius:9px;border:1.5px solid #E5E7EB;
                          background:#fff;display:flex;align-items:center;justify-content:center;
                          color:#6B7280;text-decoration:none;font-size:14px;">
                    <i class="fas fa-bag-shopping"></i>
                </a>
                <a href="/store/shopapay_api/admin/logout.php"
                   style="width:36px;height:36px;border-radius:9px;border:1.5px solid #fecaca;
                          background:#fef2f2;display:flex;align-items:center;justify-content:center;
                          color:#EF4444;text-decoration:none;font-size:13px;">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
        <!-- /mobile top bar -->