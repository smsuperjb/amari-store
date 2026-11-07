<?php
// admin/export_orders.php
require_once '../config/database.php';

$search        = trim($_GET['search']        ?? '');
$status_filter = trim($_GET['status_filter'] ?? '');

$conditions = [];
$params     = [];

if ($search !== '') {
    $conditions[] = "(order_id LIKE :search OR customer_name LIKE :search OR phone LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($status_filter !== '') {
    $conditions[] = "status = :status";
    $params[':status'] = $status_filter;
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$stmt  = $pdo->prepare("SELECT * FROM orders $where ORDER BY created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Output as CSV (JRS / PH Global Jet Express format) ──
$filename = 'orders_jrs_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

// BOM for Excel UTF-8
fputs($out, "\xEF\xBB\xBF");

// ── JRS Template Header Rows ──
fputcsv($out, ['', '', 'PH GLOBAL JET EXPRESS INC.']);
fputcsv($out, ['V20200721', '', '', '', 'ORDER LIST']);
fputcsv($out, ['(*) Information that must be filled out']);

// ── JRS Column Headers ──
fputcsv($out, [
    'Receiver(*)',
    'Receiver Telephone (*)',
    'Receiver Address (*)',
    'Receiver Province (*)',
    'Receiver City (*)',
    'Receiver Region (*)',
    'Express Type (*)',
    'Parcel Name (*)',
    'Weight (kg)  (*)',
    'Total parcels(*)',
    'Parcel Value (Insurance Fee) (*)',
    'COD (PHP) (*)',
    'Remarks',
]);

foreach ($orders as $o) {
    $items = json_decode($o['items_summary'] ?? '[]', true);

    // Parcel Name — item names only (no qty/price)
    $parcelName = '';
    $totalQty   = 0;

    if (is_array($items)) {
        $names = array_map(fn($i) => trim($i['name'] ?? ''), $items);
        $parcelName = implode(' / ', array_filter($names));
        $totalQty   = array_sum(array_column($items, 'qty'));
    }

    if ($totalQty < 1) $totalQty = 1;

    // Insurance = total_amount / 2, rounded to 2 decimals
    $totalAmount = floatval($o['total_amount'] ?? 0);
    $insurance   = round($totalAmount / 2, 2);

    // COD = total_amount if payment method is COD, else 0
    $paymentMethod = strtolower(trim($o['payment_method'] ?? ''));
    $cod = (str_contains($paymentMethod, 'cod')) ? $totalAmount : 0;

    // Phone — strip leading 0 or +63 so it's clean
    $phone = preg_replace('/^\+63|^0/', '', trim($o['phone'] ?? ''));

    fputcsv($out, [
        $o['customer_name']                        ?? '',  // Receiver
        $phone,                                             // Receiver Telephone
        $o['full_address'] ?? $o['address']        ?? '',  // Receiver Address
        strtoupper($o['province']                  ?? ''), // Receiver Province
        strtoupper($o['city']                      ?? ''), // Receiver City
        strtoupper($o['barangay']                  ?? ''), // Receiver Region
        'EZ',                                              // Express Type
        $parcelName,                                       // Parcel Name
        '0.5',                                             // Weight (kg)
        $totalQty,                                         // Total parcels
        $insurance,                                        // Parcel Value
        $cod,                                              // COD
        '',                                                // Remarks
    ]);
}

fclose($out);
exit;
?>