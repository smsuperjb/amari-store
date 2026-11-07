<?php
// admin/print_waybill.php
require_once '../config/database.php';

if (!isset($_GET['id'])) die("Order ID is required");

$order_id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) die("Order not found");

$items       = json_decode($order['items_summary'] ?? '[]', true);
if (!is_array($items)) $items = [];

$totalAmount = (float)($order['total_amount'] ?? $order['total_price'] ?? 0);
$fullAddress = trim($order['full_address'] ?? $order['address'] ?? '');
$province    = $order['province']      ?? '';
$city        = $order['city']          ?? '';
$barangay    = $order['barangay']      ?? '';
$payMethod   = $order['payment_method'] ?? 'Cash on Delivery';
$isCod       = stripos($payMethod, 'cod') !== false || stripos($payMethod, 'cash') !== false;

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
<title>Waybill — <?= htmlspecialchars($order['order_id']) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,sans-serif;background:#F3F4F6;color:#111827;font-size:13px;}
.page{max-width:780px;margin:30px auto;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.10);overflow:hidden;}
.wb-header{background:#1F2937;color:#fff;padding:20px 28px;display:flex;align-items:center;justify-content:space-between;}
.brand{display:flex;align-items:center;gap:14px;}
.brand img{height:40px;object-fit:contain;}
.brand-name{font-size:18px;font-weight:700;letter-spacing:.5px;}
.brand-sub{font-size:11px;color:#9CA3AF;margin-top:2px;}
.order-id{text-align:right;}
.order-id .label{font-size:10px;color:#9CA3AF;text-transform:uppercase;letter-spacing:.5px;}
.order-id .value{font-size:20px;font-weight:700;color:#F59E0B;margin-top:2px;}
.meta-strip{background:#F9FAFB;border-bottom:1px solid #E5E7EB;padding:10px 28px;display:flex;gap:24px;font-size:12px;color:#6B7280;flex-wrap:wrap;}
.meta-strip strong{color:#374151;}
.wb-body{padding:24px 28px;}
.addr-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;}
.addr-box{border:1.5px solid #E5E7EB;border-radius:10px;padding:14px 16px;}
.addr-box.highlight{border-color:#6C3FC8;background:#F5F3FF;}
.addr-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6B7280;margin-bottom:8px;}
.addr-label.purple{color:#6C3FC8;}
.addr-name{font-size:15px;font-weight:700;color:#111827;margin-bottom:4px;}
.addr-line{color:#6B7280;font-size:12px;line-height:1.7;}
.section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6B7280;margin-bottom:10px;}
table.items-tbl{width:100%;border-collapse:collapse;margin-bottom:16px;font-size:13px;}
.items-tbl thead th{background:#F9FAFB;color:#6B7280;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;padding:9px 12px;border-bottom:1px solid #E5E7EB;text-align:left;}
.items-tbl tbody td{padding:10px 12px;border-bottom:1px solid #F3F4F6;color:#374151;}
.items-tbl tbody tr:last-child td{border-bottom:none;}
.text-right{text-align:right;}
.totals-row{display:flex;justify-content:flex-end;margin-top:8px;}
.totals-box{width:260px;border:1.5px solid #E5E7EB;border-radius:10px;overflow:hidden;}
.totals-line{display:flex;justify-content:space-between;padding:9px 14px;border-bottom:1px solid #F3F4F6;font-size:13px;color:#6B7280;}
.totals-line.total-final{background:#1F2937;color:#fff;font-weight:700;font-size:15px;border-bottom:none;}
.totals-line.cod-line{background:#FEF3C7;color:#92400E;font-weight:700;border-bottom:none;}
.pay-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;}
.pay-cod{background:#FEF3C7;color:#92400E;}
.pay-gcash{background:#DBEAFE;color:#1E40AF;}
.pay-maya{background:#EDE9FE;color:#5B21B6;}
.pay-other{background:#F3F4F6;color:#374151;}
.sig-box{border-top:1.5px dashed #D1D5DB;width:180px;text-align:center;padding-top:6px;color:#9CA3AF;font-size:11px;}
.wb-footer{background:#F9FAFB;border-top:1px solid #E5E7EB;padding:16px 28px;display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#9CA3AF;}
.print-bar{text-align:center;padding:20px;}
.btn-print{background:#6C3FC8;color:#fff;border:none;border-radius:10px;padding:12px 36px;font-size:15px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;}
.btn-print:hover{background:#5a32a8;}
.btn-back{background:#fff;color:#374151;border:1.5px solid #E5E7EB;border-radius:10px;padding:12px 24px;font-size:14px;font-weight:600;cursor:pointer;margin-right:10px;text-decoration:none;}
@media print{body{background:#fff;}.page{box-shadow:none;margin:0;border-radius:0;}.print-bar{display:none;}}
</style>
</head>
<body>

<div class="print-bar">
  <a class="btn-back" href="javascript:window.close()">&#10005; Close</a>
  <button class="btn-print" onclick="window.print()">&#128424; Print Waybill</button>
</div>

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

  <!-- Meta strip -->
  <div class="meta-strip">
    <div><strong>Date:</strong> <?= date('F j, Y', strtotime($order['created_at'])) ?></div>
    <div><strong>Time:</strong> <?= date('h:i A', strtotime($order['created_at'])) ?></div>
    <div>
      <strong>Payment:</strong>
      <?php
      $pClass = 'pay-other';
      if ($isCod) $pClass = 'pay-cod';
      elseif (stripos($payMethod, 'gcash') !== false) $pClass = 'pay-gcash';
      elseif (stripos($payMethod, 'maya')  !== false) $pClass = 'pay-maya';
      ?>
      <span class="pay-badge <?= $pClass ?>"><?= htmlspecialchars($payMethod) ?></span>
    </div>
    <div><strong>Status:</strong> <?= htmlspecialchars($order['status'] ?? 'Processing') ?></div>
  </div>

  <!-- Body -->
  <div class="wb-body">

    <!-- Addresses -->
    <div class="addr-row">
      <div class="addr-box">
        <div class="addr-label">&#128230; From (Sender)</div>
        <div class="addr-name">ShopaPay Store</div>
        <div class="addr-line">
          Metro Manila, Philippines<br>
          shopapay@amariaestheticsph.com
        </div>
      </div>
      <div class="addr-box highlight">
        <div class="addr-label purple">&#128205; To (Recipient)</div>
        <div class="addr-name"><?= htmlspecialchars($order['customer_name'] ?? '—') ?></div>
        <div class="addr-line">
          <?php if (!empty($order['phone'])): ?>
            &#128222; <?= htmlspecialchars($order['phone']) ?><br>
          <?php endif; ?>
          <?php if (!empty($order['email'])): ?>
            &#9993; <?= htmlspecialchars($order['email']) ?><br>
          <?php endif; ?>
          <?php
          $addrParts = array_filter([$fullAddress, $barangay, $city, $province]);
          echo htmlspecialchars(implode(', ', $addrParts));
          ?>
        </div>
      </div>
    </div>

    <!-- Items -->
    <div class="section-title">Items Ordered</div>
    <table class="items-tbl">
      <thead>
        <tr>
          <th>#</th>
          <th>Item Description</th>
          <th class="text-right">Qty</th>
          <th class="text-right">Unit Price</th>
          <th class="text-right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($items)): ?>
          <?php foreach ($items as $idx => $item): ?>
          <?php
          $qty      = (int)($item['qty'] ?? $item['quantity'] ?? 1);
          $price    = (float)($item['price'] ?? 0);
          $subtotal = $price * $qty;
          ?>
          <tr>
            <td style="color:#9CA3AF;"><?= $idx + 1 ?></td>
            <td><?= htmlspecialchars($item['name'] ?? 'Item') ?></td>
            <td class="text-right"><?= $qty ?></td>
            <td class="text-right">&#8369;<?= number_format($price, 2) ?></td>
            <td class="text-right" style="font-weight:600;">&#8369;<?= number_format($subtotal, 2) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="color:#9CA3AF;text-align:center;padding:20px;">No item details available</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-row">
      <div class="totals-box">
        <div class="totals-line">
          <span>Subtotal</span>
          <span>&#8369;<?= number_format($totalAmount, 2) ?></span>
        </div>
        <div class="totals-line">
          <span>Shipping</span>
          <span>Calculated at delivery</span>
        </div>
        <div class="totals-line total-final">
          <span>Total Amount</span>
          <span>&#8369;<?= number_format($totalAmount, 2) ?></span>
        </div>
        <?php if ($isCod): ?>
        <div class="totals-line cod-line">
          <span>&#128181; COD Collection</span>
          <span>&#8369;<?= number_format($totalAmount, 2) ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Signatures -->
    <div style="display:flex;justify-content:space-between;margin-top:36px;padding-top:16px;">
      <div class="sig-box">Prepared by</div>
      <div class="sig-box">Received by</div>
      <div class="sig-box">Courier Signature</div>
    </div>

  </div>

  <!-- Footer -->
  <div class="wb-footer">
    <div>Thank you for shopping with ShopaPay! &#128717;<br>Please present this waybill to the courier.</div>
    <div style="text-align:right;">
      Printed: <?= date('M d, Y h:i A') ?><br>
      Ref: <?= htmlspecialchars($order['order_id']) ?>
    </div>
  </div>

</div>

</body>
</html>