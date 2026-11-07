<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$province = trim($_GET['province'] ?? '');
if ($province === '') {
    echo json_encode([
        "status" => "error",
        "message" => "Province required"
    ]);
    exit;
}

function normalize_text($text) {
    return strtolower(trim(preg_replace('/\s+/', ' ', $text)));
}

// ✅ Resolve NCR / Metro Manila aliases to a single canonical form
function resolve_province($text) {
    $lower = strtolower(trim($text));
    if (
        str_contains($lower, 'ncr')          ||
        str_contains($lower, 'metro manila') ||
        str_contains($lower, 'national capital')
    ) {
        return 'metro manila';
    }
    return normalize_text($text);
}

$targetProvince = resolve_province($province);

$stmt = $pdo->query("SELECT shipping_cost, provinces, zone_name FROM shipping_zones");
$zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$shippingCost = 150; // fallback
$matchedZone  = null;

foreach ($zones as $zone) {
    $rawProvinces = trim($zone['provinces'] ?? '');
    if ($rawProvinces === '') {
        continue;
    }

    // Try JSON array first, then fall back to comma/pipe/newline-separated
    $provinceList = json_decode($rawProvinces, true);
    if (!is_array($provinceList)) {
        $provinceList = preg_split('/[,|\n]/', $rawProvinces);
    }

    foreach ($provinceList as $p) {
        // ✅ Apply the same alias resolution to every DB entry
        if (resolve_province($p) === $targetProvince) {
            $shippingCost = (float)$zone['shipping_cost'];
            $matchedZone  = $zone['zone_name'] ?? null;
            break 2;
        }
    }
}

echo json_encode([
    "status"       => "success",
    "province"     => $province,
    "matched_zone" => $matchedZone,
    "shipping_cost" => $shippingCost
]);
?>