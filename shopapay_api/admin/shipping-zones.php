<?php
require_once 'includes/header.php';
require_once '../config/database.php';

$current_edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

$usedProvinces = [];
$stmt = $pdo->query("SELECT id, zone_name, provinces FROM shipping_zones");
while ($row = $stmt->fetch()) {
    if ($row['id'] == $current_edit_id) continue;
    $provs = json_decode($row['provinces'], true) ?? [];
    foreach ($provs as $p) $usedProvinces[$p][] = $row['zone_name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_shipping_zone'])) {
    $zone_name     = trim($_POST['zone_name'] ?? '');
    $shipping_name = trim($_POST['shipping_name'] ?? '');
    $shipping_type = $_POST['shipping_type'] ?? 'flat_rate';
    $shipping_cost = ($shipping_type === 'flat_rate') ? (float)($_POST['shipping_cost'] ?? 0) : 0.00;
    $provinces     = $_POST['provinces'] ?? [];

    if ($zone_name && $shipping_name && !empty($provinces)) {
        $pdo->prepare("INSERT INTO shipping_zones (zone_name, shipping_name, shipping_type, shipping_cost, provinces, created_at) VALUES (?,?,?,?,?,NOW())")
            ->execute([$zone_name, $shipping_name, $shipping_type, $shipping_cost, json_encode($provinces)]);
        header("Location: shipping-zones.php?success=added"); exit;
    } else {
        $error = "Zone name, shipping name, and at least one province are required.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_shipping_zone'])) {
    $id            = (int)$_POST['edit_id'];
    $zone_name     = trim($_POST['zone_name'] ?? '');
    $shipping_name = trim($_POST['shipping_name'] ?? '');
    $shipping_type = $_POST['shipping_type'] ?? 'flat_rate';
    $shipping_cost = ($shipping_type === 'flat_rate') ? (float)($_POST['shipping_cost'] ?? 0) : 0.00;
    $provinces     = $_POST['provinces'] ?? [];

    if ($id && $zone_name && $shipping_name && !empty($provinces)) {
        $pdo->prepare("UPDATE shipping_zones SET zone_name=?,shipping_name=?,shipping_type=?,shipping_cost=?,provinces=? WHERE id=?")
            ->execute([$zone_name, $shipping_name, $shipping_type, $shipping_cost, json_encode($provinces), $id]);
        header("Location: shipping-zones.php?success=updated"); exit;
    }
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM shipping_zones WHERE id = ?")->execute([(int)$_GET['delete']]);
    header("Location: shipping-zones.php?success=deleted"); exit;
}

$stmt  = $pdo->query("SELECT * FROM shipping_zones ORDER BY created_at DESC");
$zones = $stmt->fetchAll();

$edit_zone = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM shipping_zones WHERE id = ?");
    $s->execute([$current_edit_id]);
    $edit_zone = $s->fetch();
}

$total_zones   = count($zones);
$free_zones    = count(array_filter($zones, fn($z) => $z['shipping_type'] === 'free_shipping'));
$flat_zones    = $total_zones - $free_zones;
?>

<style>
.province-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 6px; max-height: 320px; overflow-y: auto;
    border: 1.5px solid #E5E7EB; border-radius: 10px; padding: 12px;
    background: #FAFAFA;
}
.prov-check {
    display: flex; align-items: center; gap: 8px;
    padding: 6px 10px; border-radius: 7px; cursor: pointer;
    transition: background .15s; font-size: 12.5px; color: #374151;
}
.prov-check:hover { background: #F3F0FF; }
.prov-check.used { opacity: .45; cursor: not-allowed; }
.prov-check.selected { background: #F3F0FF; color: #6C3FC8; font-weight: 600; }
.zone-card {
    background: #fff; border-radius: 14px; padding: 18px 20px;
    box-shadow: 0 1px 6px rgba(0,0,0,.06); margin-bottom: 12px;
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
}
.zone-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: #F3F0FF; display: flex; align-items: center;
    justify-content: center; color: #6C3FC8; font-size: 18px; flex-shrink: 0;
}
.zone-info { flex: 1; min-width: 0; }
.zone-name  { font-weight: 700; font-size: 14px; color: #111827; }
.zone-sub   { font-size: 12px; color: #6B7280; margin-top: 2px; }
.zone-provinces { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 8px; }
.prov-pill {
    background: #F3F4F6; color: #374151;
    font-size: 11px; padding: 2px 9px; border-radius: 20px;
}
.zone-actions { display: flex; gap: 8px; flex-shrink: 0; }
.cost-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
}
.cost-flat { background: #EDE9FF; color: #6C3FC8; }
.cost-free { background: #D1FAE5; color: #065F46; }
.search-bar { position: relative; margin-bottom: 10px; }
.search-bar input { width: 100%; padding-left: 34px; }
.search-bar i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9CA3AF; font-size: 13px; }
.select-all-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 12px; color: #6B7280; }
</style>

<div class="orders-wrap">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <h1 class="page-title">Shipping Zones</h1>
            <p class="page-sub">Define shipping rates and coverage areas</p>
        </div>
        <button class="btn-primary-c" data-bs-toggle="modal" data-bs-target="#shippingModal" onclick="openAddMode()">
            <i class="fas fa-plus"></i> Add Shipping Zone
        </button>
    </div>

    <!-- ALERTS -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert-s alert-success-c mb-4">
            <i class="fas fa-check-circle me-2"></i>
            <?= $_GET['success'] === 'added' ? 'Shipping zone added!' :
                ($_GET['success'] === 'updated' ? 'Zone updated!' : 'Zone deleted.') ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert-s alert-danger-c mb-4">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stat-cards mb-4">
        <div class="stat-card">
            <div class="stat-num"><?= $total_zones ?></div>
            <div class="stat-label">Total Zones</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#6C3FC8"><?= $flat_zones ?></div>
            <div class="stat-label">Flat Rate</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#065F46"><?= $free_zones ?></div>
            <div class="stat-label">Free Shipping</div>
        </div>
    </div>

    <!-- ZONE LIST -->
    <?php if (empty($zones)): ?>
        <div class="tbl-card">
            <div style="text-align:center;padding:60px 20px;color:#9CA3AF">
                <i class="fas fa-shipping-fast fa-3x mb-3 d-block"></i>
                <p style="font-size:15px;font-weight:600;color:#374151">No shipping zones yet</p>
                <p style="font-size:13px">Click "Add Shipping Zone" to define your first zone.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($zones as $zone):
            $provs = json_decode($zone['provinces'], true) ?? [];
        ?>
            <div class="zone-card">
                <div class="zone-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="zone-info">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="zone-name"><?= htmlspecialchars($zone['zone_name']) ?></span>
                        <span class="cost-badge <?= $zone['shipping_type']==='free_shipping' ? 'cost-free' : 'cost-flat' ?>">
                            <?php if ($zone['shipping_type'] === 'free_shipping'): ?>
                                <i class="fas fa-gift"></i> Free Shipping
                            <?php else: ?>
                                <i class="fas fa-tag"></i> ₱<?= number_format($zone['shipping_cost'], 2) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="zone-sub"><?= htmlspecialchars($zone['shipping_name']) ?> &bull; Added <?= date('M d, Y', strtotime($zone['created_at'])) ?></div>
                    <div class="zone-provinces">
                        <?php foreach (array_slice($provs, 0, 6) as $p): ?>
                            <span class="prov-pill"><?= htmlspecialchars($p) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($provs) > 6): ?>
                            <span class="prov-pill" style="background:#EDE9FF;color:#6C3FC8">
                                +<?= count($provs) - 6 ?> more
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="zone-actions">
                    <a href="?edit=<?= $zone['id'] ?>" class="btn-outline-c" style="height:36px;padding:0 14px">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="?delete=<?= $zone['id'] ?>"
                       class="icon-btn" style="border-color:#EF4444;color:#EF4444"
                       onclick="return confirm('Delete this shipping zone?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- MODAL -->
<div class="modal fade" id="shippingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-card">
            <div class="modal-hdr">
                <h5 id="modalTitle">Create New Shipping Zone</h5>
            </div>
            <form method="POST">
                <input type="hidden" name="add_shipping_zone" value="1" id="formMode">
                <input type="hidden" name="edit_id" value="" id="editId">

                <div class="modal-bdy">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-lbl">Zone Name <span style="color:#EF4444">*</span></label>
                            <input type="text" name="zone_name" id="zone_name" class="form-inp" placeholder="e.g. Luzon" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-lbl">Display Name <span style="color:#EF4444">*</span></label>
                            <input type="text" name="shipping_name" id="shipping_name" class="form-inp" placeholder="e.g. Standard Shipping" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-lbl">Shipping Type</label>
                            <select name="shipping_type" id="shippingType" class="form-inp" onchange="toggleCostField()">
                                <option value="flat_rate">Flat Rate</option>
                                <option value="free_shipping">Free Shipping</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="costField">
                            <label class="form-lbl">Shipping Cost (₱)</label>
                            <input type="number" name="shipping_cost" id="shipping_cost" class="form-inp"
                                   step="0.01" min="0" value="0" placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-lbl">Provinces / Areas <span style="color:#EF4444">*</span></label>
                        <div class="search-bar">
                            <i class="fas fa-search"></i>
                            <input type="text" id="provinceSearch" class="form-inp" placeholder="Search provinces...">
                        </div>
                        <div class="select-all-row">
                            <input type="checkbox" id="selectAllProvinces" onchange="toggleSelectAll(this)">
                            <label for="selectAllProvinces" style="cursor:pointer;margin:0">Select all available</label>
                            <span id="selectedCount" style="margin-left:auto;font-weight:600;color:#6C3FC8">0 selected</span>
                        </div>
                    </div>

                    <div class="province-grid" id="provinceList">
                        <?php
                        $allProvinces = ["Metro Manila","Ilocos Norte","Ilocos Sur","La Union","Pangasinan","Batanes","Cagayan","Isabela","Nueva Vizcaya","Quirino","Bataan","Bulacan","Nueva Ecija","Pampanga","Tarlac","Zambales","Aurora","Batangas","Cavite","Laguna","Quezon","Rizal","Marinduque","Occidental Mindoro","Oriental Mindoro","Palawan","Romblon","Albay","Camarines Norte","Camarines Sur","Catanduanes","Masbate","Sorsogon","Aklan","Antique","Capiz","Iloilo","Negros Occidental","Negros Oriental","Guimaras","Bohol","Cebu","Siquijor","Eastern Samar","Leyte","Northern Samar","Southern Leyte","Biliran","Zamboanga Del Norte","Zamboanga Del Sur","Zamboanga Sibugay","Bukidnon","Camiguin","Lanao Del Norte","Misamis Occidental","Misamis Oriental","Davao Del Norte","Davao Del Sur","Davao Oriental","Davao De Oro","Davao Occidental","Cotabato","South Cotabato","Sultan Kudarat","Sarangani","Abra","Benguet","Ifugao","Kalinga","Mountain Province","Apayao","Agusan Del Norte","Agusan Del Sur","Surigao Del Norte","Surigao Del Sur","Dinagat Islands","Basilan","Lanao Del Sur","Maguindanao","Sulu","Tawi-Tawi"];

                        foreach ($allProvinces as $prov) {
                            $isUsed = isset($usedProvinces[$prov]);
                            $tip    = $isUsed ? 'Used in: ' . implode(', ', $usedProvinces[$prov]) : '';
                            echo '<label class="prov-check ' . ($isUsed ? 'used' : '') . '" title="' . htmlspecialchars($tip) . '">
                                <input type="checkbox" name="provinces[]" value="' . htmlspecialchars($prov) . '"
                                    id="prov_' . htmlspecialchars($prov) . '" ' . ($isUsed ? 'disabled' : '') . '
                                    onchange="updateSelectedCount()">
                                ' . htmlspecialchars($prov) . ($isUsed ? ' <small>(used)</small>' : '') . '
                            </label>';
                        }
                        ?>
                    </div>
                </div>

                <div class="modal-ftr">
                    <button type="button" class="btn-outline-c" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-c" id="submitBtn">
                        <i class="fas fa-save"></i> Create Zone
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddMode() {
    document.getElementById('modalTitle').textContent = 'Create New Shipping Zone';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Create Zone';
    document.getElementById('formMode').name = 'add_shipping_zone';
    document.getElementById('editId').value = '';
    document.getElementById('zone_name').value = '';
    document.getElementById('shipping_name').value = '';
    document.getElementById('shippingType').value = 'flat_rate';
    document.getElementById('shipping_cost').value = '0';
    document.getElementById('costField').style.display = 'block';
    document.querySelectorAll('#provinceList input[type="checkbox"]').forEach(cb => { if (!cb.disabled) cb.checked = false; });
    updateSelectedCount();
}

function toggleCostField() {
    document.getElementById('costField').style.display =
        document.getElementById('shippingType').value === 'flat_rate' ? 'block' : 'none';
}

function updateSelectedCount() {
    const count = document.querySelectorAll('#provinceList input[type="checkbox"]:checked').length;
    document.getElementById('selectedCount').textContent = count + ' selected';
    // Update label styles
    document.querySelectorAll('#provinceList .prov-check').forEach(label => {
        const cb = label.querySelector('input');
        if (!cb.disabled) label.classList.toggle('selected', cb.checked);
    });
}

function toggleSelectAll(el) {
    document.querySelectorAll('#provinceList input[type="checkbox"]:not([disabled])').forEach(cb => {
        cb.checked = el.checked;
    });
    updateSelectedCount();
}

document.getElementById('provinceSearch').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('#provinceList .prov-check').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
});

window.addEventListener('load', function() {
    <?php if ($edit_zone): ?>
        const modal = new bootstrap.Modal(document.getElementById('shippingModal'));
        modal.show();
        document.getElementById('modalTitle').textContent = 'Edit Shipping Zone';
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update Zone';
        document.getElementById('formMode').name = 'update_shipping_zone';
        document.getElementById('editId').value = '<?= $edit_zone['id'] ?>';
        document.getElementById('zone_name').value = '<?= addslashes(htmlspecialchars($edit_zone['zone_name'])) ?>';
        document.getElementById('shipping_name').value = '<?= addslashes(htmlspecialchars($edit_zone['shipping_name'])) ?>';
        document.getElementById('shippingType').value = '<?= $edit_zone['shipping_type'] ?>';
        document.getElementById('shipping_cost').value = '<?= $edit_zone['shipping_cost'] ?>';
        toggleCostField();
        const selected = <?= json_encode(json_decode($edit_zone['provinces'], true) ?? []) ?>;
        document.querySelectorAll('#provinceList input[type="checkbox"]').forEach(cb => {
            if (selected.includes(cb.value)) { cb.checked = true; }
        });
        updateSelectedCount();
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>