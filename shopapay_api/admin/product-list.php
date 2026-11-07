<?php
// admin/product-list.php
require_once '../config/database.php';
include 'includes/header.php';

// Pagination & Search
$per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'],[10,25,50,100]) ? (int)$_GET['per_page'] : 10;
$page     = max(1, (int)($_GET['page'] ?? 1));
$search   = trim($_GET['search'] ?? '');
$offset   = ($page - 1) * $per_page;

$where  = $search ? "WHERE p.name LIKE :s OR c.name LIKE :s" : '';
$params = $search ? [':s' => "%$search%"] : [];

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id = c.id $where");
$countStmt->execute($params);
$total   = (int)$countStmt->fetchColumn();
$pages   = max(1, ceil($total / $per_page));
$page    = min($page, $pages);
$offset  = ($page - 1) * $per_page;

$stmt = $pdo->prepare("SELECT p.id, p.name, p.price, p.stock, p.is_variable, p.image,
                              COALESCE(c.name,'Uncategorized') as category_name
                       FROM products p LEFT JOIN categories c ON p.category_id = c.id
                       $where ORDER BY p.id DESC LIMIT :lim OFFSET :off");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset,   PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();
?>

<style>
.page-hdr   { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
.page-title { font-size:22px; font-weight:700; color:#111827; margin:0; }
.page-sub   { font-size:13px; color:#9CA3AF; margin-top:2px; }
.btn-add    { background:var(--rose, #C4826A); color:#fff; border:none; border-radius:10px; padding:10px 20px; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; text-decoration:none; transition:background .15s; }
.btn-add:hover { background:var(--rose-mid, #B06A52); color:#fff; }
.tbl-card   { background:#fff; border-radius:16px; box-shadow:0 1px 6px rgba(0,0,0,.06); overflow:hidden; }
.tbl-toolbar{ padding:16px 20px; border-bottom:1px solid #F3F4F6; display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
.inp        { height:36px; border:1.5px solid #E5E7EB; border-radius:8px; padding:0 12px; font-size:13px; color:#374151; outline:none; font-family:inherit; }
.inp:focus  { border-color:var(--rose, #C4826A); }
select.inp  { padding:0 8px; cursor:pointer; }
.btn-search { background:var(--rose, #C4826A); color:#fff; border:none; border-radius:8px; height:36px; padding:0 16px; font-size:13px; cursor:pointer; font-family:inherit; }
.btn-clear  { background:#fff; color:#374151; border:1.5px solid #E5E7EB; border-radius:8px; height:36px; padding:0 14px; font-size:13px; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; }
.dash-table { width:100%; border-collapse:collapse; font-size:13px; }
.dash-table thead th { background:#F9FAFB; color:#9CA3AF; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; padding:11px 16px; border-bottom:1px solid #F3F4F6; white-space:nowrap; }
.dash-table tbody tr { border-bottom:1px solid #F3F4F6; transition:background .12s; }
.dash-table tbody tr:last-child { border-bottom:none; }
.dash-table tbody tr:hover { background:#FAFAFA; }
.dash-table td { padding:12px 16px; color:#374151; vertical-align:middle; }
.prod-img   { width:42px; height:42px; border-radius:10px; object-fit:cover; display:block; border: 1px solid var(--mist, #E5D8CF); }
.prod-img-ph{ width:42px; height:42px; border-radius:10px; background:var(--ivory-deep, #F2EAE0); display:flex; align-items:center; justify-content:center; color:var(--ink-soft, #6B4C3B); font-size:16px; }
.badge-var  { background:#FEF3C7; color:#92400E; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.badge-sim  { background:#D1FAE5; color:#065F46; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.stock-ok   { background:#D1FAE5; color:#065F46; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:600; }
.stock-low  { background:#FEE2E2; color:#991B1B; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:600; }
.icon-btn   { width:30px; height:30px; border-radius:7px; border:1.5px solid #E5E7EB; background:#fff; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; color:#6B7280; font-size:12px; transition:all .15s; text-decoration:none; }
.icon-btn:hover { border-color:var(--rose, #C4826A); color:var(--rose, #C4826A); }
.icon-btn.danger:hover { border-color:#EF4444; color:#EF4444; }
.tbl-footer { padding:14px 20px; border-top:1px solid #F3F4F6; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.pag        { display:flex; gap:4px; }
.pag a, .pag span { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; font-size:13px; border:1.5px solid #E5E7EB; color:#374151; text-decoration:none; }
.pag a:hover { border-color:var(--rose, #C4826A); color:var(--rose, #C4826A); }
.pag span.active { background:var(--rose, #C4826A); color:#fff; border-color:var(--rose, #C4826A); font-weight:700; }
</style>

<div style="padding:28px 24px;">

    <div class="page-hdr">
        <div>
            <div class="page-title">Products</div>
            <div class="page-sub"><?= number_format($total) ?> total products</div>
        </div>
        <a href="/store/shopapay_api/admin/products/add-product.php" class="btn-add">
            <i class="fas fa-plus"></i> New Product
        </a>
    </div>

    <div class="tbl-card">
        <div class="tbl-toolbar">
            <form method="GET" style="display:flex;gap:8px;flex:1;min-width:200px;">
                <input type="hidden" name="per_page" value="<?= $per_page ?>">
                <input class="inp" style="flex:1;" type="text" name="search"
                       placeholder="Search product or category…" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                <?php if ($search): ?>
                    <a href="product-list.php" class="btn-clear">Clear</a>
                <?php endif; ?>
            </form>
            <form method="GET" style="display:flex;gap:6px;align-items:center;">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <span style="font-size:13px;color:#6B7280;white-space:nowrap;">Show</span>
                <select class="inp" name="per_page" onchange="this.form.submit()">
                    <?php foreach ([10,25,50,100] as $o): ?>
                        <option value="<?= $o ?>" <?= $per_page==$o?'selected':'' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div style="overflow-x:auto;">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th style="width:50px;"></th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Type</th>
                        <th style="width:80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:60px;color:#9CA3AF;">
                        <i class="fas fa-box" style="font-size:36px;display:block;margin-bottom:10px;"></i>
                        No products found<?= $search ? ' for "'.htmlspecialchars($search).'"' : '' ?>
                    </td></tr>
                <?php else: ?>
                <?php foreach ($products as $row):
                    if ($row['is_variable']) {
                        $v = $pdo->prepare("SELECT MIN(price) as mn, MAX(price) as mx FROM product_variants WHERE product_id=? AND is_active=1");
                        $v->execute([$row['id']]);
                        $vr = $v->fetch();
                        $priceText = '₱'.number_format((float)($vr['mn']??0),2).' – ₱'.number_format((float)($vr['mx']??0),2);
                    } else {
                        $priceText = '₱'.number_format((float)($row['price']??0),2);
                    }
                    
                    // Path fallback validation layer
                    $imgPath = '';
                    if (!empty($row['image'])) {
                        // Check if it's already an absolute storage URL or relative filename string
                        if (filter_var($row['image'], FILTER_VALIDATE_URL)) {
                            $imgPath = $row['image'];
                        } else {
                            // Uses absolute server routing path instead of fragile relative links
                            $imgPath = '/store/shopapay_api/uploads/' . ltrim($row['image'], '/');
                        }
                    }
                ?>
                <tr>
                    <td style="width: 50px; padding: 12px 8px;">
                        <?php 
                        $imageFilename = !empty($row['image']) ? trim($row['image']) : '';
                        $imgUrl = '';

                        if ($imageFilename) {
                            if (filter_var($imageFilename, FILTER_VALIDATE_URL)) {
                                $imgUrl = $imageFilename;
                            } else {
                                $cleanName = ltrim($imageFilename, '/');
                                
                                // Let's check where the file actually lives dynamically
                                // 1. Check relative to admin/ directory context
                                if (file_exists(__DIR__ . '/../uploads/' . $cleanName)) {
                                    $imgUrl = '../uploads/' . $cleanName;
                                } 
                                // 2. Check if nested inside an admin subfolder route context
                                elseif (file_exists(__DIR__ . '/../../uploads/' . $cleanName)) {
                                    $imgUrl = '../../uploads/' . $cleanName;
                                }
                                // 3. Fall back to absolute project root pathing string
                                else {
                                    $imgUrl = '/store/shopapay_api/uploads/' . $cleanName;
                                }
                            }
                        }
                        ?>
                        
                        <div class="prod-img-wrapper" style="position: relative; width: 42px; height: 42px;">
                            <?php if (!empty($imgUrl)): ?>
                                <img src="<?= htmlspecialchars($imgUrl) ?>" 
                                     class="prod-img" 
                                     alt="" 
                                     style="width: 42px; height: 42px; border-radius: 10px; object-fit: cover; display: block;"
                                     onload="this.style.opacity='1'; if(this.nextElementSibling) this.nextElementSibling.style.setProperty('display', 'none', 'important');"
                                     onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.setProperty('display', 'flex', 'important');">
                                
                                <div class="prod-img-ph" style="position: absolute; top: 0; left: 0; width: 42px; height: 42px; border-radius: 10px; background: #F3F4F6; display: flex; align-items: center; justify-content: center; color: #D1D5DB; font-size: 16px; pointer-events: none;"><i class="fas fa-image"></i></div>
                            <?php else: ?>
                                <div class="prod-img-ph" style="width: 42px; height: 42px; border-radius: 10px; background: #F3F4F6; display: flex; align-items: center; justify-content: center; color: #D1D5DB; font-size: 16px;"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($row['name']) ?></div>
                        <div style="font-size:11px;color:#9CA3AF;">ID #<?= $row['id'] ?></div>
                    </td>
                    <td style="color:#6B7280;"><?= htmlspecialchars($row['category_name']) ?></td>
                    <td style="font-weight:700;color:#111827;white-space:nowrap;"><?= $priceText ?></td>
                    <td>
                        <span class="<?= (int)$row['stock'] < 10 ? 'stock-low' : 'stock-ok' ?>">
                            <?= (int)($row['stock']??0) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['is_variable']): ?>
                            <span class="badge-var">Variable</span>
                        <?php else: ?>
                            <span class="badge-sim">Simple</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/store/shopapay_api/admin/products/edit.php?id=<?= $row['id'] ?>" class="icon-btn" title="Edit"><i class="fas fa-pen"></i></a>
                        <a href="/store/shopapay_api/admin/products/delete.php?id=<?= $row['id'] ?>" class="icon-btn danger" title="Delete"
                           onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="tbl-footer">
            <div style="font-size:13px;color:#6B7280;">
                Showing <strong><?= min($offset+1,$total) ?></strong>–<strong><?= min($offset+$per_page,$total) ?></strong>
                of <strong><?= number_format($total) ?></strong>
            </div>
            <nav class="pag">
                <?php if ($page>1): ?><a href="?page=<?=$page-1?>&per_page=<?=$per_page?>&search=<?=urlencode($search)?>"><i class="fas fa-chevron-left" style="font-size:10px;"></i></a><?php endif; ?>
                <?php for ($p_num=1;$p_num<=$pages;$p_num++): ?>
                    <?php if ($p_num==$page): ?><span class="active"><?=$p_num?></span>
                    <?php else: ?><a href="?page=<?=$p_num?>&per_page=<?=$per_page?>&search=<?=urlencode($search)?>"><?=$p_num?></a><?php endif; ?>
                <?php endfor; ?>
                <?php if ($page<$pages): ?><a href="?page=<?=$page+1?>&per_page=<?=$per_page?>&search=<?=urlencode($search)?>"><i class="fas fa-chevron-right" style="font-size:10px;"></i></a><?php endif; ?>
            </nav>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>