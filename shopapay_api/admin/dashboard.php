<?php
// admin/dashboard.php
ob_start();
require_once '../config/database.php';

// Stats
try { $totalProducts   = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(); } catch(Exception $e){ $totalProducts=0; }
try { $totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(); } catch(Exception $e){ $totalCategories=0; }
try { $totalOrders     = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0; } catch(Exception $e){ $totalOrders=0; }
try { $lowStock        = $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 10")->fetchColumn(); } catch(Exception $e){ $lowStock=0; }
try { $totalRevenue    = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled'")->fetchColumn(); } catch(Exception $e){ $totalRevenue=0; }
try { $todayRevenue    = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'")->fetchColumn(); } catch(Exception $e){ $todayRevenue=0; }

// Order status breakdown
$statusMap = [];
try {
    $statusStmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
    while ($r = $statusStmt->fetch()) $statusMap[strtolower($r['status'])] = $r['cnt'];
} catch(Exception $e){}

// Recent orders
try { $recentOrders = $pdo->query("SELECT order_id, customer_name, total_amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 7")->fetchAll(); }
catch(Exception $e){ $recentOrders=[]; }

// Recent products
try { $recentProducts = $pdo->query("SELECT id, name, price, stock, image FROM products ORDER BY id DESC LIMIT 5")->fetchAll(); }
catch(Exception $e){ $recentProducts=[]; }

// Monthly revenue
$monthlyData = [];
try {
    $monthlyData = $pdo->query("
        SELECT DATE_FORMAT(created_at,'%b') as month,
               COALESCE(SUM(total_amount),0) as revenue
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          AND status != 'cancelled'
        GROUP BY DATE_FORMAT(created_at,'%Y-%m')
        ORDER BY created_at ASC
    ")->fetchAll();
} catch(Exception $e){}

function statusBadge($status) {
    $map = [
        'delivered'        => ['#D1FAE5','#065F46'],
        'shipped'          => ['#DBEAFE','#1E40AF'],
        'out for delivery' => ['#E0E7FF','#3730A3'],
        'confirmed'        => ['#D1FAE5','#065F46'],
        'cancelled'        => ['#FEE2E2','#991B1B'],
        'processing'       => ['#FEF3C7','#92400E'],
    ];
    $key = strtolower(trim($status));
    [$bg, $color] = $map[$key] ?? ['#F3F4F6','#374151'];
    $label = ucwords($key);
    return "<span style='background:{$bg};color:{$color};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap'>{$label}</span>";
}

include 'includes/header.php';
?>

<style>
/* ── Dashboard-specific styles ── */
.dash-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 1px 6px rgba(0,0,0,.06);
    overflow: hidden;
    height: 100%;
}
.dash-card-hdr {
    padding: 14px 18px;
    border-bottom: 1px solid #F3F4F6;
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px; flex-wrap: wrap;
}
.dash-card-hdr h6 { font-weight: 700; font-size: 13px; color: #111827; margin: 0; }
.view-all {
    font-size: 12px; color: #6C3FC8; text-decoration: none;
    font-weight: 600; padding: 4px 12px;
    border-radius: 8px; background: #f3f0ff;
    transition: background .15s; white-space: nowrap;
}
.view-all:hover { background: #e9e3fb; }

/* ── Status pills ── */
.status-pills { display: flex; flex-wrap: wrap; gap: 8px; padding: 16px; }
.status-pill {
    flex: 1; min-width: 80px;
    background: #F9FAFB; border-radius: 10px;
    padding: 10px 10px; text-align: center;
    border: 1px solid #F3F4F6;
}
.snum   { font-size: 20px; font-weight: 700; color: #111827; line-height: 1; }
.slabel { font-size: 10px; color: #9CA3AF; margin-top: 3px; }

/* ── Chart ── */
.chart-wrap  { padding: 18px 16px 10px; }
.chart-bars  { display: flex; align-items: flex-end; gap: 8px; height: 110px; }
.chart-bar-col {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; gap: 4px; height: 100%; justify-content: flex-end;
}
.chart-bar {
    width: 100%; border-radius: 6px 6px 0 0;
    background: linear-gradient(180deg, #9C6FE4, #6C3FC8);
    min-height: 4px; transition: opacity .2s;
}
.chart-bar:hover { opacity: .75; }
.chart-label { font-size: 10px; color: #9CA3AF; }
.chart-val   { font-size: 9px; color: #6C3FC8; font-weight: 600; }

/* ── Stat icon card ── */
.stat-icon-card {
    display: flex; align-items: center; gap: 14px;
}
.stat-icon {
    width: 48px; height: 48px; min-width: 48px;
    border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.stat-sub { font-size: 11px; color: #6C3FC8; margin-top: 4px; font-weight: 600; }

/* ── Dashboard grid system ── */
.dash-row {
    display: grid;
    gap: 14px;
    margin-bottom: 14px;
}
.dash-row-2   { grid-template-columns: 1fr 1fr; }
.dash-row-2-1 { grid-template-columns: 1.5fr 1fr; }

/* ── Product thumb ── */
.prod-thumb {
    width: 38px; height: 38px;
    border-radius: 9px; object-fit: cover;
    background: #F3F4F6;
    display: flex; align-items: center; justify-content: center;
    color: #D1D5DB; font-size: 14px;
}
img.prod-thumb { display: block; }

/* ── Responsive ── */
@media (max-width: 900px) {
    .dash-row-2,
    .dash-row-2-1 { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .stat-cards { grid-template-columns: repeat(2, 1fr); }
    .stat-icon-card { gap: 10px; }
    .stat-icon { width: 40px; height: 40px; font-size: 16px; border-radius: 10px; }
    .stat-num  { font-size: 18px; }
    .snum      { font-size: 18px; }
    .status-pills { gap: 6px; padding: 12px; }
    .status-pill  { min-width: 70px; padding: 8px 6px; }

    /* Tables scroll on mobile */
    .dash-table { font-size: 12px; }
    .dash-table td, .dash-table th { padding: 9px 10px; }

    /* Hide less critical columns on mobile */
    .dash-table th:nth-child(5),
    .dash-table td:nth-child(5) { display: none; }

    .chart-bars  { height: 80px; gap: 5px; }
    .chart-label { font-size: 9px; }
}

@media (max-width: 480px) {
    .stat-cards { grid-template-columns: 1fr 1fr; }
    .dash-card-hdr { padding: 12px 14px; }
    .status-pill { min-width: 60px; }
    .snum { font-size: 16px; }
}
</style>

<div class="orders-wrap">

    <!-- Page header -->
    <div style="display:flex; justify-content:space-between; align-items:center;
                margin-bottom:22px; flex-wrap:wrap; gap:10px;">
        <div>
            <div class="page-title">Dashboard</div>
            <div class="page-sub"><?= date('l, F d, Y') ?></div>
        </div>
        <div style="background:#fff; border-radius:10px; padding:8px 16px;
                    box-shadow:0 1px 4px rgba(0,0,0,.06); font-size:12px; color:#6B7280;
                    display:flex; align-items:center; gap:6px; flex-shrink:0;">
            <i class="fas fa-circle" style="color:#22C55E; font-size:7px;"></i>
            System Online
        </div>
    </div>

    <!-- ── Stat Cards ── -->
    <div class="stat-cards" style="margin-bottom:18px;">
        <?php
        $cards = [
            ['icon'=>'fa-peso-sign', 'bg'=>'#EDE9FE', 'color'=>'#6C3FC8',
             'num'=>'₱'.number_format($totalRevenue,0),
             'label'=>'Total Revenue',
             'sub'=>'+₱'.number_format($todayRevenue,0).' today'],
            ['icon'=>'fa-bag-shopping', 'bg'=>'#DBEAFE', 'color'=>'#1E40AF',
             'num'=>number_format($totalOrders),
             'label'=>'Total Orders',
             'sub'=>($statusMap['processing']??0).' processing'],
            ['icon'=>'fa-box', 'bg'=>'#D1FAE5', 'color'=>'#065F46',
             'num'=>number_format($totalProducts),
             'label'=>'Products',
             'sub'=>number_format($totalCategories).' categories'],
            ['icon'=>'fa-triangle-exclamation', 'bg'=>'#FEF3C7', 'color'=>'#92400E',
             'num'=>number_format($lowStock),
             'label'=>'Low Stock',
             'sub'=>$lowStock > 0 ? 'Needs attention' : 'All good',
             'num_color'=> $lowStock > 0 ? '#EF4444' : '#111827'],
        ];
        foreach ($cards as $c):
        ?>
        <div class="stat-card">
            <div class="stat-icon-card">
                <div class="stat-icon" style="background:<?= $c['bg'] ?>;">
                    <i class="fas <?= $c['icon'] ?>" style="color:<?= $c['color'] ?>;"></i>
                </div>
                <div>
                    <div class="stat-num" style="color:<?= $c['num_color'] ?? '#111827' ?>">
                        <?= $c['num'] ?>
                    </div>
                    <div class="stat-label"><?= $c['label'] ?></div>
                    <div class="stat-sub" style="color:<?= $c['color'] ?>"><?= $c['sub'] ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Row: Chart + Order Status ── -->
    <div class="dash-row dash-row-2" style="margin-bottom:14px;">

        <!-- Revenue Chart -->
        <div class="dash-card">
            <div class="dash-card-hdr">
                <h6><i class="fas fa-chart-bar me-2" style="color:#6C3FC8;"></i>Revenue — Last 6 Months</h6>
            </div>
            <div class="chart-wrap">
                <?php $maxRev = max(array_column($monthlyData, 'revenue') ?: [1]); ?>
                <div class="chart-bars">
                    <?php if (empty($monthlyData)): ?>
                        <div style="width:100%;text-align:center;color:#9CA3AF;font-size:12px;padding:20px 0">
                            No revenue data yet
                        </div>
                    <?php else: ?>
                        <?php foreach ($monthlyData as $m):
                            $pct = $maxRev > 0 ? ($m['revenue'] / $maxRev * 100) : 0;
                        ?>
                        <div class="chart-bar-col">
                            <div class="chart-val">₱<?= number_format($m['revenue']/1000, 1) ?>k</div>
                            <div class="chart-bar" style="height:<?= max($pct, 4) ?>%;"></div>
                            <div class="chart-label"><?= $m['month'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Order Status -->
        <div class="dash-card">
            <div class="dash-card-hdr">
                <h6><i class="fas fa-circle-half-stroke me-2" style="color:#6C3FC8;"></i>Order Status</h6>
                <a href="/store/shopapay_api/admin/orders.php" class="view-all">View All</a>
            </div>
            <div class="status-pills">
                <?php foreach ([
                    ['processing',      'Processing',   '#F59E0B'],
                    ['confirmed',       'Confirmed',    '#22C55E'],
                    ['shipped',         'Shipped',      '#3B82F6'],
                    ['out for delivery','Out Delivery', '#8B5CF6'],
                    ['delivered',       'Delivered',    '#10B981'],
                    ['cancelled',       'Cancelled',    '#EF4444'],
                ] as [$key, $label, $color]): ?>
                <div class="status-pill">
                    <div class="snum" style="color:<?= $color ?>"><?= $statusMap[$key] ?? 0 ?></div>
                    <div class="slabel"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── Row: Recent Orders + Recent Products ── -->
    <div class="dash-row dash-row-2-1">

        <!-- Recent Orders -->
        <div class="dash-card">
            <div class="dash-card-hdr">
                <h6><i class="fas fa-clock-rotate-left me-2" style="color:#6C3FC8;"></i>Recent Orders</h6>
                <a href="/store/shopapay_api/admin/orders.php" class="view-all">View All</a>
            </div>
            <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                <table class="dash-table" style="min-width:500px;">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentOrders)): ?>
                            <tr><td colspan="5" style="text-align:center;padding:40px;color:#9CA3AF;">
                                No orders yet
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td style="font-weight:700;color:#111827;font-size:12px;">
                                    <?= htmlspecialchars($o['order_id']) ?>
                                </td>
                                <td style="font-size:12px; max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?= htmlspecialchars($o['customer_name']) ?>
                                </td>
                                <td style="font-weight:600;white-space:nowrap;">
                                    ₱<?= number_format($o['total_amount'],2) ?>
                                </td>
                                <td><?= statusBadge($o['status']) ?></td>
                                <td style="font-size:11px;color:#9CA3AF;white-space:nowrap;">
                                    <?= date('M d', strtotime($o['created_at'])) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Products -->
        <div class="dash-card">
            <div class="dash-card-hdr">
                <h6><i class="fas fa-box me-2" style="color:#6C3FC8;"></i>Recent Products</h6>
                <a href="/store/shopapay_api/admin/product-list.php" class="view-all">View All</a>
            </div>
            <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                <table class="dash-table" style="min-width:320px;">
                    <thead>
                        <tr>
                            <th style="width:46px;"></th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentProducts)): ?>
                            <tr><td colspan="4" style="text-align:center;padding:40px;color:#9CA3AF;">
                                No products yet
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($recentProducts as $p): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($p['image'])): ?>
                                        <img src="../uploads/<?= htmlspecialchars($p['image']) ?>"
                                             class="prod-thumb" alt="" style="width:38px;height:38px;border-radius:9px;object-fit:cover;">
                                    <?php else: ?>
                                        <div class="prod-thumb" style="width:38px;height:38px;border-radius:9px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;color:#D1D5DB;">
                                            <i class="fas fa-image" style="font-size:13px;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:12px;font-weight:500;color:#111827;max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?= htmlspecialchars($p['name']) ?>
                                </td>
                                <td style="font-size:12px;font-weight:600;white-space:nowrap;">
                                    ₱<?= number_format((float)$p['price'],2) ?>
                                </td>
                                <td>
                                    <span style="background:<?= (int)$p['stock']<10?'#FEE2E2':'#D1FAE5' ?>;
                                                 color:<?= (int)$p['stock']<10?'#991B1B':'#065F46' ?>;
                                                 padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;">
                                        <?= (int)($p['stock']??0) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /orders-wrap -->

<?php require_once 'includes/footer.php'; ?>