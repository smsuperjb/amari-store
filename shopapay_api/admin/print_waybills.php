<?php
// admin/print_waybills.php — multiple waybills, one page, auto-print
require_once '../config/database.php';

if (empty($_GET['ids'])) die("No order IDs provided.");

$rawIds = explode(',', $_GET['ids']);
$ids    = array_map('intval', array_filter($rawIds));
if (empty($ids)) die("Invalid order IDs.");

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id IN ($placeholders) ORDER BY created_at DESC");
$stmt->execute($ids);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($orders)) die("No orders found.");

// Logo
$logo_path = __DIR__ . "/../uploads/logo.txt";
$logoUrl   = "";
if (file_exists($logo_path)) {
    $logoFile = trim(file_get_contents($logo_path));
    if (!empty($logoFile)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $logoUrl  = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/store/shopapay_api/uploads/' . $logoFile;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Waybills — <?= count($orders) ?> Orders</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,sans-serif;background:#F3F4F6;color:#111827;font-size:13px;}

/* ── Screen: show print bar + pages ── */
.print-bar{text-align:center;padding:20px;background:#fff;border-bottom:1px solid #E5E7EB;position:sticky;top:0;z-index:99;}
.btn-print{background:#6C3FC8;color:#fff;border:none;border-radius:10px;padding:10px 32px;font-size:15px;font-weight:700;cursor:pointer;margin-right:8px;}
.btn-print:hover{background:#5a32a8;}
.btn-back{background:#fff;color:#374151;border:1.5px solid #E5E7EB;border-radius:10px;padding:10px 20px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;}
.order-count{font-size:13px;color:#6B7280;margin-left:12px;}

.page{max-width:780px;margin:24px auto;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.10);overflow:hidden;}
.wb-header{background:#1F2937;color:#fff;padding:18px 24px;display:flex;align-items:center;justify-content:space-between;}
.brand{display:flex;align-items:center;gap:12px;}
.brand img{height:36px;object-fit:contain;}
.brand-name{font-size:17px;font-weight:700;}
.brand-sub{font-size:11px;color:#9CA3AF;margin-top:2px;}
.order-id .label{font-size:10px;color:#9CA3AF;text-transform:uppercase;letter-spacing:.5px;}
.order-id .value{font-size:18px;font-weight:700;color:#F59E0B;text-align:right;}
.meta-strip{background:#F9FAFB;border-bottom:1px solid #E5E7EB;padding:9px 24px;display:flex;gap:20px;font-size:12px;color:#6B7280;flex-wrap:wrap;}
.meta-strip strong{color:#374151;}
.wb-body{padding:20px 24px;}
.addr-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;}
.addr-box{border:1.5px solid #E5E7EB;border-radius:10px;padding:12px 14px;}
.addr-box.highlight{border-color:#6C3FC8;background:#F5F3FF;}
.addr-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6B7280;margin-bottom:6px;}
.addr-label.purple{color:#6C3FC8;}
.addr-name{font-size:14px;font-weight:700;color:#111827;margin-bottom:3px;}
.addr-line{color:#6B7280;font-size:12px;line-height:1.6;}
.section-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6B7280;margin-bottom:8px;}
table.items-tbl{width:100%;border-collapse:collapse;margin-bottom:12px;font-size:12px;}
.items-tbl thead th{background:#F9FAFB;color:#6B7280;font-size:10px;font-weight:600;text-transform:uppercase;padding:8px 10px;border-bottom:1px solid #E5E7EB;text-align:left;}
.items-tbl tbody td{padding:8px 10px;border-bottom:1px solid #F3F4F6;color:#374151;}
.items-tbl tbody tr:last-child td{border-bottom:none;}
.text-right{text-align:right;}
.totals-row{display:flex;justify-content:flex-end;}
.totals-box{width:240px;border:1.5px solid #E5E7EB;border-radius:10px;overflow:hidden;}
.totals-line{display:flex;justify-content:space-between;padding:8px 12px;border-bottom:1px solid #F3F4F6;font-size:12px;color:#6B7280;}
.totals-line.total-final{background:#1F2937;color:#fff;font-weight:700;font-size:14px;border-bottom:none;}
.totals-line.cod-line{background:#FEF3C7;color:#92400E;font-weight:700;border-bottom:none;}
.pay-badge{display:inline-flex;align-items:center;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;}
.pay-cod{background:#FEF3C7;color:#92400E;}
.pay-gcash{background:#DBEAFE;color:#1E40AF;}
.pay-maya{background:#EDE9FE;color:#5B21B6;}
.pay-other{background:#F3F4F6;color:#374151;}
.sig-row{display:flex;justify-content:space-between;margin-top:28px;}
.sig-box{border-top:1.5px dashed #D1D5DB;width:160px;text-align:center;padding-top:5px;color:#9CA3AF;font-size:10px;}
.wb-footer{background:#F9FAFB;border-top:1px solid #E5E7EB;padding:12px 24px;display:flex;justify-content:space-between;font-size:11px;color:#9CA3AF;}

/* ── Print: one waybill per page ── */
@media print {
  body { background: #fff; }
  .print-bar { display: none; }
  .page {
    box-shadow: none;
    border-radius: 0;
    margin: 0;
    max-width: 100%;
    page-break-after: always;
    break-after: page;
  }
  .page:last-child {
    page-break-after: avoid;
    break-after: avoid;
  }
}
</style>
</head>
<body>

<!-- Print bar -->
<div class="print-bar">
  <a class="btn-back" href="javascript:window.close()">&#10005; Close</a>
  <button class="btn-print" onclick="window.print()">&#128424; Print All Waybills</button>
  <span class="order-count"><?= count($orders) ?> waybill<?= count($orders) > 1 ? 's' : '' ?> ready</span>
</div>

<?php foreach ($orders as $order):
  $items       = json_decode($order['items_summary'] ?? '[]', true);
  if (!is_array($items)) $items = [];
  $totalAmount = (float)($order['total_amount'] ?? 0);
  $fullAddress = trim($order['full_address'] ?? $order['address'] ?? '');
  $province    = $order['province']       ?? '';
  $city        = $order['city']           ?? '';
  $barangay    = $order['barangay']       ?? '';
  $payMethod   = $order['payment_method'] ?? 'Cash on Delivery';
  $payLower    = strtolower($payMethod);
  $isCod       = str_contains($payLower, 'cod') ||
                 ($payLower === 'cash on delivery') ||
                 (str_starts_with($payLower, 'cash') && !str_contains($payLower, 'gcash'));
  $pClass = 'pay-other';
  if ($isCod)                              $pClass = 'pay-cod';
  elseif (str_contains($payLower,'gcash')) $pClass = 'pay-gcash';
  elseif (str_contains($payLower,'maya'))  $pClass = 'pay-maya';
  $addrParts = array_filter([$fullAddress, $barangay, $city, $province]);
?>

<div class="page">

  <!-- Header -->
  <div class="wb-header">
    <div class="brand">
      <?php if ($logoUrl): ?>
        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo">
      <?php endif; ?>
      <div>
        <div class="brand-name">ShopaPay</div>
        <div class="brand-sub">Shipping Waybill</div>
      </div>
    </div>
    <div class="order-id">
      <div class="label">Order ID</div>
      <div class="value"><?= htmlspecialchars($order['order_id']) ?></div>
    </div>
  </div>

  <!-- Meta -->
  <div class="meta-strip">
    <div><strong>Date:</strong> <?= date('F j, Y', strtotime($order['created_at'])) ?></div>
    <div><strong>Time:</strong> <?= date('h:i A', strtotime($order['created_at'])) ?></div>
    <div><strong>Payment:</strong> <span class="pay-badge <?= $pClass ?>"><?= htmlspecialchars($payMethod) ?></span></div>
    <div><strong>Status:</strong> <?= htmlspecialchars($order['status'] ?? 'Processing') ?></div>
  </div>

  <!-- Body -->
  <div class="wb-body">

    <!-- Addresses -->
    <div class="addr-row">
      <div class="addr-box">
        <div class="addr-label">&#128230; From</div>
        <div class="addr-name">ShopaPay Store</div>
        <div class="addr-line">Metro Manila, Philippines<br>shopapay@amariaestheticsph.com</div>
      </div>
      <div class="addr-box highlight">
        <div class="addr-label purple">&#128205; To (Recipient)</div>
        <div class="addr-name"><?= htmlspecialchars($order['customer_name'] ?? '—') ?></div>
        <div class="addr-line">
          <?php if (!empty($order['phone'])): ?>&#128222; <?= htmlspecialchars($order['phone']) ?><br><?php endif; ?>
          <?php if (!empty($order['email'])): ?>&#9993; <?= htmlspecialchars($order['email']) ?><br><?php endif; ?>
          <?= htmlspecialchars(implode(', ', $addrParts)) ?>
        </div>
      </div>
    </div>

    <!-- Items -->
    <div class="section-title">Items Ordered</div>
    <table class="items-tbl">
      <thead>
        <tr>
          <th>#</th><th>Description</th>
          <th class="text-right">Qty</th>
          <th class="text-right">Unit Price</th>
          <th class="text-right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($items)): ?>
          <?php foreach ($items as $idx => $item):
            $qty      = (int)($item['qty'] ?? $item['quantity'] ?? 1);
            $price    = (float)($item['price'] ?? 0);
          ?>
          <tr>
            <td style="color:#9CA3AF;"><?= $idx + 1 ?></td>
            <td><?= htmlspecialchars($item['name'] ?? 'Item') ?></td>
            <td class="text-right"><?= $qty ?></td>
            <td class="text-right">&#8369;<?= number_format($price, 2) ?></td>
            <td class="text-right" style="font-weight:600;">&#8369;<?= number_format($price * $qty, 2) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" style="color:#9CA3AF;text-align:center;padding:16px;">No item details</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-row">
      <div class="totals-box">
        <div class="totals-line"><span>Subtotal</span><span>&#8369;<?= number_format($totalAmount, 2) ?></span></div>
        <div class="totals-line"><span>Shipping</span><span>At delivery</span></div>
        <div class="totals-line total-final"><span>Total</span><span>&#8369;<?= number_format($totalAmount, 2) ?></span></div>
        <?php if ($isCod): ?>
        <div class="totals-line cod-line"><span>&#128181; COD</span><span>&#8369;<?= number_format($totalAmount, 2) ?></span></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Signatures -->
    <div class="sig-row">
      <div class="sig-box">Prepared by</div>
      <div class="sig-box">Received by</div>
      <div class="sig-box">Courier Signature</div>
    </div>
  </div>

  <!-- Footer -->
  <div class="wb-footer">
    <div>Thank you for shopping with ShopaPay! &#128717;</div>
    <div>Printed: <?= date('M d, Y h:i A') ?> &nbsp;|&nbsp; Ref: <?= htmlspecialchars($order['order_id']) ?></div>
  </div>

</div>
<?php endforeach; ?>

</body>
</html>