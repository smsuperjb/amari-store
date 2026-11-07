<?php
// admin/orders.php
ob_start();
require_once '../config/database.php';

// ==================== HELPERS ====================
function getNextOrderId($pdo) {
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(order_id, 5) AS UNSIGNED)) as max_num FROM orders WHERE order_id LIKE 'ORD-%'");
    $result = $stmt->fetch();
    $next = ($result['max_num'] ?? 0) + 1;
    return "ORD-" . str_pad($next, 5, '0', STR_PAD_LEFT);
}

function statusBadge($status) {
    $map = [
        'delivered'        => ['bg' => '#D1FAE5', 'color' => '#065F46', 'label' => 'Delivered'],
        'shipped'          => ['bg' => '#DBEAFE', 'color' => '#1E40AF', 'label' => 'Shipped'],
        'out for delivery' => ['bg' => '#E0E7FF', 'color' => '#3730A3', 'label' => 'Out for Delivery'],
        'confirmed'        => ['bg' => '#D1FAE5', 'color' => '#065F46', 'label' => 'Confirmed'],
        'cancelled'        => ['bg' => '#FEE2E2', 'color' => '#991B1B', 'label' => 'Cancelled'],
        'processing'       => ['bg' => '#FEF3C7', 'color' => '#92400E', 'label' => 'Processing'],
    ];
    $key = strtolower(trim($status));
    $s   = $map[$key] ?? ['bg' => '#F3F4F6', 'color' => '#374151', 'label' => ucfirst($status)];
    return "<span style='background:{$s['bg']};color:{$s['color']};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;'>{$s['label']}</span>";
}

// ==================== ADD MANUAL ORDER ====================
// ✅ MUST be before header.php include — uses header() redirect
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_manual_order'])) {
    $order_id       = getNextOrderId($pdo);
    $user_id        = (int)($_POST['user_id'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'Cash on Delivery';
    $customer_name  = trim($_POST['customer_name'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $full_address   = trim($_POST['address'] ?? '');
    $province       = trim($_POST['province'] ?? '');
    $city           = trim($_POST['city'] ?? '');
    $barangay       = trim($_POST['barangay'] ?? '');

    if ($user_id > 0) {
        $su = $pdo->prepare("SELECT name, phone, email FROM users WHERE id = ?");
        $su->execute([$user_id]);
        if ($u = $su->fetch(PDO::FETCH_ASSOC)) {
            $customer_name = $customer_name ?: $u['name'];
            $phone         = $phone         ?: $u['phone'];
            $email         = $email         ?: $u['email'];
        }
    }

    $total_amount  = 0;
    $items_summary = [];
    $product_ids   = $_POST['product_id'] ?? [];
    $variant_ids   = $_POST['variant_id'] ?? [];
    $quantities    = $_POST['quantity']   ?? [];

    try {
        foreach ($product_ids as $i => $pid) {
            $qty = (int)($quantities[$i] ?? 0);
            $vid = (int)($variant_ids[$i] ?? 0);
            if ($qty <= 0 || !$pid) continue;

            if ($vid > 0) {
                $s = $pdo->prepare("SELECT p.name, p.image, v.price, v.sku FROM products p JOIN product_variants v ON v.product_id = p.id WHERE p.id = ? AND v.id = ?");
                $s->execute([$pid, $vid]);
                $item  = $s->fetch(PDO::FETCH_ASSOC);
                $price = (float)($item['price'] ?? 0);
                $name  = ($item['name'] ?? 'Product') . ' (' . ($item['sku'] ?? '') . ')';
                $image = $item['image'] ?? '';
            } else {
                $s = $pdo->prepare("SELECT name, price, image FROM products WHERE id = ?");
                $s->execute([$pid]);
                $item  = $s->fetch(PDO::FETCH_ASSOC);
                $price = (float)($item['price'] ?? 0);
                $name  = $item['name'] ?? 'Product';
                $image = $item['image'] ?? '';
            }

            if ($price <= 0) continue;
            $total_amount  += $price * $qty;
            $items_summary[] = ['name' => $name, 'qty' => $qty, 'price' => $price, 'image' => $image];
        }

        $stmt = $pdo->prepare("
            INSERT INTO orders (order_id, user_id, total_amount, items_summary, status,
                address, payment_method, payment_status, customer_name, phone, email,
                province, city, barangay, created_at)
            VALUES (?, ?, ?, ?, 'Processing', ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $order_id, $user_id, $total_amount,
            json_encode($items_summary, JSON_UNESCAPED_UNICODE),
            $full_address, $payment_method,
            $customer_name, $phone, $email,
            $province, $city, $barangay,
        ]);

        header("Location: orders.php?success=order_added");
        exit;

    } catch (Exception $e) {
        $error = "Failed to create order: " . $e->getMessage();
    }
}

// ==================== BULK STATUS UPDATE ====================
// ✅ MUST be before header.php include — uses header() redirect
if (isset($_POST['bulk_status_update']) && !empty($_POST['order_ids'])) {
    $ids    = array_map('intval', $_POST['order_ids']);
    $ph     = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$_POST['bulk_status']], $ids);
    $pdo->prepare("UPDATE orders SET status = ? WHERE id IN ($ph)")->execute($params);
    header("Location: orders.php?success=status_updated&" . http_build_query(array_filter([
        'search'   => $_GET['search']   ?? '',
        'page'     => $_GET['page']     ?? 1,
        'per_page' => $_GET['per_page'] ?? 10,
    ])));
    exit;
}

// ==================== FETCH WITH PAGINATION + SEARCH ====================
$per_page_options = [10, 25, 50, 100];
$per_page      = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $per_page_options)
                    ? (int)$_GET['per_page'] : 10;
$page          = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$search        = isset($_GET['search'])        ? trim($_GET['search'])        : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$offset        = ($page - 1) * $per_page;

$conditions = [];
$params     = [];

if ($search !== '') {
    $conditions[] = "(o.order_id LIKE :search OR o.customer_name LIKE :search OR o.phone LIKE :search OR o.email LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($status_filter !== '') {
    $conditions[] = "o.status = :status";
    $params[':status'] = $status_filter;
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$cStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o $where");
$cStmt->execute($params);
$total_records = (int)$cStmt->fetchColumn();
$total_pages   = $total_records > 0 ? max(1, (int)ceil($total_records / $per_page)) : 1;
$page          = min($page, $total_pages);
$offset        = ($page - 1) * $per_page;

$sql  = "SELECT o.* FROM orders o $where ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$allProducts = $pdo->query("SELECT id, name, price, is_variable FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

function buildQuery($overrides = []) {
    $base = array_filter([
        'search'        => $_GET['search']        ?? '',
        'per_page'      => $_GET['per_page']      ?? 10,
        'status_filter' => $_GET['status_filter'] ?? '',
        'page'          => $_GET['page']           ?? 1,
    ]);
    return '?' . http_build_query(array_merge($base, $overrides));
}

// ✅ All redirects done — safe to output HTML now
require_once 'includes/header.php';
?>

<div class="orders-wrap">

    <form id="exportForm" method="POST" action="export_orders.php" target="_blank" style="display:none;">
        <input type="hidden" name="order_ids" id="exportOrderIds">
    </form>

    <!-- Page header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <div class="page-title">Orders Management</div>
            <div class="page-sub"><?= number_format($total_records) ?> total orders</div>
        </div>
        <div style="display:flex;gap:10px;">
            <button class="btn-outline-c" onclick="exportExcel()">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
            <button class="btn-primary-c" data-bs-toggle="modal" data-bs-target="#addOrderModal">
                <i class="fas fa-plus"></i> New Order
            </button>
        </div>
    </div>

    <!-- Stat cards -->
    <?php
    $statStmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
    $stats = [];
    while ($r = $statStmt->fetch(PDO::FETCH_ASSOC)) $stats[strtolower($r['status'])] = $r['cnt'];
    $totalOrders = array_sum($stats);
    ?>
    <div class="stat-cards">
        <div class="stat-card">
            <div class="stat-num"><?= number_format($totalOrders) ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#92400E"><?= number_format($stats['processing'] ?? 0) ?></div>
            <div class="stat-label">Processing</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#1E40AF"><?= number_format($stats['shipped'] ?? 0) ?></div>
            <div class="stat-label">Shipped</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#065F46"><?= number_format($stats['delivered'] ?? 0) ?></div>
            <div class="stat-label">Delivered</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#991B1B"><?= number_format($stats['cancelled'] ?? 0) ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert-s alert-success-c">
            <?= $_GET['success'] === 'order_added' ? '✅ New order created successfully!' : '✅ Status updated successfully!' ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert-s alert-danger-c">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Main table card -->
    <form method="POST" id="bulkForm">
        <input type="hidden" name="bulk_status_update" value="1">

        <div class="tbl-card">

            <!-- Toolbar -->
            <div class="tbl-header">

                <form method="GET" id="searchForm" style="display:flex;gap:8px;flex:1;min-width:200px;">
                    <input type="hidden" name="per_page" value="<?= $per_page ?>">
                    <input type="hidden" name="status_filter" value="<?= htmlspecialchars($status_filter) ?>">
                    <input type="hidden" name="page" value="1">
                    <input class="inp" style="flex:1;" type="text" name="search"
                           placeholder="Search order ID, customer, phone, email…"
                           value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn-primary-c"><i class="fas fa-search"></i></button>
                    <?php if ($search || $status_filter): ?>
                        <a href="orders.php?per_page=<?= $per_page ?>" class="btn-outline-c">Clear</a>
                    <?php endif; ?>
                </form>

                <form method="GET" id="filterForm" style="display:flex;gap:8px;align-items:center;">
                    <input type="hidden" name="per_page" value="<?= $per_page ?>">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="page" value="1">
                    <select class="inp" name="status_filter" onchange="document.getElementById('filterForm').submit()">
                        <option value="">All Statuses</option>
                        <?php foreach (['Processing','Confirmed','Shipped','Out for Delivery','Delivered','Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <form method="GET" id="perPageForm" style="display:flex;gap:6px;align-items:center;">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="status_filter" value="<?= htmlspecialchars($status_filter) ?>">
                    <input type="hidden" name="page" value="1">
                    <span style="font-size:13px;color:#6B7280;white-space:nowrap;">Show</span>
                    <select class="inp" name="per_page" onchange="document.getElementById('perPageForm').submit()">
                        <?php foreach ($per_page_options as $opt): ?>
                            <option value="<?= $opt ?>" <?= $per_page == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <select class="inp" name="bulk_status">
                        <?php foreach (['Processing','Confirmed','Shipped','Out for Delivery','Delivered','Cancelled'] as $s): ?>
                            <option><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-outline-c">Apply to Selected</button>
                    <button type="button" class="btn-outline-c" onclick="bulkPrintWaybills()">
                        <i class="fas fa-print"></i> Print Selected
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="tbl-wrap">
                <table class="orders-tbl">
                    <thead>
                        <tr>
                            <th style="width:36px;"><input type="checkbox" onclick="toggleAll(this)" title="Select all"></th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="11" style="text-align:center;padding:60px;color:#9CA3AF;">
                                    <i class="fas fa-inbox" style="font-size:40px;display:block;margin-bottom:12px;"></i>
                                    No orders found<?= $search ? ' for "' . htmlspecialchars($search) . '"' : '' ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><input type="checkbox" name="order_ids[]" value="<?= $order['id'] ?>" class="order-checkbox"></td>

                                    <td>
                                        <span style="font-weight:700;color:#111827;">
                                            <?= htmlspecialchars($order['order_id'] ?? 'N/A') ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div style="font-weight:600;color:#111827;">
                                            <?= htmlspecialchars($order['customer_name'] ?? '—') ?>
                                        </div>
                                        <div style="font-size:11px;color:#9CA3AF;">
                                            <?= htmlspecialchars($order['email'] ?? '') ?>
                                        </div>
                                    </td>

                                    <td class="muted"><?= htmlspecialchars($order['phone'] ?? '—') ?></td>

                                    <td style="max-width:180px;">
                                        <?php
                                        $addr = trim($order['full_address'] ?? $order['address'] ?? '');
                                        if (!$addr) {
                                            $parts = array_filter([$order['barangay'], $order['city'], $order['province']]);
                                            $addr  = implode(', ', $parts);
                                        }
                                        ?>
                                        <span style="font-size:12px;color:#6B7280;" title="<?= htmlspecialchars($addr) ?>">
                                            <?= htmlspecialchars(mb_strimwidth($addr, 0, 60, '…')) ?>
                                        </span>
                                    </td>

                                    <td style="max-width:200px;">
                                        <?php
                                        $items = json_decode($order['items_summary'], true);
                                        if (is_array($items)) {
                                            foreach (array_slice($items, 0, 3) as $it) {
                                                echo '<span class="item-chip">' . htmlspecialchars($it['name'] ?? '') . ' ×' . ($it['qty'] ?? 1) . '</span><br>';
                                            }
                                            if (count($items) > 3) {
                                                echo '<span style="font-size:11px;color:#9CA3AF;">+' . (count($items) - 3) . ' more</span>';
                                            }
                                        } else {
                                            echo '<span class="muted">—</span>';
                                        }
                                        ?>
                                    </td>

                                    <td style="font-weight:700;color:#111827;white-space:nowrap;">
                                        ₱<?= number_format((float)($order['total_amount'] ?? 0), 2) ?>
                                    </td>

                                    <td class="muted" style="white-space:nowrap;">
                                        <?= htmlspecialchars($order['payment_method'] ?? '—') ?>
                                    </td>

                                    <td><?= statusBadge($order['status'] ?? 'Processing') ?></td>

                                    <td class="muted" style="white-space:nowrap;font-size:12px;">
                                        <?= date('M d, Y', strtotime($order['created_at'])) ?><br>
                                        <span style="color:#D1D5DB;"><?= date('h:i A', strtotime($order['created_at'])) ?></span>
                                    </td>

                                    <td>
                                        <button class="icon-btn" onclick="printWaybill(<?= $order['id'] ?>)" title="Print waybill">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="tbl-footer">
                <div style="font-size:13px;color:#6B7280;">
                    Showing <strong><?= min($offset + 1, $total_records) ?></strong>–<strong><?= min($offset + $per_page, $total_records) ?></strong>
                    of <strong><?= number_format($total_records) ?></strong> orders
                </div>

                <nav class="pag">
                    <?php if ($page > 1): ?>
                        <a href="<?= buildQuery(['page' => $page - 1]) ?>">
                            <i class="fas fa-chevron-left" style="font-size:10px;"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    $window = 2;
                    $pages  = [];
                    for ($p = 1; $p <= $total_pages; $p++) {
                        if ($p == 1 || $p == $total_pages || abs($p - $page) <= $window) $pages[] = $p;
                    }
                    $prev = null;
                    foreach ($pages as $p):
                        if ($prev !== null && $p - $prev > 1): ?>
                            <span class="dots">…</span>
                        <?php endif;
                        if ($p == $page): ?>
                            <span class="active"><?= $p ?></span>
                        <?php else: ?>
                            <a href="<?= buildQuery(['page' => $p]) ?>"><?= $p ?></a>
                        <?php endif;
                        $prev = $p;
                    endforeach; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?= buildQuery(['page' => $page + 1]) ?>">
                            <i class="fas fa-chevron-right" style="font-size:10px;"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </form>
</div>


<!-- ADD MANUAL ORDER MODAL -->
<div class="modal fade" id="addOrderModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content modal-card">
            <div class="modal-hdr">
                <h5><i class="fas fa-plus-circle me-2" style="color:#6C3FC8;"></i>Create New Manual Order</h5>
            </div>
            <form method="POST">
                <input type="hidden" name="add_manual_order" value="1">
                <div class="modal-bdy">
                    <div class="row g-3">

                        <div class="col-12">
                            <div style="font-size:12px;font-weight:700;color:#6C3FC8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">
                                Customer Information
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-lbl">User ID (optional)</label>
                            <input class="form-inp" type="number" name="user_id" id="modalUserId"
                                   min="0" placeholder="e.g. 12" onchange="autoFillUser(this.value)">
                        </div>
                        <div class="col-md-3">
                            <label class="form-lbl">Customer Name</label>
                            <input class="form-inp" type="text" name="customer_name" id="modalName"
                                   placeholder="Full name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-lbl">Phone</label>
                            <input class="form-inp" type="text" name="phone" id="modalPhone"
                                   placeholder="09XXXXXXXXX">
                        </div>
                        <div class="col-md-3">
                            <label class="form-lbl">Email</label>
                            <input class="form-inp" type="email" name="email" id="modalEmail"
                                   placeholder="email@example.com">
                        </div>

                        <div class="col-12" style="margin-top:6px;">
                            <div style="font-size:12px;font-weight:700;color:#6C3FC8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">
                                Delivery Address
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-lbl">Full Address / Street</label>
                            <input class="form-inp" type="text" name="address"
                                   placeholder="House No., Street, Barangay, City, Province" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-lbl">Province</label>
                            <input class="form-inp" type="text" name="province" placeholder="Province">
                        </div>
                        <div class="col-md-2">
                            <label class="form-lbl">City</label>
                            <input class="form-inp" type="text" name="city" placeholder="City">
                        </div>
                        <div class="col-md-2">
                            <label class="form-lbl">Barangay</label>
                            <input class="form-inp" type="text" name="barangay" placeholder="Barangay">
                        </div>

                        <div class="col-md-4" style="margin-top:6px;">
                            <label class="form-lbl">Payment Method</label>
                            <select class="form-inp" name="payment_method">
                                <option>Cash on Delivery</option>
                                <option>GCash</option>
                                <option>Maya</option>
                                <option>Bank Transfer</option>
                            </select>
                        </div>

                        <div class="col-12" style="margin-top:6px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                <div style="font-size:12px;font-weight:700;color:#6C3FC8;text-transform:uppercase;letter-spacing:.5px;">
                                    Products
                                </div>
                                <button type="button" class="btn-primary-c"
                                        onclick="addProductRow()" style="height:32px;font-size:12px;">
                                    <i class="fas fa-plus"></i> Add Product
                                </button>
                            </div>
                            <div id="productItems"></div>

                            <div style="display:flex;justify-content:flex-end;margin-top:12px;">
                                <div style="background:#F3F0FF;border-radius:10px;padding:12px 20px;text-align:right;">
                                    <div style="font-size:12px;color:#6B7280;">Order Total</div>
                                    <div style="font-size:22px;font-weight:700;color:#6C3FC8;">
                                        ₱<span id="totalDisplay">0.00</span>
                                    </div>
                                    <input type="hidden" name="total_amount" id="totalPrice" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-ftr">
                    <button type="button" class="btn-outline-c" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-c">
                        <i class="fas fa-check me-1"></i> Create Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
function toggleAll(src) {
    document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = src.checked);
}

function printWaybill(id) {
    window.open('print_waybill.php?id=' + id, '_blank');
}

function bulkPrintWaybills() {
    const checked = [...document.querySelectorAll('.order-checkbox:checked')];
    if (!checked.length) { alert('Please select at least one order.'); return; }
    window.open('print_waybills.php?ids=' + checked.map(cb => cb.value).join(','), '_blank');
}

function exportExcel() {
    const checked = [...document.querySelectorAll('.order-checkbox:checked')];
    if (!checked.length) { alert('Please select at least one order to export.'); return; }
    document.getElementById('exportOrderIds').value = checked.map(cb => cb.value).join(',');
    document.getElementById('exportForm').submit();
}

async function autoFillUser(userId) {
    if (!userId) return;
    try {
        const res  = await fetch('get_user_info.php?id=' + userId);
        const data = await res.json();
        if (data.name)  document.getElementById('modalName').value  = data.name;
        if (data.phone) document.getElementById('modalPhone').value = data.phone;
        if (data.email) document.getElementById('modalEmail').value = data.email;
    } catch(e) {}
}

function addProductRow() {
    const container = document.getElementById('productItems');
    const idx       = Date.now();

    const productsHtml = <?php
        $opts = '<option value="">— Select Product —</option>';
        foreach ($allProducts as $p) {
            $label = htmlspecialchars($p['name']) . " — ₱" . number_format($p['price'], 2);
            if ($p['is_variable']) $label .= " (Variable)";
            $opts .= "<option value='{$p['id']}' data-price='{$p['price']}' data-variable='{$p['is_variable']}'>{$label}</option>";
        }
        echo json_encode($opts);
    ?>;

    const row = document.createElement('div');
    row.className = 'product-row-card';
    row.id = 'row_' + idx;
    row.innerHTML = `
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-lbl">Product</label>
                <select name="product_id[]" class="form-inp"
                        onchange="loadVariants(this, '${idx}'); recalc();">
                    ${productsHtml}
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-lbl">Variant</label>
                <select name="variant_id[]" class="form-inp" id="variant_${idx}" onchange="recalc()">
                    <option value="">No variant</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-lbl">Quantity</label>
                <input type="number" name="quantity[]" class="form-inp"
                       min="1" value="1" onchange="recalc()">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn-danger-c w-100"
                        onclick="document.getElementById('row_${idx}').remove(); recalc();">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
        </div>
    `;
    container.appendChild(row);
    recalc();
}

async function loadVariants(select, idx) {
    const varSel = document.getElementById('variant_' + idx);
    varSel.innerHTML = '<option value="">No variant</option>';
    const pid   = select.value;
    const isVar = select.options[select.selectedIndex]?.dataset?.variable;
    if (!pid || isVar == '0') { recalc(); return; }

    try {
        const res  = await fetch('get_product_variants.php?product_id=' + pid);
        const data = await res.json();
        if (Array.isArray(data)) {
            data.forEach(v => {
                const opt      = document.createElement('option');
                opt.value      = v.id;
                opt.dataset.price = v.price;
                opt.textContent = (v.sku || 'Variant') + ' — ₱' + parseFloat(v.price).toFixed(2);
                varSel.appendChild(opt);
            });
        }
    } catch(e) {}
    recalc();
}

function recalc() {
    let total = 0;
    document.querySelectorAll('.product-row-card').forEach(row => {
        const pSel = row.querySelector('select[name="product_id[]"]');
        const vSel = row.querySelector('select[name="variant_id[]"]');
        const qty  = parseInt(row.querySelector('input[name="quantity[]"]')?.value) || 0;
        if (!pSel?.value) return;
        let price = 0;
        if (vSel?.value) {
            price = parseFloat(vSel.options[vSel.selectedIndex]?.dataset?.price || 0);
        } else {
            price = parseFloat(pSel.options[pSel.selectedIndex]?.dataset?.price || 0);
        }
        total += price * qty;
    });
    document.getElementById('totalPrice').value       = total.toFixed(2);
    document.getElementById('totalDisplay').textContent = total.toFixed(2);
}

document.getElementById('addOrderModal')?.addEventListener('show.bs.modal', () => {
    document.getElementById('productItems').innerHTML    = '';
    document.getElementById('totalPrice').value          = '0';
    document.getElementById('totalDisplay').textContent  = '0.00';
    addProductRow();
});
</script>

<?php require_once 'includes/footer.php'; ?>