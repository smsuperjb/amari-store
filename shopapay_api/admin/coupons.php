<?php
// admin/coupons.php
require_once 'includes/header.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id               = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
    $code             = !empty($_POST['code']) ? strtoupper(trim($_POST['code'])) : null;
    $type             = $_POST['type'];
    $value            = (float)$_POST['value'];
    $min_order_amount = (float)$_POST['min_order_amount'];
    $min_quantity     = (int)$_POST['min_quantity'];
    $usage_limit      = (int)$_POST['usage_limit'];
    $expiry_date      = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $can_combine      = isset($_POST['can_combine']) ? 1 : 0;
    $auto_apply       = isset($_POST['auto_apply']) ? 1 : 0;
    $is_active        = isset($_POST['is_active']) ? 1 : 0;
    if ($auto_apply && empty($code)) $code = null;

    if ($id > 0) {
        $pdo->prepare("UPDATE coupons SET code=?,type=?,value=?,min_order_amount=?,min_quantity=?,usage_limit=?,expiry_date=?,can_combine=?,auto_apply=?,is_active=? WHERE id=?")
            ->execute([$code,$type,$value,$min_order_amount,$min_quantity,$usage_limit,$expiry_date,$can_combine,$auto_apply,$is_active,$id]);
        $success = "Promo updated!";
    } else {
        $pdo->prepare("INSERT INTO coupons (code,type,value,min_order_amount,min_quantity,usage_limit,expiry_date,can_combine,auto_apply,is_active,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())")
            ->execute([$code,$type,$value,$min_order_amount,$min_quantity,$usage_limit,$expiry_date,$can_combine,$auto_apply,$is_active]);
        $success = "New promo created!";
    }
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM coupons WHERE id=?")->execute([(int)$_GET['delete']]);
    $success = "Promo deleted!";
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();

$edit_coupon = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM coupons WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $edit_coupon = $s->fetch();
}
?>

<style>
.page-title  { font-size:22px; font-weight:700; color:#111827; margin:0; }
.page-sub    { font-size:13px; color:#9CA3AF; margin-top:2px; }
.btn-add     { background:#6C3FC8; color:#fff; border:none; border-radius:10px; padding:10px 20px; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:background .15s; }
.btn-add:hover { background:#5a32a8; color:#fff; }
.alert-s     { padding:12px 16px; border-radius:10px; font-size:13px; margin-bottom:20px; }
.alert-ok    { background:#D1FAE5; color:#065F46; }
.tbl-card    { background:#fff; border-radius:16px; box-shadow:0 1px 6px rgba(0,0,0,.06); overflow:hidden; }
.dash-table  { width:100%; border-collapse:collapse; font-size:13px; }
.dash-table thead th { background:#F9FAFB; color:#9CA3AF; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; padding:11px 16px; border-bottom:1px solid #F3F4F6; white-space:nowrap; }
.dash-table tbody tr { border-bottom:1px solid #F3F4F6; transition:background .12s; }
.dash-table tbody tr:last-child { border-bottom:none; }
.dash-table tbody tr:hover { background:#FAFAFA; }
.dash-table td { padding:12px 16px; color:#374151; vertical-align:middle; }
.icon-btn    { width:30px; height:30px; border-radius:7px; border:1.5px solid #E5E7EB; background:#fff; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; color:#6B7280; font-size:12px; transition:all .15s; text-decoration:none; }
.icon-btn:hover { border-color:#6C3FC8; color:#6C3FC8; }
.icon-btn.danger:hover { border-color:#EF4444; color:#EF4444; }
/* Modal */
.modal-card  { border-radius:16px !important; border:none !important; }
.modal-hdr   { background:#F9FAFB; border-radius:16px 16px 0 0 !important; padding:18px 24px; border-bottom:1px solid #E5E7EB; }
.modal-hdr h5{ font-weight:700; font-size:15px; color:#111827; margin:0; }
.modal-bdy   { padding:24px; }
.modal-ftr   { padding:14px 24px; border-top:1px solid #E5E7EB; display:flex; gap:10px; justify-content:flex-end; }
.form-lbl    { font-size:12px; font-weight:600; color:#374151; margin-bottom:5px; display:block; }
.form-inp    { width:100%; height:38px; border:1.5px solid #E5E7EB; border-radius:8px; padding:0 12px; font-size:13px; color:#374151; outline:none; font-family:inherit; }
.form-inp:focus { border-color:#6C3FC8; }
select.form-inp { padding:0 8px; cursor:pointer; }
.btn-primary-c { background:#6C3FC8; color:#fff; border:none; border-radius:8px; padding:0 20px; height:38px; font-size:13px; font-weight:600; cursor:pointer; font-family:inherit; }
.btn-outline-c { background:#fff; color:#374151; border:1.5px solid #E5E7EB; border-radius:8px; padding:0 16px; height:38px; font-size:13px; cursor:pointer; font-family:inherit; }
.toggle-row  { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid #F3F4F6; }
.toggle-row:last-child { border-bottom:none; }
.toggle-row label { font-size:13px; color:#374151; margin:0; cursor:pointer; flex:1; }
/* custom toggle */
.tog { position:relative; width:38px; height:22px; flex-shrink:0; }
.tog input { opacity:0; width:0; height:0; }
.tog-slider { position:absolute; inset:0; background:#E5E7EB; border-radius:22px; transition:.2s; cursor:pointer; }
.tog-slider:before { content:''; position:absolute; width:16px; height:16px; background:#fff; border-radius:50%; left:3px; bottom:3px; transition:.2s; }
.tog input:checked + .tog-slider { background:#6C3FC8; }
.tog input:checked + .tog-slider:before { transform:translateX(16px); }
</style>

<div style="padding:28px 24px;">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <div>
            <div class="page-title">Coupons & Promos</div>
            <div class="page-sub"><?= count($coupons) ?> promos configured</div>
        </div>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#couponModal" onclick="openAddMode()">
            <i class="fas fa-plus"></i> New Promo
        </button>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert-s alert-ok">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="tbl-card">
        <div style="overflow-x:auto;">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Min Order</th>
                        <th>Usage</th>
                        <th>Auto Apply</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th style="width:80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($coupons)): ?>
                    <tr><td colspan="9" style="text-align:center;padding:60px;color:#9CA3AF;">
                        <i class="fas fa-percent" style="font-size:36px;display:block;margin-bottom:10px;"></i>
                        No promos yet
                    </td></tr>
                <?php else: ?>
                <?php foreach ($coupons as $c): ?>
                <tr>
                    <td>
                        <?php if ($c['code']): ?>
                            <span style="font-weight:700;color:#111827;font-family:monospace;background:#F3F4F6;padding:3px 8px;border-radius:6px;">
                                <?= htmlspecialchars($c['code']) ?>
                            </span>
                        <?php else: ?>
                            <span style="background:#DBEAFE;color:#1E40AF;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;">Auto Apply</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:#6B7280;"><?= ucfirst(str_replace('_',' ',$c['type'])) ?></td>
                    <td style="font-weight:600;">
                        <?php
                        if ($c['type']==='percentage') echo $c['value'].'%';
                        elseif ($c['type']==='fixed') echo '₱'.number_format($c['value'],2);
                        elseif ($c['type']==='free_shipping') echo 'Free Ship';
                        else echo 'BxGy';
                        ?>
                    </td>
                    <td>₱<?= number_format($c['min_order_amount'],2) ?></td>
                    <td style="color:#6B7280;"><?= $c['used_count'] ?> / <?= $c['usage_limit'] ?: '∞' ?></td>
                    <td>
                        <?php if ($c['auto_apply']): ?>
                            <span style="background:#D1FAE5;color:#065F46;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;">Yes</span>
                        <?php else: ?>
                            <span style="color:#9CA3AF;font-size:12px;">No</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:#6B7280;"><?= $c['expiry_date'] ? date('M d, Y',strtotime($c['expiry_date'])) : '—' ?></td>
                    <td>
                        <?php if ($c['is_active']): ?>
                            <span style="background:#D1FAE5;color:#065F46;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;">Active</span>
                        <?php else: ?>
                            <span style="background:#F3F4F6;color:#9CA3AF;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?edit=<?= $c['id'] ?>" class="icon-btn" title="Edit"><i class="fas fa-pen"></i></a>
                        <a href="?delete=<?= $c['id'] ?>" class="icon-btn danger" title="Delete" onclick="return confirm('Delete this promo?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="couponModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content modal-card">
            <div class="modal-hdr">
                <h5 id="modalTitle">Create New Promo</h5>
            </div>
            <form method="POST">
                <input type="hidden" name="edit_id" id="editId" value="">
                <div class="modal-bdy">

                    <div style="margin-bottom:16px;">
                        <label class="form-lbl">Coupon Code <span style="color:#9CA3AF;font-weight:400;">(leave blank for Auto Apply)</span></label>
                        <input type="text" name="code" id="code" class="form-inp text-uppercase" maxlength="20" placeholder="e.g. WELCOME20" style="text-transform:uppercase;">
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                        <div>
                            <label class="form-lbl">Discount Type</label>
                            <select name="type" id="type" class="form-inp" onchange="updateFields()">
                                <option value="percentage">Percentage Off</option>
                                <option value="fixed">Fixed Amount Off</option>
                                <option value="free_shipping">Free Shipping</option>
                                <option value="bxgy">Buy X Get Y</option>
                            </select>
                        </div>
                        <div id="valueContainer">
                            <label class="form-lbl" id="valueLabel">Value</label>
                            <input type="number" name="value" id="value" class="form-inp" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                        <div>
                            <label class="form-lbl">Min Order Amount (₱)</label>
                            <input type="number" name="min_order_amount" id="min_order_amount" class="form-inp" step="0.01" min="0" value="0">
                        </div>
                        <div id="minQtyContainer">
                            <label class="form-lbl">Min Quantity (BxGy)</label>
                            <input type="number" name="min_quantity" id="min_quantity" class="form-inp" min="1" value="1">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
                        <div>
                            <label class="form-lbl">Usage Limit (0 = unlimited)</label>
                            <input type="number" name="usage_limit" id="usage_limit" class="form-inp" min="0" value="0">
                        </div>
                        <div>
                            <label class="form-lbl">Expiry Date</label>
                            <input type="date" name="expiry_date" id="expiry_date" class="form-inp">
                        </div>
                    </div>

                    <div style="background:#F9FAFB;border-radius:10px;padding:14px 16px;">
                        <div class="toggle-row">
                            <label class="tog"><input type="checkbox" name="auto_apply" id="auto_apply" onchange="toggleCodeField()"><span class="tog-slider"></span></label>
                            <label for="auto_apply">Auto Apply at Checkout <span style="color:#9CA3AF;font-size:12px;">(no code needed)</span></label>
                        </div>
                        <div class="toggle-row">
                            <label class="tog"><input type="checkbox" name="can_combine" id="can_combine" checked><span class="tog-slider"></span></label>
                            <label for="can_combine">Can combine with other discounts</label>
                        </div>
                        <div class="toggle-row">
                            <label class="tog"><input type="checkbox" name="is_active" id="is_active" checked><span class="tog-slider"></span></label>
                            <label for="is_active">Active</label>
                        </div>
                    </div>

                </div>
                <div class="modal-ftr">
                    <button type="button" class="btn-outline-c" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-c" id="submitBtn">Create Promo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddMode() {
    document.getElementById('modalTitle').textContent = 'Create New Promo';
    document.getElementById('submitBtn').textContent = 'Create Promo';
    document.getElementById('editId').value = '';
    document.getElementById('code').value = '';
    document.getElementById('type').value = 'percentage';
    document.getElementById('value').value = '';
    document.getElementById('min_order_amount').value = '0';
    document.getElementById('min_quantity').value = '1';
    document.getElementById('usage_limit').value = '0';
    document.getElementById('expiry_date').value = '';
    document.getElementById('auto_apply').checked = false;
    document.getElementById('can_combine').checked = true;
    document.getElementById('is_active').checked = true;
    updateFields(); toggleCodeField();
}
function updateFields() {
    const t = document.getElementById('type').value;
    const lbl = document.getElementById('valueLabel');
    if (t==='percentage') lbl.textContent = 'Discount (%)';
    else if (t==='fixed') lbl.textContent = 'Fixed Amount (₱)';
    else lbl.textContent = 'Value';
    document.getElementById('minQtyContainer').style.display = (t==='bxgy') ? 'block' : 'none';
}
function toggleCodeField() {
    const a = document.getElementById('auto_apply').checked;
    const f = document.getElementById('code');
    f.disabled = a; f.value = a ? '' : f.value;
    f.placeholder = a ? 'Auto Apply — no code needed' : 'e.g. WELCOME20';
}
window.addEventListener('load', function() {
    <?php if ($edit_coupon): ?>
    new bootstrap.Modal(document.getElementById('couponModal')).show();
    document.getElementById('modalTitle').textContent = 'Edit Promo';
    document.getElementById('submitBtn').textContent = 'Update Promo';
    document.getElementById('editId').value = '<?= $edit_coupon['id'] ?>';
    document.getElementById('code').value = '<?= addslashes($edit_coupon['code'] ?? '') ?>';
    document.getElementById('type').value = '<?= $edit_coupon['type'] ?>';
    document.getElementById('value').value = '<?= $edit_coupon['value'] ?>';
    document.getElementById('min_order_amount').value = '<?= $edit_coupon['min_order_amount'] ?>';
    document.getElementById('min_quantity').value = '<?= $edit_coupon['min_quantity'] ?? 1 ?>';
    document.getElementById('usage_limit').value = '<?= $edit_coupon['usage_limit'] ?>';
    document.getElementById('expiry_date').value = '<?= $edit_coupon['expiry_date'] ?? '' ?>';
    document.getElementById('auto_apply').checked = <?= $edit_coupon['auto_apply'] ? 'true':'false' ?>;
    document.getElementById('can_combine').checked = <?= $edit_coupon['can_combine'] ? 'true':'false' ?>;
    document.getElementById('is_active').checked = <?= $edit_coupon['is_active'] ? 'true':'false' ?>;
    updateFields(); toggleCodeField();
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>