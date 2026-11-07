<?php
// admin/customers.php
require_once 'includes/header.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $name          = trim($_POST['name'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $province      = trim($_POST['province'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $barangay      = trim($_POST['barangay'] ?? '');
    $temp_password = trim($_POST['temp_password'] ?? '');

    if ($name && $email && $temp_password) {
        try {
            $pdo->prepare("INSERT INTO users (name,email,phone,address,province,city,barangay,password,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())")
                ->execute([$name,$email,$phone,$address,$province,$city,$barangay, password_hash($temp_password, PASSWORD_DEFAULT)]);
            $success = "Customer added! Temp password: <code style='background:#D1FAE5;padding:2px 6px;border-radius:4px;'>" . htmlspecialchars($temp_password) . "</code>";
        } catch (Exception $e) {
            $error = "Failed: " . $e->getMessage();
        }
    } else {
        $error = "Name, email, and password are required.";
    }
}

$per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'],[10,50,100]) ? (int)$_GET['per_page'] : 10;
$page     = max(1,(int)($_GET['page']??1));
$search   = trim($_GET['search']??'');
$offset   = ($page-1)*$per_page;
$where    = $search ? "WHERE name LIKE ? OR email LIKE ?" : "";
$params   = $search ? ["%$search%","%$search%"] : [];

$total    = (int)$pdo->prepare("SELECT COUNT(*) FROM users $where")->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM users $where")->execute($params) : 0;
$cStmt    = $pdo->prepare("SELECT COUNT(*) FROM users $where"); $cStmt->execute($params);
$total    = (int)$cStmt->fetchColumn();
$pages    = max(1,ceil($total/$per_page));

$stmt = $pdo->prepare("SELECT id,name,email,phone,address,COALESCE(province,'') as province,COALESCE(city,'') as city,COALESCE(barangay,'') as barangay,created_at FROM users $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<style>
.page-title { font-size:22px; font-weight:700; color:#111827; margin:0; }
.page-sub   { font-size:13px; color:#9CA3AF; margin-top:2px; }
.btn-add    { background:#6C3FC8; color:#fff; border:none; border-radius:10px; padding:10px 20px; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:background .15s; }
.btn-add:hover { background:#5a32a8; color:#fff; }
.alert-s    { padding:12px 16px; border-radius:10px; font-size:13px; margin-bottom:20px; }
.alert-ok   { background:#D1FAE5; color:#065F46; }
.alert-err  { background:#FEE2E2; color:#991B1B; }
.tbl-card   { background:#fff; border-radius:16px; box-shadow:0 1px 6px rgba(0,0,0,.06); overflow:hidden; }
.tbl-toolbar{ padding:16px 20px; border-bottom:1px solid #F3F4F6; display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
.inp        { height:36px; border:1.5px solid #E5E7EB; border-radius:8px; padding:0 12px; font-size:13px; color:#374151; outline:none; font-family:inherit; }
.inp:focus  { border-color:#6C3FC8; }
select.inp  { padding:0 8px; }
.btn-search { background:#6C3FC8; color:#fff; border:none; border-radius:8px; height:36px; padding:0 16px; font-size:13px; cursor:pointer; font-family:inherit; }
.dash-table { width:100%; border-collapse:collapse; font-size:13px; min-width:900px; }
.dash-table thead th { background:#F9FAFB; color:#9CA3AF; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; padding:11px 16px; border-bottom:1px solid #F3F4F6; }
.dash-table tbody tr { border-bottom:1px solid #F3F4F6; transition:background .12s; }
.dash-table tbody tr:last-child { border-bottom:none; }
.dash-table tbody tr:hover { background:#FAFAFA; }
.dash-table td { padding:12px 16px; color:#374151; vertical-align:middle; }
.avatar     { width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#8B5CF6,#6C3FC8); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:13px; flex-shrink:0; }
.tbl-footer { padding:14px 20px; border-top:1px solid #F3F4F6; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.pag        { display:flex; gap:4px; }
.pag a, .pag span { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; font-size:13px; border:1.5px solid #E5E7EB; color:#374151; text-decoration:none; }
.pag a:hover { border-color:#6C3FC8; color:#6C3FC8; }
.pag span.active { background:#6C3FC8; color:#fff; border-color:#6C3FC8; font-weight:700; }
/* Modal */
.modal-card { border-radius:16px !important; border:none !important; }
.modal-hdr  { background:#F9FAFB; border-radius:16px 16px 0 0 !important; padding:18px 24px; border-bottom:1px solid #E5E7EB; }
.modal-hdr h5{ font-weight:700; font-size:15px; color:#111827; margin:0; }
.modal-bdy  { padding:24px; }
.modal-ftr  { padding:14px 24px; border-top:1px solid #E5E7EB; display:flex; gap:10px; justify-content:flex-end; }
.form-lbl   { font-size:12px; font-weight:600; color:#374151; margin-bottom:5px; display:block; }
.form-inp   { width:100%; height:38px; border:1.5px solid #E5E7EB; border-radius:8px; padding:0 12px; font-size:13px; color:#374151; outline:none; font-family:inherit; }
.form-inp:focus { border-color:#6C3FC8; }
select.form-inp { padding:0 8px; }
textarea.form-inp { height:72px; padding:8px 12px; resize:none; }
.btn-primary-c { background:#6C3FC8; color:#fff; border:none; border-radius:8px; padding:0 20px; height:38px; font-size:13px; font-weight:600; cursor:pointer; font-family:inherit; }
.btn-outline-c { background:#fff; color:#374151; border:1.5px solid #E5E7EB; border-radius:8px; padding:0 16px; height:38px; font-size:13px; cursor:pointer; font-family:inherit; }
</style>

<div style="padding:28px 24px;">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <div>
            <div class="page-title">Customers</div>
            <div class="page-sub"><?= number_format($total) ?> registered customers</div>
        </div>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
            <i class="fas fa-plus"></i> Add Customer
        </button>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert-s alert-ok">✅ <?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert-s alert-err">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="tbl-card">
        <div class="tbl-toolbar">
            <form method="GET" style="display:flex;gap:8px;flex:1;min-width:200px;">
                <input type="hidden" name="per_page" value="<?= $per_page ?>">
                <input class="inp" style="flex:1;" type="text" name="search"
                       placeholder="Search name or email…" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                <?php if ($search): ?><a href="customers.php" style="height:36px;display:inline-flex;align-items:center;padding:0 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;color:#374151;text-decoration:none;">Clear</a><?php endif; ?>
            </form>
            <form method="GET" style="display:flex;gap:6px;align-items:center;">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <span style="font-size:13px;color:#6B7280;">Show</span>
                <select class="inp" name="per_page" onchange="this.form.submit()">
                    <?php foreach ([10,50,100] as $o): ?>
                        <option value="<?= $o ?>" <?= $per_page==$o?'selected':'' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div style="overflow-x:auto;">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="4" style="text-align:center;padding:60px;color:#9CA3AF;">
                        <i class="fas fa-users" style="font-size:36px;display:block;margin-bottom:10px;"></i>
                        No customers found
                    </td></tr>
                <?php else: ?>
                <?php foreach ($customers as $c): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="avatar"><?= strtoupper(substr($c['name'],0,1)) ?></div>
                            <div>
                                <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($c['name']) ?></div>
                                <div style="font-size:12px;color:#9CA3AF;"><?= htmlspecialchars($c['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="color:#6B7280;"><?= htmlspecialchars($c['phone'] ?: '—') ?></td>
                    <td style="font-size:12px;color:#6B7280;max-width:220px;">
                        <?= htmlspecialchars($c['address'] ?: '—') ?>
                        <?php $loc = implode(', ', array_filter([$c['barangay'],$c['city'],$c['province']]));
                              if ($loc): ?><br><span style="color:#C4C9D4;"><?= htmlspecialchars($loc) ?></span><?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:#9CA3AF;white-space:nowrap;"><?= date('M d, Y',strtotime($c['created_at'])) ?></td>
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
                <?php for ($p=1;$p<=$pages;$p++): ?>
                    <?php if ($p==$page): ?><span class="active"><?=$p?></span>
                    <?php else: ?><a href="?page=<?=$p?>&per_page=<?=$per_page?>&search=<?=urlencode($search)?>"><?=$p?></a><?php endif; ?>
                <?php endfor; ?>
                <?php if ($page<$pages): ?><a href="?page=<?=$page+1?>&per_page=<?=$per_page?>&search=<?=urlencode($search)?>"><i class="fas fa-chevron-right" style="font-size:10px;"></i></a><?php endif; ?>
            </nav>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content modal-card">
            <div class="modal-hdr">
                <h5><i class="fas fa-user-plus me-2" style="color:#6C3FC8;"></i>Add New Customer</h5>
            </div>
            <form method="POST">
                <input type="hidden" name="add_customer" value="1">
                <div class="modal-bdy">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div><label class="form-lbl">Full Name *</label><input type="text" name="name" class="form-inp" required></div>
                        <div><label class="form-lbl">Email *</label><input type="email" name="email" class="form-inp" required></div>
                    </div>
                    <div style="margin-bottom:12px;">
                        <label class="form-lbl">Phone</label>
                        <input type="text" name="phone" class="form-inp" placeholder="09XXXXXXXXX">
                    </div>
                    <div style="margin-bottom:12px;">
                        <label class="form-lbl">Full Address</label>
                        <textarea name="address" class="form-inp"></textarea>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px;">
                        <div><label class="form-lbl">Province</label><select name="province" id="provinceSelect" class="form-inp" onchange="loadCities()"></select></div>
                        <div><label class="form-lbl">City</label><select name="city" id="citySelect" class="form-inp" onchange="loadBarangays()"></select></div>
                        <div><label class="form-lbl">Barangay</label><select name="barangay" id="barangaySelect" class="form-inp"></select></div>
                    </div>
                    <div style="background:#F9FAFB;border-radius:10px;padding:14px;">
                        <label class="form-lbl">Temporary Password *</label>
                        <input type="text" name="temp_password" class="form-inp" placeholder="Customer's initial password" required>
                        <div style="font-size:12px;color:#9CA3AF;margin-top:5px;">Customer can change this after logging in.</div>
                    </div>
                </div>
                <div class="modal-ftr">
                    <button type="button" class="btn-outline-c" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-c">Add Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function loadProvinces() {
    const s = document.getElementById('provinceSelect');
    s.innerHTML = '<option>Loading…</option>';
    try {
        const r = await fetch('https://psgc.gitlab.io/api/provinces/');
        const d = await r.json();
        s.innerHTML = '<option value="">Select Province</option>';
        d.sort((a,b)=>a.name.localeCompare(b.name)).forEach(p=>{
            s.innerHTML += `<option value="${p.name}">${p.name}</option>`;
        });
    } catch(e) { s.innerHTML = '<option>Failed to load</option>'; }
}
async function loadCities() {
    const p = document.getElementById('provinceSelect').value;
    const s = document.getElementById('citySelect');
    s.innerHTML = '<option value="">Select City</option>';
    document.getElementById('barangaySelect').innerHTML = '<option value="">Select Barangay</option>';
    if (!p) return;
    const r = await fetch(`https://psgc.gitlab.io/api/provinces/${encodeURIComponent(p)}/cities-municipalities/`);
    const d = await r.json();
    d.sort((a,b)=>a.name.localeCompare(b.name)).forEach(c=>{
        s.innerHTML += `<option value="${c.name}">${c.name}</option>`;
    });
}
async function loadBarangays() {
    const c = document.getElementById('citySelect').value;
    const s = document.getElementById('barangaySelect');
    s.innerHTML = '<option value="">Select Barangay</option>';
    if (!c) return;
    const r = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${encodeURIComponent(c)}/barangays/`);
    const d = await r.json();
    d.sort((a,b)=>a.name.localeCompare(b.name)).forEach(b=>{
        s.innerHTML += `<option value="${b.name}">${b.name}</option>`;
    });
}
document.getElementById('addCustomerModal').addEventListener('shown.bs.modal', loadProvinces);
</script>

<?php require_once 'includes/footer.php'; ?>