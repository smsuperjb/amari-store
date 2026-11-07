<?php
ob_start();
session_start();
require_once '../config/database.php';

// ── SECURITY KEY — change this before uploading ───────────────
// You must enter this key on the page to be allowed to reset
require_once __DIR__ . '/../config/secrets.php';

$step    = 'auth';   // auth → select → done
$error   = '';
$success = '';
$admins  = [];

// ── STEP 1: Verify secret key ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === 'auth') {
    if (trim($_POST['secret_key'] ?? '') === RESET_SECRET) {
        $_SESSION['pw_reset_auth'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = 'Incorrect security key.';
        $step  = 'auth';
    }
}

// If authenticated via session, show admin list
if (!empty($_SESSION['pw_reset_auth'])) {
    $step = 'select';

    // Fetch all admin users
    try {
        $admins = $pdo->query("SELECT id, name, email FROM users WHERE role = 'admin' ORDER BY name")
                      ->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Try without role filter if column doesn't exist
        try {
            $admins = $pdo->query("SELECT id, name, email FROM users ORDER BY name")
                          ->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e2) {
            $error = 'Could not load users: ' . $e2->getMessage();
        }
    }
}

// ── STEP 2: Reset password ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === 'reset') {
    if (empty($_SESSION['pw_reset_auth'])) {
        $error = 'Unauthorized.';
        $step  = 'auth';
    } else {
        $userId      = (int)($_POST['user_id']    ?? 0);
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirm     = trim($_POST['confirm_pw']   ?? '');

        if (!$userId) {
            $error = 'Please select an admin account.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($newPassword !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $userId]);

                if ($stmt->rowCount() > 0) {
                    $success = true;
                    unset($_SESSION['pw_reset_auth']); // clear session after success
                    $step = 'done';
                } else {
                    $error = 'User not found. No changes made.';
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }

        if ($step !== 'done') $step = 'select';
    }
}

// ── LOGOUT / CANCEL ───────────────────────────────────────────
if (isset($_GET['cancel'])) {
    unset($_SESSION['pw_reset_auth']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Password Reset — ShopaPay</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --mocha:  #5C3D2E;
      --rose:   #C4826A;
      --blush:  #EDD5C8;
      --ivory:  #FAF6F1;
      --mist:   #E5D8CF;
      --gold:   #C9A96E;
      --ink:    #1E1310;
      --green:  #166534;
      --green-bg: #F0FDF4;
      --green-border: #BBF7D0;
      --red:    #9F1239;
      --red-bg: #FFF1F2;
      --red-border: #FECDD3;
    }

    body {
      font-family: 'Jost', sans-serif;
      background: var(--ivory);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
    }

    .card {
      width: 100%; max-width: 480px;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(92,61,46,.14);
      overflow: hidden;
    }

    /* Header */
    .card-header {
      background: linear-gradient(135deg, var(--mocha) 0%, #3a2218 100%);
      padding: 2.25rem 2.5rem;
      text-align: center;
      position: relative;
    }
    .card-header::after {
      content: '';
      position: absolute; bottom: 0; left: 0; right: 0; height: 1px;
      background: linear-gradient(to right, transparent, rgba(201,169,110,.4), transparent);
    }
    .header-icon {
      width: 64px; height: 64px;
      background: rgba(201,130,106,.15);
      border: 1.5px solid rgba(201,130,106,.3);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1rem;
      font-size: 1.4rem; color: var(--blush);
    }
    .card-header h1 {
      font-family: 'Playfair Display', serif;
      font-size: 1.5rem; font-weight: 400;
      color: #fff; margin-bottom: .3rem;
    }
    .card-header p {
      font-size: .75rem; color: rgba(237,213,200,.55);
      letter-spacing: .06em;
    }

    /* Step indicator */
    .steps {
      display: flex; align-items: center; justify-content: center;
      gap: .5rem; padding: 1.25rem 2.5rem;
      background: var(--ivory); border-bottom: 1px solid var(--mist);
    }
    .step-dot {
      width: 28px; height: 28px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: .7rem; font-weight: 600;
      transition: all .3s;
    }
    .step-dot.active  { background: var(--mocha); color: #fff; }
    .step-dot.done    { background: var(--gold);  color: #fff; }
    .step-dot.pending { background: var(--mist);  color: #aaa; }
    .step-line { flex: 1; max-width: 40px; height: 1px; background: var(--mist); }
    .step-text { font-size: .65rem; letter-spacing: .1em; text-transform: uppercase; }
    .step-text.active { color: var(--mocha); font-weight: 600; }
    .step-text.done   { color: var(--gold); }
    .step-text.pending{ color: #ccc; }

    /* Body */
    .card-body { padding: 2rem 2.5rem; }

    /* Alert */
    .alert {
      display: flex; align-items: flex-start; gap: .7rem;
      padding: .85rem 1.1rem; border-radius: 10px;
      font-size: .82rem; margin-bottom: 1.5rem; line-height: 1.5;
    }
    .alert i { margin-top: 1px; flex-shrink: 0; }
    .alert-error   { background: var(--red-bg);   color: var(--red);   border: 1px solid var(--red-border); }
    .alert-success { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-border); }
    .alert-info    { background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; }

    /* Form elements */
    .field-wrap { margin-bottom: 1.1rem; }
    .field-label {
      display: block; margin-bottom: .4rem;
      font-size: .68rem; font-weight: 600;
      letter-spacing: .1em; text-transform: uppercase; color: #9a8680;
    }
    .field-wrap-icon { position: relative; }
    .field-icon {
      position: absolute; left: .9rem; top: 50%;
      transform: translateY(-50%);
      color: #cfc4bf; font-size: .8rem; pointer-events: none;
    }
    .field-toggle {
      position: absolute; right: .9rem; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      color: #cfc4bf; cursor: pointer; font-size: .8rem; padding: .2rem;
    }
    .field-toggle:hover { color: var(--rose); }
    input[type="text"],
    input[type="password"],
    select {
      width: 100%;
      padding: .78rem 1rem .78rem 2.7rem;
      background: var(--ivory);
      border: 1.5px solid var(--mist);
      border-radius: 10px;
      font-family: 'Jost', sans-serif;
      font-size: .9rem; color: var(--ink);
      outline: none;
      transition: border-color .25s, box-shadow .25s;
      appearance: none;
    }
    input:focus, select:focus {
      border-color: var(--rose);
      box-shadow: 0 0 0 4px rgba(196,130,106,.1);
    }
    input::placeholder { color: #cfc4bf; }
    select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8'%3E%3Cpath fill='%23C4826A' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 1rem center;
      background-size: 10px; padding-right: 2.5rem; }

    /* Strength */
    .pw-bars { display: flex; gap: .3rem; margin-top: .45rem; }
    .pw-bar  {
      flex: 1; height: 3px; border-radius: 2px;
      background: var(--mist); transition: background .3s;
    }
    .pw-hint { font-size: .7rem; color: #b0a09a; margin-top: .3rem; min-height: 1.2em; }

    /* Match indicator */
    .match-hint { font-size: .7rem; margin-top: .3rem; min-height: 1.2em; }
    .match-ok   { color: #16a34a; }
    .match-no   { color: #dc2626; }

    /* Buttons */
    .btn {
      width: 100%; padding: .88rem 1rem;
      border: none; border-radius: 10px;
      font-family: 'Jost', sans-serif;
      font-size: .78rem; font-weight: 600;
      letter-spacing: .12em; text-transform: uppercase;
      cursor: pointer; transition: all .25s;
      display: flex; align-items: center; justify-content: center; gap: .6rem;
    }
    .btn-primary { background: var(--mocha); color: #fff; }
    .btn-primary:hover { background: var(--rose); transform: translateY(-1px); box-shadow: 0 8px 24px rgba(92,61,46,.2); }
    .btn-ghost {
      background: transparent; color: var(--mocha);
      border: 1.5px solid var(--mist); margin-top: .65rem;
      font-size: .72rem;
    }
    .btn-ghost:hover { border-color: var(--rose); color: var(--rose); }

    /* Success state */
    .success-icon {
      width: 72px; height: 72px;
      background: linear-gradient(135deg, var(--rose), var(--mocha));
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.5rem;
      font-size: 1.8rem; color: #fff;
      box-shadow: 0 12px 32px rgba(92,61,46,.25);
    }

    /* Delete warning */
    .delete-warning {
      background: #FFF7ED; border: 1px solid #FED7AA;
      border-radius: 10px; padding: 1rem 1.1rem;
      font-size: .78rem; color: #92400E;
      display: flex; gap: .6rem; align-items: flex-start;
      margin-top: 1.5rem;
    }

    /* Footer */
    .card-footer {
      padding: 1rem 2.5rem 1.5rem;
      text-align: center;
      font-size: .7rem; color: #ccc;
      border-top: 1px solid var(--mist);
    }
  </style>
</head>
<body>

<div class="card">

  <!-- Header -->
  <div class="card-header">
    <div class="header-icon">
      <i class="fas fa-key"></i>
    </div>
    <h1>Password Reset</h1>
    <p>ShopaPay Admin Panel</p>
  </div>

  <!-- Step indicator -->
  <div class="steps">
    <span class="step-dot <?= $step==='auth'?'active':($step!=='auth'?'done':'pending') ?>">
      <?= $step !== 'auth' ? '<i class="fas fa-check"></i>' : '1' ?>
    </span>
    <div class="step-line"></div>
    <span style="font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;color:<?= $step==='auth'?'var(--mocha)':'#ccc' ?>;font-weight:<?= $step==='auth'?'600':'400' ?>">Verify</span>
    <div class="step-line"></div>
    <span class="step-dot <?= $step==='select'?'active':($step==='done'?'done':'pending') ?>">
      <?= $step === 'done' ? '<i class="fas fa-check"></i>' : '2' ?>
    </span>
    <div class="step-line"></div>
    <span style="font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;color:<?= $step==='select'?'var(--mocha)':'#ccc' ?>;font-weight:<?= $step==='select'?'600':'400' ?>">Reset</span>
    <div class="step-line"></div>
    <span class="step-dot <?= $step==='done'?'active':'pending' ?>">3</span>
    <div class="step-line"></div>
    <span style="font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;color:<?= $step==='done'?'var(--mocha)':'#ccc' ?>;font-weight:<?= $step==='done'?'600':'400' ?>">Done</span>
  </div>

  <div class="card-body">

    <?php if (!empty($error)): ?>
      <div class="alert alert-error">
        <i class="fas fa-circle-xmark"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>


    <!-- ══ STEP 1: Auth ══════════════════════════════════════ -->
    <?php if ($step === 'auth'): ?>

      <div class="alert alert-info">
        <i class="fas fa-circle-info"></i>
        Enter the security key to access the password reset tool.
        This key is set inside the PHP file.
      </div>

      <form method="POST">
        <input type="hidden" name="step" value="auth">

        <div class="field-wrap">
          <label class="field-label">Security Key</label>
          <div class="field-wrap-icon">
            <i class="fas fa-shield-halved field-icon"></i>
            <input type="password" name="secret_key" id="secretKey"
                   placeholder="Enter reset security key" autofocus required>
            <button type="button" class="field-toggle" onclick="togglePw('secretKey',this)">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fas fa-unlock"></i> Verify & Continue
        </button>
      </form>

      <a href="login.php" class="btn btn-ghost" style="margin-top:.65rem; display:flex">
        <i class="fas fa-arrow-left"></i> Back to Login
      </a>


    <!-- ══ STEP 2: Select & Reset ════════════════════════════ -->
    <?php elseif ($step === 'select'): ?>

      <?php if (empty($admins)): ?>
        <div class="alert alert-error">
          <i class="fas fa-circle-xmark"></i>
          No admin accounts found in the database.
        </div>
      <?php else: ?>

        <form method="POST" id="resetForm">
          <input type="hidden" name="step" value="reset">

          <!-- Select admin -->
          <div class="field-wrap">
            <label class="field-label">Select Admin Account</label>
            <div class="field-wrap-icon">
              <i class="fas fa-user field-icon"></i>
              <select name="user_id" required onchange="showSelected(this)">
                <option value="">— Choose an account —</option>
                <?php foreach ($admins as $a): ?>
                  <option value="<?= (int)$a['id'] ?>"
                          data-email="<?= htmlspecialchars($a['email']) ?>"
                          <?= ($_POST['user_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($a['name']) ?> — <?= htmlspecialchars($a['email']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Selected badge -->
          <div id="selectedBadge" style="display:none; margin-bottom:1.1rem;
               background:var(--ivory); border:1px solid var(--mist); border-radius:10px;
               padding:.75rem 1rem; font-size:.8rem; color:var(--mocha);
               display:flex; align-items:center; gap:.6rem">
            <i class="fas fa-circle-user" style="color:var(--rose)"></i>
            <span id="selectedEmail"></span>
          </div>

          <!-- New password -->
          <div class="field-wrap">
            <label class="field-label">New Password</label>
            <div class="field-wrap-icon">
              <i class="fas fa-lock field-icon"></i>
              <input type="password" name="new_password" id="newPw"
                     placeholder="Min. 8 characters" required
                     oninput="checkStrength(this.value); checkMatch()">
              <button type="button" class="field-toggle" onclick="togglePw('newPw',this)">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <div class="pw-bars">
              <div class="pw-bar" id="b1"></div>
              <div class="pw-bar" id="b2"></div>
              <div class="pw-bar" id="b3"></div>
              <div class="pw-bar" id="b4"></div>
            </div>
            <p class="pw-hint" id="pwHint">Enter a password</p>
          </div>

          <!-- Confirm password -->
          <div class="field-wrap">
            <label class="field-label">Confirm New Password</label>
            <div class="field-wrap-icon">
              <i class="fas fa-lock field-icon"></i>
              <input type="password" name="confirm_pw" id="confirmPw"
                     placeholder="Re-enter new password" required
                     oninput="checkMatch()">
              <button type="button" class="field-toggle" onclick="togglePw('confirmPw',this)">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <p class="match-hint" id="matchHint"></p>
          </div>

          <button type="submit" class="btn btn-primary" onclick="return confirmReset()">
            <i class="fas fa-rotate"></i> Reset Password
          </button>

          <a href="?cancel=1" class="btn btn-ghost" style="display:flex">
            <i class="fas fa-xmark"></i> Cancel
          </a>
        </form>

      <?php endif; ?>


    <!-- ══ STEP 3: Done ══════════════════════════════════════ -->
    <?php elseif ($step === 'done'): ?>

      <div style="text-align:center; padding:1rem 0">
        <div class="success-icon">
          <i class="fas fa-check"></i>
        </div>
        <h2 style="font-family:'Playfair Display',serif; font-size:1.6rem;
                   color:var(--mocha); margin-bottom:.5rem">
          Password Updated!
        </h2>
        <p style="font-size:.85rem; color:#9a8680; line-height:1.7; margin-bottom:2rem">
          The admin password has been successfully changed.
          You can now log in with the new password.
        </p>
        <a href="login.php" class="btn btn-primary" style="text-decoration:none">
          <i class="fas fa-arrow-right-to-bracket"></i> Go to Login
        </a>
      </div>

    <?php endif; ?>

    <!-- Delete warning -->
    <?php if ($step !== 'done'): ?>
      <div class="delete-warning">
        <i class="fas fa-triangle-exclamation" style="flex-shrink:0; margin-top:1px"></i>
        <span>
          <strong>Security reminder:</strong> Delete this file
          (<code>reset_password.php</code>) from your server immediately after use.
        </span>
      </div>
    <?php endif; ?>

  </div>

  <div class="card-footer">
    © <?= date('Y') ?> Amaria Aesthetics · ShopaPay Admin
  </div>
</div>

<script>
function togglePw(id, btn) {
  const input = document.getElementById(id);
  const icon  = btn.querySelector('i');
  input.type  = input.type === 'password' ? 'text' : 'password';
  icon.classList.toggle('fa-eye',       input.type === 'password');
  icon.classList.toggle('fa-eye-slash', input.type === 'text');
}

function checkStrength(pw) {
  const bars   = ['b1','b2','b3','b4'].map(id => document.getElementById(id));
  const hint   = document.getElementById('pwHint');
  if (!bars[0]) return;

  const colors = ['#EF4444','#F97316','#EAB308','#22C55E'];
  const labels = ['Too weak — add numbers & symbols','Fair — try mixing cases','Good — almost there!','Strong password ✓'];

  let score = 0;
  if (pw.length >= 8)           score++;
  if (/[A-Z]/.test(pw))        score++;
  if (/[0-9]/.test(pw))        score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;

  bars.forEach((bar, i) => {
    bar.style.background = i < score ? colors[score - 1] : 'var(--mist)';
  });

  if (pw.length === 0) {
    hint.textContent = 'Enter a password';
    hint.style.color = '#b0a09a';
  } else {
    hint.textContent = labels[score - 1] || 'Too weak';
    hint.style.color = colors[score - 1];
  }
}

function checkMatch() {
  const pw      = document.getElementById('newPw')?.value;
  const confirm = document.getElementById('confirmPw')?.value;
  const hint    = document.getElementById('matchHint');
  if (!hint || !confirm) return;

  if (confirm.length === 0) {
    hint.textContent = '';
  } else if (pw === confirm) {
    hint.textContent = '✓ Passwords match';
    hint.className   = 'match-hint match-ok';
  } else {
    hint.textContent = '✗ Passwords do not match';
    hint.className   = 'match-hint match-no';
  }
}

function showSelected(sel) {
  const badge = document.getElementById('selectedBadge');
  const email = document.getElementById('selectedEmail');
  if (!badge) return;
  const opt = sel.options[sel.selectedIndex];
  if (sel.value) {
    badge.style.display = 'flex';
    email.textContent   = opt.dataset.email || opt.text;
  } else {
    badge.style.display = 'none';
  }
}

function confirmReset() {
  const sel = document.querySelector('select[name="user_id"]');
  const opt = sel?.options[sel.selectedIndex];
  const name = opt ? opt.text.split('—')[0].trim() : 'this account';
  return confirm(`Reset password for ${name}?\n\nThis cannot be undone.`);
}
</script>

</body>
</html>