<?php
ob_start();
session_start();
require_once '../config/database.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/../config/secrets.php';

$loginError    = '';
$registerError = '';
$registerSuccess = '';
$activeTab     = 'login';

// ── LOGIN ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $loginError = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            header("Location: dashboard.php");
            exit;
        } else {
            $loginError = 'Invalid email or password.';
        }
    }
}

// ── REGISTER ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $activeTab = 'register';

    $name     = trim($_POST['reg_name']     ?? '');
    $email    = trim($_POST['reg_email']    ?? '');
    $password = $_POST['reg_password']      ?? '';
    $confirm  = $_POST['reg_confirm']       ?? '';
    $secret   = trim($_POST['admin_secret'] ?? '');

    // Simple admin registration secret key — change this to something only you know
    $ADMIN_SECRET = ADMIN_SECRET;

    if (empty($name) || empty($email) || empty($password) || empty($confirm) || empty($secret)) {
        $registerError = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerError = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $registerError = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $registerError = 'Passwords do not match.';
    } elseif ($secret !== $ADMIN_SECRET) {
        $registerError = 'Invalid admin registration key.';
    } else {
        // Check if email already exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $registerError = 'An account with that email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $insert->execute([$name, $email, $hashed]);
            $registerSuccess = 'Admin account created successfully! You can now log in.';
            $activeTab = 'login';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — ShopaPay</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --mocha:  #5C3D2E;
      --rose:   #C4826A;
      --blush:  #EDD5C8;
      --ivory:  #FAF6F1;
      --mist:   #E5D8CF;
      --gold:   #C9A96E;
    }

    body {
      font-family: 'Jost', sans-serif;
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr 1fr;
      background: var(--ivory);
      overflow: hidden;
    }

    /* ── LEFT PANEL ── */
    .auth-visual {
      background: linear-gradient(160deg, var(--mocha) 0%, #3a2218 60%, #2a1810 100%);
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      padding: 3rem; text-align: center;
      position: relative; overflow: hidden;
    }
    .auth-visual::before {
      content: '';
      position: absolute; top: -30%; left: -20%; right: -20%;
      height: 70%;
      background: radial-gradient(ellipse, rgba(201,135,106,.18) 0%, transparent 65%);
    }
    .auth-visual::after {
      content: '';
      position: absolute; bottom: -10%; right: -10%;
      width: 300px; height: 300px;
      border: 1px solid rgba(201,169,110,.15); border-radius: 50%;
    }
    .brand-monogram {
      width: 80px; height: 80px;
      background: rgba(201,135,106,.15);
      border: 1px solid rgba(201,135,106,.3);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Playfair Display', serif;
      font-size: 2.2rem; font-style: italic;
      color: var(--blush); margin-bottom: 2rem;
      position: relative; z-index: 1;
    }
    .brand-title {
      font-family: 'Playfair Display', serif;
      font-size: 2rem; font-weight: 400;
      color: #fff; letter-spacing: .03em;
      position: relative; z-index: 1;
      margin-bottom: .4rem;
    }
    .brand-sub {
      font-size: .65rem; font-weight: 400;
      letter-spacing: .22em; text-transform: uppercase;
      color: var(--gold); margin-bottom: 2.5rem;
      position: relative; z-index: 1;
    }
    .brand-tagline {
      font-size: .85rem; line-height: 1.75;
      color: rgba(237,213,200,.5);
      max-width: 280px;
      position: relative; z-index: 1;
    }
    .brand-feature {
      display: flex; align-items: center; gap: .75rem;
      background: rgba(255,255,255,.05);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 10px; padding: .85rem 1.25rem;
      margin-top: .75rem; width: 100%; max-width: 300px;
      position: relative; z-index: 1;
    }
    .brand-feature i { color: var(--gold); font-size: .85rem; }
    .brand-feature span { font-size: .78rem; color: rgba(237,213,200,.6); }

    /* ── RIGHT PANEL ── */
    .auth-form-side {
      display: flex; align-items: center; justify-content: center;
      padding: 3rem 2rem; overflow-y: auto;
    }
    .auth-box { width: 100%; max-width: 400px; }

    .auth-heading {
      font-family: 'Playfair Display', serif;
      font-size: 2rem; font-weight: 400;
      color: var(--mocha); margin-bottom: .4rem;
    }
    .auth-sub { font-size: .85rem; color: #9a8680; margin-bottom: 2rem; }

    /* Tabs */
    .auth-tabs {
      display: flex; gap: 0;
      border-bottom: 1.5px solid var(--mist);
      margin-bottom: 2rem;
    }
    .auth-tab {
      flex: 1; padding: .75rem 1rem; background: none; border: none;
      font-family: 'Jost', sans-serif;
      font-size: .72rem; font-weight: 500;
      letter-spacing: .12em; text-transform: uppercase;
      color: #b0a09a; cursor: pointer;
      border-bottom: 2px solid transparent;
      margin-bottom: -1.5px; transition: all .25s;
    }
    .auth-tab:hover { color: var(--rose); }
    .auth-tab.active { color: var(--mocha); border-bottom-color: var(--rose); }

    /* Form fields */
    .field-wrap { margin-bottom: 1.1rem; }
    .field-label {
      display: block; margin-bottom: .4rem;
      font-size: .7rem; font-weight: 500;
      letter-spacing: .1em; text-transform: uppercase;
      color: #9a8680;
    }
    .field-input-wrap { position: relative; }
    .field-input {
      width: 100%; padding: .75rem 1rem .75rem 2.75rem;
      background: var(--ivory); border: 1.5px solid var(--mist);
      border-radius: 8px;
      font-family: 'Jost', sans-serif; font-size: .9rem; color: var(--mocha);
      outline: none; transition: border-color .25s, box-shadow .25s;
    }
    .field-input:focus {
      border-color: var(--rose);
      box-shadow: 0 0 0 4px rgba(196,130,106,.1);
    }
    .field-input::placeholder { color: #cfc4bf; }
    .field-icon {
      position: absolute; left: .9rem; top: 50%; transform: translateY(-50%);
      color: #cfc4bf; font-size: .8rem; pointer-events: none;
    }
    .field-toggle {
      position: absolute; right: .9rem; top: 50%; transform: translateY(-50%);
      background: none; border: none; color: #cfc4bf; cursor: pointer;
      font-size: .8rem; padding: .25rem;
    }
    .field-toggle:hover { color: var(--rose); }

    /* Strength indicator */
    .pw-strength { display: flex; gap: .3rem; margin-top: .45rem; }
    .pw-bar {
      flex: 1; height: 3px; border-radius: 2px;
      background: var(--mist); transition: background .3s;
    }
    .pw-label { font-size: .68rem; color: #b0a09a; margin-top: .3rem; }

    /* Secret key hint */
    .secret-hint {
      font-size: .72rem; color: #b0a09a; margin-top: .35rem;
      display: flex; align-items: center; gap: .35rem;
    }

    /* Submit button */
    .btn-auth {
      width: 100%; padding: .85rem 1rem;
      background: var(--mocha); color: var(--ivory);
      border: none; border-radius: 8px;
      font-family: 'Jost', sans-serif;
      font-size: .78rem; font-weight: 500;
      letter-spacing: .12em; text-transform: uppercase;
      cursor: pointer; transition: all .25s;
      display: flex; align-items: center; justify-content: center; gap: .6rem;
      margin-top: 1.5rem;
    }
    .btn-auth:hover { background: var(--rose); transform: translateY(-1px); box-shadow: 0 8px 24px rgba(92,61,46,.2); }
    .btn-auth:active { transform: translateY(0); }

    /* Alert */
    .auth-alert {
      display: flex; align-items: center; gap: .6rem;
      padding: .8rem 1rem; border-radius: 8px;
      font-size: .82rem; margin-bottom: 1.25rem;
    }
    .auth-alert-error   { background: #FFF1F2; color: #9F1239; border: 1px solid #FECDD3; }
    .auth-alert-success { background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; }

    /* Divider */
    .or-divider {
      display: flex; align-items: center; gap: .75rem;
      font-size: .7rem; color: #cfc4bf; margin: 1.25rem 0;
    }
    .or-divider::before, .or-divider::after {
      content: ''; flex: 1; height: 1px; background: var(--mist);
    }

    /* Footer note */
    .auth-footer-note {
      text-align: center; font-size: .73rem;
      color: #b0a09a; margin-top: 1.25rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
      body { grid-template-columns: 1fr; overflow: auto; }
      .auth-visual { display: none; }
      .auth-form-side { padding: 2rem 1.25rem; min-height: 100vh; }
    }
  </style>
</head>
<body>

<!-- ── LEFT: Brand Visual ── -->
<div class="auth-visual">
  <div class="brand-monogram">A</div>
  <h1 class="brand-title">ShopaPay</h1>
  <p class="brand-sub">Admin Dashboard</p>
  <p class="brand-tagline">
    Manage your Amaria Aesthetics store — orders, products, customers, and more from one place.
  </p>

  <div style="margin-top:2.5rem; display:flex; flex-direction:column; align-items:center; gap:.5rem; width:100%">
    <?php foreach ([
      ['fa-bag-shopping', 'Manage orders & fulfilment'],
      ['fa-box',          'Products & inventory'],
      ['fa-users',        'Customer management'],
      ['fa-chart-line',   'Sales & analytics'],
    ] as [$icon, $text]): ?>
      <div class="brand-feature">
        <i class="fas <?= $icon ?>"></i>
        <span><?= $text ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── RIGHT: Form ── -->
<div class="auth-form-side">
  <div class="auth-box">

    <!-- Register success banner (shown after tab switch) -->
    <?php if (!empty($registerSuccess)): ?>
      <div class="auth-alert auth-alert-success">
        <i class="fas fa-circle-check"></i>
        <?= htmlspecialchars($registerSuccess) ?>
      </div>
    <?php endif; ?>

    <p class="auth-heading">Welcome back</p>
    <p class="auth-sub">Sign in to access the admin dashboard</p>

    <!-- Tabs -->
    <div class="auth-tabs">
      <button class="auth-tab <?= $activeTab==='login'?'active':'' ?>"
              onclick="switchTab('login')" type="button">
        <i class="fas fa-sign-in-alt" style="margin-right:.4rem"></i> Sign In
      </button>
      <button class="auth-tab <?= $activeTab==='register'?'active':'' ?>"
              onclick="switchTab('register')" type="button">
        <i class="fas fa-user-plus" style="margin-right:.4rem"></i> Register Admin
      </button>
    </div>


    <!-- ══ LOGIN FORM ══════════════════════════════════════ -->
    <div id="panel-login" style="<?= $activeTab==='register'?'display:none':'' ?>">

      <?php if (!empty($loginError)): ?>
        <div class="auth-alert auth-alert-error">
          <i class="fas fa-circle-xmark"></i>
          <?= htmlspecialchars($loginError) ?>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <input type="hidden" name="action" value="login">

        <div class="field-wrap">
          <label class="field-label">Email Address</label>
          <div class="field-input-wrap">
            <i class="fas fa-envelope field-icon"></i>
            <input type="email" name="email" class="field-input"
                   placeholder="admin@amaria.com" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
        </div>

        <div class="field-wrap">
          <label class="field-label">Password</label>
          <div class="field-input-wrap">
            <i class="fas fa-lock field-icon"></i>
            <input type="password" name="password" id="loginPw"
                   class="field-input" placeholder="••••••••" required>
            <button type="button" class="field-toggle" onclick="togglePw('loginPw', this)">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-auth">
          <i class="fas fa-arrow-right-to-bracket"></i>
          Sign In to Dashboard
        </button>
      </form>

    </div><!-- /login -->


    <!-- ══ REGISTER FORM ═══════════════════════════════════ -->
    <div id="panel-register" style="<?= $activeTab==='login'?'display:none':'' ?>">

      <?php if (!empty($registerError)): ?>
        <div class="auth-alert auth-alert-error">
          <i class="fas fa-circle-xmark"></i>
          <?= htmlspecialchars($registerError) ?>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <input type="hidden" name="action" value="register">

        <div class="field-wrap">
          <label class="field-label">Full Name</label>
          <div class="field-input-wrap">
            <i class="fas fa-user field-icon"></i>
            <input type="text" name="reg_name" class="field-input"
                   placeholder="Maria Santos" required
                   value="<?= htmlspecialchars($_POST['reg_name'] ?? '') ?>">
          </div>
        </div>

        <div class="field-wrap">
          <label class="field-label">Email Address</label>
          <div class="field-input-wrap">
            <i class="fas fa-envelope field-icon"></i>
            <input type="email" name="reg_email" class="field-input"
                   placeholder="admin@amaria.com" required
                   value="<?= htmlspecialchars($_POST['reg_email'] ?? '') ?>">
          </div>
        </div>

        <div class="field-wrap">
          <label class="field-label">Password</label>
          <div class="field-input-wrap">
            <i class="fas fa-lock field-icon"></i>
            <input type="password" name="reg_password" id="regPw"
                   class="field-input" placeholder="Min. 8 characters" required
                   oninput="checkStrength(this.value)">
            <button type="button" class="field-toggle" onclick="togglePw('regPw', this)">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <!-- Strength bars -->
          <div class="pw-strength">
            <div class="pw-bar" id="bar1"></div>
            <div class="pw-bar" id="bar2"></div>
            <div class="pw-bar" id="bar3"></div>
            <div class="pw-bar" id="bar4"></div>
          </div>
          <p class="pw-label" id="pwLabel">Enter a password</p>
        </div>

        <div class="field-wrap">
          <label class="field-label">Confirm Password</label>
          <div class="field-input-wrap">
            <i class="fas fa-lock field-icon"></i>
            <input type="password" name="reg_confirm" id="regConfirm"
                   class="field-input" placeholder="Re-enter password" required>
            <button type="button" class="field-toggle" onclick="togglePw('regConfirm', this)">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="field-wrap">
          <label class="field-label">Admin Registration Key</label>
          <div class="field-input-wrap">
            <i class="fas fa-key field-icon"></i>
            <input type="password" name="admin_secret" class="field-input"
                   placeholder="Enter secret key" required>
          </div>
          <p class="secret-hint">
            <i class="fas fa-circle-info"></i>
            Contact your system administrator for the registration key.
          </p>
        </div>

        <button type="submit" class="btn-auth" style="background:var(--rose)">
          <i class="fas fa-user-shield"></i>
          Create Admin Account
        </button>
      </form>

    </div><!-- /register -->

    <p class="auth-footer-note">
      © <?= date('Y') ?> Amaria Aesthetics · ShopaPay Admin
    </p>

  </div>
</div>

<script>
// Tab switching
function switchTab(tab) {
  document.getElementById('panel-login').style.display    = tab === 'login'    ? '' : 'none';
  document.getElementById('panel-register').style.display = tab === 'register' ? '' : 'none';
  document.querySelectorAll('.auth-tab').forEach((btn, i) => {
    btn.classList.toggle('active', (i === 0 && tab === 'login') || (i === 1 && tab === 'register'));
  });
}

// Show/hide password
function togglePw(id, btn) {
  const input = document.getElementById(id);
  const icon  = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
}

// Password strength
function checkStrength(pw) {
  const bars   = [1,2,3,4].map(i => document.getElementById('bar' + i));
  const label  = document.getElementById('pwLabel');
  const colors = ['#EF4444','#F97316','#EAB308','#22C55E'];
  const labels = ['Too weak','Fair','Good','Strong'];

  let score = 0;
  if (pw.length >= 8)              score++;
  if (/[A-Z]/.test(pw))           score++;
  if (/[0-9]/.test(pw))           score++;
  if (/[^A-Za-z0-9]/.test(pw))    score++;

  bars.forEach((bar, i) => {
    bar.style.background = i < score ? colors[score - 1] : 'var(--mist)';
  });
  label.textContent = pw.length === 0 ? 'Enter a password' : labels[score - 1] || 'Too weak';
  label.style.color = pw.length === 0 ? '#b0a09a' : colors[score - 1];
}

// Auto-switch tab on page load if register had an error
<?php if ($activeTab === 'register'): ?>
switchTab('register');
<?php endif; ?>
</script>

</body>
</html>