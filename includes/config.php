<?php
/**
 * store/includes/config.php
 * Robust DB bridge — tries multiple paths automatically.
 */
if (!isset($pdo)) {
    $dbCandidates = [
        dirname(__DIR__) . '/shopapay_api/config/database.php',          // store/shopapay_api (current home)
        $_SERVER['DOCUMENT_ROOT'] . '/shopapay_api/config/database.php',
        dirname(__DIR__, 2) . '/shopapay_api/config/database.php',
        dirname(__DIR__, 1) . '/../shopapay_api/config/database.php',
    ];
    $dbLoaded = false;
    foreach ($dbCandidates as $candidate) {
        if (file_exists($candidate)) {
            require_once $candidate;
            $dbLoaded = true;
            break;
        }
    }
    if (!$dbLoaded) {
        http_response_code(500);
        die('<h2>Config error:</h2> Cannot find database.php. Check paths in store/includes/config.php.<br>Tried:<br>'
            . implode('<br>', $dbCandidates));
    }
}

define('STORE_URL',   'https://amariaestheticsph.com/store');
define('SITE_NAME',   'Amari Aesthetics');
define('SITE_TAGLINE','Science-Backed. Elegance-Defined.');
define('CURRENCY',    '₱');
define('ASSETS_URL',  STORE_URL . '/assets');

// Uploads base (filesystem + URL)
define('UPLOADS_FS',  dirname(__DIR__) . '/shopapay_api/uploads/');
define('UPLOADS_URL', STORE_URL . '/shopapay_api/uploads/');

// ── HELPERS ──────────────────────────────────────────────────

function formatPrice(float $a): string {
    return '₱' . number_format($a, 2);
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Smart product image URL — checks filesystem to find where image lives
 */
function productImageUrl(?string $img): string {
    if (empty($img)) return '';
    if (str_starts_with($img, 'http')) return $img;
    $candidates = ['products/', '', 'product/', 'images/products/', 'images/'];
    foreach ($candidates as $sub) {
        if (file_exists(UPLOADS_FS . $sub . $img)) {
            return UPLOADS_URL . $sub . $img;
        }
    }
    return UPLOADS_URL . 'products/' . $img;
}

/**
 * Banner image URL
 * Prefers image_url (full URL) → falls back to image filename in banners/
 */
function bannerImageUrl(array $banner): string {
    // Use stored full URL if available
    if (!empty($banner['image_url']) && str_starts_with($banner['image_url'], 'http')) {
        // Older rows point at /shopapay_api/ from before it moved into /store/
        return str_replace('amariaestheticsph.com/shopapay_api/', 'amariaestheticsph.com/store/shopapay_api/', $banner['image_url']);
    }
    // Fall back to filename
    if (!empty($banner['image'])) {
        $path = UPLOADS_FS . 'banners/' . $banner['image'];
        if (file_exists($path)) {
            return UPLOADS_URL . 'banners/' . $banner['image'];
        }
        // Return anyway — server might serve it
        return UPLOADS_URL . 'banners/' . $banner['image'];
    }
    return '';
}

/**
 * Category image URL
 * Admin saves as cat_XXXXX.jpg in uploads/
 */
function categoryImageUrl(array $cat): string {
    if (empty($cat['image'])) return '';
    // Check root uploads first (admin saves here)
    if (file_exists(UPLOADS_FS . $cat['image'])) {
        return UPLOADS_URL . $cat['image'];
    }
    // Check categories subfolder
    if (file_exists(UPLOADS_FS . 'categories/' . $cat['image'])) {
        return UPLOADS_URL . 'categories/' . $cat['image'];
    }
    // Return anyway
    return UPLOADS_URL . $cat['image'];
}

function getColumns(PDO $pdo, string $table): array {
    try {
        return array_column(
            $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(),
            'Field'
        );
    } catch (Exception $e) { return []; }
}

function generateOrderId(): string {
    return 'AM-' . strtoupper(substr(md5(uniqid()), 0, 8));
}