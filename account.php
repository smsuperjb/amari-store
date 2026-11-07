<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

requireLogin();

$user = currentUser();

// ── Handle POST actions ────────────────────────────────────
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Update profile ──
    if ($action === 'update_profile') {
        $name  = trim($_POST['name']  ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (!$name) {
            $error = 'Name cannot be empty.';
        } else {
            try {
                $cols = getColumns($pdo, 'users');
                $sets = ['name = ?'];
                $vals = [$name];

                if (in_array('phone', $cols)) { $sets[] = 'phone = ?'; $vals[] = $phone; }

                $vals[] = $user['id'];
                $pdo->prepare("UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?")
                    ->execute($vals);

                // Refresh session
                $fresh = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $fresh->execute([$user['id']]);
                $user = $fresh->fetch(PDO::FETCH_ASSOC);
                loginUser($user);

                $success = 'Profile updated successfully.';
            } catch (Exception $e) {
                $error = 'Could not update profile.';
            }
        }
    }

    // ── Change password ──
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$new || !$confirm) {
            $error = 'All password fields are required.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            try {
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                    ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
                $success = 'Password changed successfully.';
            } catch (Exception $e) {
                $error = 'Could not change password.';
            }
        }
    }

    // ── Delete account ──
    if ($action === 'delete_account') {
        $confirmPassword = $_POST['delete_password'] ?? '';
        $confirmText      = trim($_POST['delete_confirm_text'] ?? '');

        if (!$confirmPassword) {
            $error = 'Please enter your password to confirm account deletion.';
        } elseif (strcasecmp($confirmText, 'DELETE') !== 0) {
            $error = 'Please type DELETE to confirm.';
        } elseif (!password_verify($confirmPassword, $user['password'])) {
            $error = 'Password is incorrect.';
        } else {
            try {
                $pdo->beginTransaction();

                // Best-effort cleanup of dependent rows on tables that may exist
                foreach (['orders', 'wishlist', 'cart_items', 'addresses'] as $table) {
                    try {
                        $chk = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
                        $chk->execute([$table]);
                        if ($chk->fetchColumn()) {
                            $userCol = in_array($table, ['orders', 'wishlist', 'cart_items', 'addresses']) ? 'user_id' : 'user_id';
                            $pdo->prepare("DELETE FROM {$table} WHERE {$userCol} = ?")->execute([$user['id']]);
                        }
                    } catch (Exception $inner) {
                        // Table/column may not exist on this install — skip silently
                    }
                }

                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user['id']]);
                $pdo->commit();

                logoutUser();
                ob_end_clean();
                header('Location: ' . STORE_URL . '/index.php?account_deleted=1');
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $error = 'Could not delete account. Please try again or contact support.';
            }
        }
    }
}

// ── Fetch orders ───────────────────────────────────────────
$orders = [];
try {
    $orderCols = getColumns($pdo, 'orders');
    if (!empty($orderCols)) {
        $hasTotal   = in_array('total',      $orderCols);
        $hasStatus  = in_array('status',     $orderCols);
        $hasCreated = in_array('created_at', $orderCols);

        $sel = 'id';
        if ($hasTotal)   $sel .= ', total';
        if ($hasStatus)  $sel .= ', status';
        if ($hasCreated) $sel .= ', created_at';

        $stmt = $pdo->prepare("SELECT $sel FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 10");
        $stmt->execute([$user['id']]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { $orders = []; }

// ── Detect user columns ────────────────────────────────────
$userCols = getColumns($pdo, 'users');
$hasPhone  = in_array('phone',      $userCols);
$hasAvatar = in_array('avatar',     $userCols);

$pageTitle = 'My Account — ' . SITE_NAME;
$pageDesc  = 'Manage your Amaria Aesthetics account.';
include __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<div class="page-hero" style="padding:calc(var(--header-h) + 2rem) var(--gutter) 2rem">
  <div class="container">
    <div class="breadcrumb" style="margin-bottom:.75rem">
      <a href="<?= STORE_URL ?>/index.php">Home</a>
      <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
      <span>My Account</span>
    </div>
    <h1 style="font-size:clamp(1.8rem,3.5vw,3rem); position:relative; z-index:1">
      My Account
    </h1>
  </div>
</div>

<div class="container section-pad-sm">

  <?php if ($error): ?>
    <div class="auth-alert auth-alert-error" style="margin-bottom:1.5rem">
      <i class="fas fa-circle-xmark"></i> <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="auth-alert auth-alert-success" style="margin-bottom:1.5rem">
      <i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <div class="account-layout">

    <!-- ── SIDEBAR ── -->
    <aside class="account-sidebar">
      <!-- Avatar / name -->
      <div class="account-profile-card">
        <div class="account-avatar">
          <?php if ($hasAvatar && !empty($user['avatar'])): ?>
            <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
          <?php else: ?>
            <span><?= strtoupper(mb_substr($user['name'] ?? 'U', 0, 1)) ?></span>
          <?php endif; ?>
        </div>
        <div class="account-profile-info">
          <strong><?= htmlspecialchars($user['name'] ?? 'Guest') ?></strong>
          <span><?= htmlspecialchars($user['email'] ?? '') ?></span>
        </div>
      </div>

      <!-- Nav -->
      <nav class="account-nav">
        <?php $tabs = [
          'overview'  => ['fa-grid-2',       'Overview'],
          'orders'    => ['fa-bag-shopping',  'My Orders'],
          'profile'   => ['fa-user',          'Edit Profile'],
          'security'  => ['fa-lock',          'Password'],
          'wishlist'  => ['fa-heart',         'Wishlist'],
        ]; ?>
        <?php foreach ($tabs as $key => [$icon, $label]): ?>
          <button class="account-nav-btn <?= ($key === 'overview') ? 'is-active' : '' ?>"
                  onclick="switchTab('<?= $key ?>', this)">
            <i class="fas <?= $icon ?>"></i>
            <?= $label ?>
            <?php if ($key === 'orders' && count($orders)): ?>
              <span class="account-nav-badge"><?= count($orders) ?></span>
            <?php endif; ?>
          </button>
        <?php endforeach; ?>
        <div class="account-nav-divider"></div>
        <button class="account-nav-btn account-nav-danger" onclick="switchTab('delete', this)">
          <i class="fas fa-trash-can"></i> Delete Account
        </button>
        <div class="account-nav-divider"></div>
        <a href="<?= STORE_URL ?>/logout.php" class="account-nav-btn account-nav-logout">
          <i class="fas fa-arrow-right-from-bracket"></i> Sign Out
        </a>
      </nav>
    </aside>

    <!-- ── MAIN CONTENT ── -->
    <div class="account-content">

      <!-- OVERVIEW TAB -->
      <div id="tab-overview" class="account-tab is-active">
        <h2 class="tab-title">Overview</h2>

        <div class="account-stats">
          <div class="account-stat">
            <div class="account-stat-icon"><i class="fas fa-bag-shopping"></i></div>
            <div>
              <div class="account-stat-num"><?= count($orders) ?></div>
              <div class="account-stat-label">Orders</div>
            </div>
          </div>
          <div class="account-stat">
            <div class="account-stat-icon"><i class="fas fa-heart"></i></div>
            <div>
              <div class="account-stat-num">—</div>
              <div class="account-stat-label">Wishlist</div>
            </div>
          </div>
          <div class="account-stat">
            <div class="account-stat-icon"><i class="fas fa-star"></i></div>
            <div>
              <div class="account-stat-num">—</div>
              <div class="account-stat-label">Reviews</div>
            </div>
          </div>
        </div>

        <!-- Recent orders preview -->
        <?php if (!empty($orders)): ?>
          <h3 class="section-subtitle">Recent Orders</h3>
          <div class="orders-table-wrap">
            <table class="orders-table">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Date</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                  <tr>
                    <td><strong>#<?= (int)$order['id'] ?></strong></td>
                    <td><?= !empty($order['created_at']) ? date('M j, Y', strtotime($order['created_at'])) : '—' ?></td>
                    <td><?= !empty($order['total']) ? formatPrice((float)$order['total']) : '—' ?></td>
                    <td>
                      <?php $st = $order['status'] ?? 'pending'; ?>
                      <span class="order-status order-status-<?= htmlspecialchars($st) ?>">
                        <?= ucfirst(htmlspecialchars($st)) ?>
                      </span>
                    </td>
                    <td>
                      <a href="<?= STORE_URL ?>/order.php?id=<?= (int)$order['id'] ?>"
                         class="btn btn-outline btn-xs">View</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="account-empty">
            <i class="fas fa-bag-shopping"></i>
            <p>No orders yet.</p>
            <a href="<?= STORE_URL ?>/shop.php" class="btn btn-primary btn-sm">Start Shopping</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- ORDERS TAB -->
      <div id="tab-orders" class="account-tab">
        <h2 class="tab-title">My Orders</h2>
        <?php if (!empty($orders)): ?>
          <div class="orders-table-wrap">
            <table class="orders-table">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Date</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $order): ?>
                  <tr>
                    <td><strong>#<?= (int)$order['id'] ?></strong></td>
                    <td><?= !empty($order['created_at']) ? date('M j, Y', strtotime($order['created_at'])) : '—' ?></td>
                    <td><?= !empty($order['total']) ? formatPrice((float)$order['total']) : '—' ?></td>
                    <td>
                      <?php $st = $order['status'] ?? 'pending'; ?>
                      <span class="order-status order-status-<?= htmlspecialchars($st) ?>">
                        <?= ucfirst(htmlspecialchars($st)) ?>
                      </span>
                    </td>
                    <td>
                      <a href="<?= STORE_URL ?>/order.php?id=<?= (int)$order['id'] ?>"
                         class="btn btn-outline btn-xs">View</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="account-empty">
            <i class="fas fa-bag-shopping"></i>
            <p>You haven't placed any orders yet.</p>
            <a href="<?= STORE_URL ?>/shop.php" class="btn btn-primary btn-sm">Browse Products</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- PROFILE TAB -->
      <div id="tab-profile" class="account-tab">
        <h2 class="tab-title">Edit Profile</h2>
        <form method="POST" class="account-form">
          <input type="hidden" name="action" value="update_profile">

          <div class="auth-field">
            <label for="name">Full Name</label>
            <div class="auth-input-wrap">
              <i class="fas fa-user auth-input-icon"></i>
              <input type="text" id="name" name="name"
                     value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                     placeholder="Your full name" required>
            </div>
          </div>

          <div class="auth-field">
            <label for="acc_email">Email Address</label>
            <div class="auth-input-wrap">
              <i class="fas fa-envelope auth-input-icon"></i>
              <input type="email" id="acc_email"
                     value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                     disabled style="opacity:.6;cursor:not-allowed">
            </div>
            <p style="font-size:.73rem;color:var(--ink-soft);margin-top:.35rem">
              Email cannot be changed. Contact support if needed.
            </p>
          </div>

          <?php if ($hasPhone): ?>
          <div class="auth-field">
            <label for="phone">Phone Number</label>
            <div class="auth-input-wrap">
              <i class="fas fa-phone auth-input-icon"></i>
              <input type="tel" id="phone" name="phone"
                     value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                     placeholder="+63 9XX XXX XXXX">
            </div>
          </div>
          <?php endif; ?>

          <button type="submit" class="auth-submit" style="max-width:200px">
            Save Changes <i class="fas fa-check"></i>
          </button>
        </form>
      </div>

      <!-- SECURITY TAB -->
      <div id="tab-security" class="account-tab">
        <h2 class="tab-title">Change Password</h2>
        <form method="POST" class="account-form">
          <input type="hidden" name="action" value="change_password">

          <div class="auth-field">
            <label for="current_password">Current Password</label>
            <div class="auth-input-wrap">
              <i class="fas fa-lock auth-input-icon"></i>
              <input type="password" id="current_password" name="current_password"
                     placeholder="Enter current password" autocomplete="current-password">
              <button type="button" class="auth-eye" onclick="togglePassword(this)">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>

          <div class="auth-field">
            <label for="new_password">New Password</label>
            <div class="auth-input-wrap">
              <i class="fas fa-lock-open auth-input-icon"></i>
              <input type="password" id="new_password" name="new_password"
                     placeholder="Min 8 characters" autocomplete="new-password">
              <button type="button" class="auth-eye" onclick="togglePassword(this)">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>

          <div class="auth-field">
            <label for="confirm_password">Confirm New Password</label>
            <div class="auth-input-wrap">
              <i class="fas fa-lock-open auth-input-icon"></i>
              <input type="password" id="confirm_password" name="confirm_password"
                     placeholder="Repeat new password" autocomplete="new-password">
              <button type="button" class="auth-eye" onclick="togglePassword(this)">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="auth-submit" style="max-width:220px">
            Update Password <i class="fas fa-arrow-right"></i>
          </button>
        </form>
      </div>

      <!-- WISHLIST TAB -->
      <div id="tab-wishlist" class="account-tab">
        <h2 class="tab-title">Wishlist</h2>
        <div class="account-empty">
          <i class="fas fa-heart"></i>
          <p>Your wishlist is empty.</p>
          <a href="<?= STORE_URL ?>/shop.php" class="btn btn-primary btn-sm">
            Explore Products
          </a>
        </div>
      </div>

      <!-- DELETE ACCOUNT TAB -->
      <div id="tab-delete" class="account-tab">
        <h2 class="tab-title" style="color:#b5192e;border-bottom-color:#f3c8ce">Delete Account</h2>

        <div class="delete-warning">
          <i class="fas fa-triangle-exclamation"></i>
          <div>
            <strong>This action is permanent.</strong>
            <p>Deleting your account will remove your profile and order history and cannot be undone. If you'd rather just take a break, consider signing out instead.</p>
          </div>
        </div>

        <form method="POST" class="account-form" onsubmit="return confirm('Are you absolutely sure you want to permanently delete your account?');">
          <input type="hidden" name="action" value="delete_account">

          <div class="auth-field">
            <label for="delete_password">Confirm Your Password</label>
            <div class="auth-input-wrap">
              <i class="fas fa-lock auth-input-icon"></i>
              <input type="password" id="delete_password" name="delete_password"
                     placeholder="Enter your password" autocomplete="current-password" required>
              <button type="button" class="auth-eye" onclick="togglePassword(this)">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>

          <div class="auth-field">
            <label for="delete_confirm_text">Type <strong>DELETE</strong> to confirm</label>
            <div class="auth-input-wrap">
              <i class="fas fa-keyboard auth-input-icon"></i>
              <input type="text" id="delete_confirm_text" name="delete_confirm_text"
                     placeholder="DELETE" autocomplete="off" required>
            </div>
          </div>

          <button type="submit" class="auth-submit auth-submit-danger" style="max-width:220px">
            Delete My Account <i class="fas fa-trash-can"></i>
          </button>
        </form>
      </div>

    </div><!-- /account-content -->
  </div><!-- /account-layout -->
</div>

<!-- ── STYLES ── -->
<?php include __DIR__ . '/includes/auth-styles.php'; ?>

<style>
/* Account layout */
.account-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 2.5rem;
  align-items: start;
}

/* Sidebar */
.account-sidebar {
  position: sticky;
  top: calc(var(--header-h) + 1.5rem);
}

.account-profile-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: #fff;
  border-radius: var(--r-sm);
  border: 1px solid var(--mist);
  margin-bottom: 1rem;
}

.account-avatar {
  width: 52px; height: 52px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--blush), var(--rose-deep));
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; font-weight: 700; color: #fff;
  overflow: hidden; flex-shrink: 0;
}
.account-avatar img { width: 100%; height: 100%; object-fit: cover; }

.account-profile-info {
  min-width: 0;
  display: flex; flex-direction: column; gap: .15rem;
}
.account-profile-info strong {
  font-size: .92rem; color: var(--ink);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.account-profile-info span {
  font-size: .72rem; color: var(--ink-soft);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* Nav */
.account-nav {
  background: #fff;
  border: 1px solid var(--mist);
  border-radius: var(--r-sm);
  overflow: hidden;
  padding: .5rem;
  display: flex;
  flex-direction: column;
  gap: .15rem;
}

.account-nav-btn {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .75rem 1rem;
  border: none;
  background: none;
  color: var(--ink-soft);
  font-size: .85rem;
  font-family: var(--font-body);
  cursor: pointer;
  border-radius: var(--r-xs);
  text-align: left;
  text-decoration: none;
  transition: background .15s, color .15s;
  width: 100%;
}
.account-nav-btn i { width: 16px; text-align: center; font-size: .88rem; }
.account-nav-btn:hover    { background: var(--ivory-deep); color: var(--ink); }
.account-nav-btn.is-active { background: var(--blush); color: var(--rose); font-weight: 600; }
.account-nav-btn.is-active i { color: var(--rose); }
.account-nav-logout { color: #b5192e !important; }
.account-nav-logout:hover { background: #fff0f2 !important; }

.account-nav-danger { color: #b5192e !important; }
.account-nav-danger:hover { background: #fff0f2 !important; }
.account-nav-danger.is-active { background: #fff0f2 !important; color: #b5192e !important; }
.account-nav-danger.is-active i { color: #b5192e !important; }

.account-nav-badge {
  margin-left: auto;
  background: var(--rose);
  color: #fff;
  font-size: .65rem;
  font-weight: 700;
  padding: .1rem .45rem;
  border-radius: 99px;
  min-width: 18px;
  text-align: center;
}

.account-nav-divider {
  height: 1px;
  background: var(--mist);
  margin: .25rem 0;
}

/* Content area */
.account-content {
  background: #fff;
  border: 1px solid var(--mist);
  border-radius: var(--r-sm);
  padding: 2rem;
  min-height: 400px;
}

/* Tabs */
.account-tab { display: none; }
.account-tab.is-active { display: block; }

.tab-title {
  font-size: 1.4rem;
  margin: 0 0 1.75rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--mist);
}

/* Stats row */
.account-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.account-stat {
  background: var(--ivory-deep);
  border-radius: var(--r-sm);
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.account-stat-icon {
  width: 42px; height: 42px;
  background: var(--blush);
  border-radius: var(--r-xs);
  display: flex; align-items: center; justify-content: center;
  color: var(--rose);
  font-size: 1rem;
  flex-shrink: 0;
}

.account-stat-num   { font-size: 1.5rem; font-weight: 700; color: var(--mocha); line-height: 1; }
.account-stat-label { font-size: .72rem; color: var(--ink-soft); margin-top: .2rem; }

.section-subtitle {
  font-size: 1rem;
  margin: 0 0 1rem;
  color: var(--ink);
}

/* Orders table */
.orders-table-wrap { overflow-x: auto; }
.orders-table {
  width: 100%;
  border-collapse: collapse;
  font-size: .84rem;
}
.orders-table th {
  text-align: left;
  padding: .6rem .85rem;
  font-size: .68rem;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--rose);
  border-bottom: 1.5px solid var(--mist);
}
.orders-table td {
  padding: .9rem .85rem;
  border-bottom: 1px solid var(--mist-light, #f0ebe4);
  color: var(--ink);
  vertical-align: middle;
}
.orders-table tr:last-child td { border-bottom: none; }
.orders-table tr:hover td { background: var(--ivory-deep); }

/* Order status badges */
.order-status {
  display: inline-block;
  padding: .2rem .65rem;
  border-radius: 99px;
  font-size: .7rem;
  font-weight: 600;
  letter-spacing: .06em;
  text-transform: uppercase;
}
.order-status-pending    { background: #fff8e1; color: #b45309; }
.order-status-processing { background: #eff6ff; color: #1d4ed8; }
.order-status-shipped    { background: #f0fdf4; color: #15803d; }
.order-status-delivered  { background: #f0fdf4; color: #166534; }
.order-status-cancelled  { background: #fff0f2; color: #b5192e; }

/* Empty states */
.account-empty {
  text-align: center;
  padding: 3.5rem 1rem;
  color: var(--ink-soft);
}
.account-empty i {
  font-size: 2.5rem;
  color: var(--blush);
  display: block;
  margin-bottom: 1rem;
}
.account-empty p { margin-bottom: 1.25rem; font-size: .9rem; }

/* Form */
.account-form { max-width: 480px; }

/* btn-xs */
.btn-xs {
  padding: .3rem .7rem !important;
  font-size: .72rem !important;
}

/* Delete account tab */
.delete-warning {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
  background: #fff5f5;
  border: 1px solid #f3c8ce;
  border-radius: var(--r-sm);
  padding: 1.1rem 1.25rem;
  margin-bottom: 1.75rem;
  max-width: 560px;
}
.delete-warning i {
  color: #b5192e;
  font-size: 1.2rem;
  margin-top: .15rem;
  flex-shrink: 0;
}
.delete-warning strong { color: #b5192e; display: block; margin-bottom: .25rem; font-size: .9rem; }
.delete-warning p { font-size: .82rem; color: var(--ink-soft); margin: 0; line-height: 1.5; }

.auth-submit-danger {
  background: #b5192e !important;
  border-color: #b5192e !important;
}
.auth-submit-danger:hover {
  background: #931222 !important;
  border-color: #931222 !important;
}

/* ── RESPONSIVE ── */
@media (max-width: 900px) {
  .account-layout { grid-template-columns: 1fr; }
  .account-sidebar { position: static; }
  .account-nav {
    flex-direction: row;
    flex-wrap: wrap;
    gap: .25rem;
  }
  .account-nav-btn { flex: 1 1 auto; justify-content: center; min-width: 100px; }
  .account-nav-divider { display: none; }
}

@media (max-width: 600px) {
  .account-content { padding: 1.25rem; }
  .account-stats { grid-template-columns: 1fr 1fr; }
}
</style>

<script>
function switchTab(name, btn) {
  // Hide all tabs
  document.querySelectorAll('.account-tab').forEach(t => t.classList.remove('is-active'));
  document.querySelectorAll('.account-nav-btn').forEach(b => b.classList.remove('is-active'));
  // Show selected
  document.getElementById('tab-' + name)?.classList.add('is-active');
  btn.classList.add('is-active');
}

function togglePassword(btn) {
  const input = btn.closest('.auth-input-wrap').querySelector('input');
  const icon  = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('fa-eye','fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('fa-eye-slash','fa-eye');
  }
}

// Auto-switch tab if error/success came from a specific form
<?php if ($error || $success): ?>
  const action = <?= json_encode($_POST['action'] ?? '') ?>;
  if (action === 'change_password') switchTab('security',  document.querySelector('[onclick*="security"]'));
  if (action === 'update_profile')  switchTab('profile',   document.querySelector('[onclick*="profile"]'));
  if (action === 'delete_account')  switchTab('delete',    document.querySelector('[onclick*="delete"]'));
<?php endif; ?>
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>