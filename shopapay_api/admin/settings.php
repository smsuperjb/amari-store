<?php
// admin/settings.php
require_once '../config/database.php';
include 'includes/header.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!empty($_POST['site_name'])) {
            file_put_contents("../uploads/site_name.txt", trim($_POST['site_name']));
        }
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $filename = "logo_" . time() . "." . pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['logo']['tmp_name'], "../uploads/" . $filename)) {
                file_put_contents("../uploads/logo.txt", $filename);
            }
        }
        $message = 'success';
    } catch (Exception $e) {
        $message = 'error';
    }
}

$current_name = file_exists("../uploads/site_name.txt") ? trim(file_get_contents("../uploads/site_name.txt")) : "ShopaPay";
$logo_txt     = "../uploads/logo.txt";
$current_logo = file_exists($logo_txt) ? "../uploads/" . trim(file_get_contents($logo_txt)) : null;
?>

<style>
.page-title { font-size:22px; font-weight:700; color:#111827; margin:0; }
.page-sub   { font-size:13px; color:#9CA3AF; margin-top:2px; }
.set-card   { background:#fff; border-radius:16px; box-shadow:0 1px 6px rgba(0,0,0,.06); overflow:hidden; max-width:640px; }
.set-hdr    { padding:18px 24px; border-bottom:1px solid #F3F4F6; }
.set-hdr h6 { font-weight:700; font-size:15px; color:#111827; margin:0; }
.set-body   { padding:24px; }
.form-lbl   { font-size:12px; font-weight:600; color:#374151; margin-bottom:6px; display:block; }
.form-inp   { width:100%; height:42px; border:1.5px solid #E5E7EB; border-radius:10px; padding:0 14px; font-size:14px; color:#374151; outline:none; font-family:inherit; transition:border-color .15s; }
.form-inp:focus { border-color:#6C3FC8; }
input[type="file"].form-inp { padding:8px 14px; height:auto; cursor:pointer; }
.btn-save   { background:#6C3FC8; color:#fff; border:none; border-radius:10px; padding:11px 28px; font-size:14px; font-weight:600; cursor:pointer; font-family:inherit; transition:background .15s; }
.btn-save:hover { background:#5a32a8; }
.alert-s    { padding:12px 16px; border-radius:10px; font-size:13px; margin-bottom:20px; }
.alert-ok   { background:#D1FAE5; color:#065F46; }
.alert-err  { background:#FEE2E2; color:#991B1B; }
.logo-preview { width:100px; height:100px; border-radius:14px; object-fit:contain; border:1.5px solid #F3F4F6; padding:8px; background:#FAFAFA; display:block; margin-bottom:12px; }
.logo-ph    { width:100px; height:100px; border-radius:14px; background:#F3F4F6; display:flex; align-items:center; justify-content:center; color:#D1D5DB; font-size:30px; margin-bottom:12px; }
.hint       { font-size:12px; color:#9CA3AF; margin-top:5px; }
</style>

<div style="padding:28px 24px;">

    <div style="margin-bottom:28px;">
        <div class="page-title">Settings</div>
        <div class="page-sub">Manage your store branding</div>
    </div>

    <?php if ($message === 'success'): ?>
        <div class="alert-s alert-ok" style="max-width:640px;">✅ Settings saved successfully!</div>
    <?php elseif ($message === 'error'): ?>
        <div class="alert-s alert-err" style="max-width:640px;">❌ Error saving settings. Please try again.</div>
    <?php endif; ?>

    <div class="set-card">
        <div class="set-hdr">
            <h6><i class="fas fa-store me-2" style="color:#6C3FC8;"></i>Site Branding</h6>
        </div>
        <div class="set-body">
            <form method="POST" enctype="multipart/form-data">

                <div style="margin-bottom:22px;">
                    <label class="form-lbl">Site Name</label>
                    <input type="text" name="site_name" class="form-inp"
                           value="<?= htmlspecialchars($current_name) ?>" required>
                    <div class="hint">Appears next to the logo in the sidebar.</div>
                </div>

                <div style="margin-bottom:28px;">
                    <label class="form-lbl">Site Logo</label>
                    <?php if ($current_logo && file_exists($current_logo)): ?>
                        <img src="<?= htmlspecialchars($current_logo) ?>" class="logo-preview" alt="Current Logo">
                    <?php else: ?>
                        <div class="logo-ph"><i class="fas fa-image"></i></div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-inp" accept="image/*">
                    <div class="hint">PNG or JPG recommended. Upload a new file to replace the current logo.</div>
                </div>

                <button type="submit" class="btn-save">
                    <i class="fas fa-floppy-disk me-2"></i>Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>