<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Already logged in → redirect
if (isLoggedIn()) {
    header('Location: ' . STORE_URL . '/account.php');
    exit;
}

$error   = '';
$success = isset($_GET['registered']) ? 'Account created! Please sign in.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $remember = isset($_POST['remember']);

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Fetch user from DB
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $user = null;
        }

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } else {
            loginUser($user);

            if ($remember) {
                setcookie('remember_email', $email, time() + 60*60*24*30, '/', '', true, true);
            }

            $redirect = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : STORE_URL . '/account.php';
            header('Location: ' . $redirect);
            exit;
        }
    }
}

$pageTitle = 'Sign In — ' . SITE_NAME;
$pageDesc  = 'Sign in to your Amaria Aesthetics account.';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-page">

  <!-- Left decorative panel -->
  <div class="auth-panel" aria-hidden="true">
    <div class="auth-panel-inner">
      <div class="auth-panel-logo"><i class="fas fa-spa"></i></div>
      <blockquote class="auth-panel-quote">
        "True beauty isn't covered — it's uncovered, nurtured, and revealed."
      </blockquote>
      <p class="auth-panel-sub">— Amaria Aesthetics</p>
      <div class="petal petal-1"></div>
      <div class="petal petal-2"></div>
      <div class="petal petal-3"></div>
      <div class="petal petal-4"></div>
    </div>
  </div>

  <!-- Right form -->
  <div class="auth-form-side">
    <div class="auth-form-wrap">

      <a href="<?= STORE_URL ?>/index.php" class="auth-back">
        <i class="fas fa-arrow-left"></i> Back to store
      </a>

      <div class="auth-heading">
        <span class="auth-eyebrow">Welcome back</span>
        <h1>Sign In</h1>
        <p>Don't have an account?
          <a href="<?= STORE_URL ?>/signup.php">Create one free</a>
        </p>
      </div>

      <?php if ($error): ?>
        <div class="auth-alert auth-alert-error" role="alert">
          <i class="fas fa-circle-xmark"></i> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="auth-alert auth-alert-success" role="alert">
          <i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="auth-form" novalidate>

        <div class="auth-field">
          <label for="email">Email address</label>
          <div class="auth-input-wrap">
            <i class="fas fa-envelope auth-input-icon"></i>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="you@example.com" autocomplete="email" required>
          </div>
        </div>

        <div class="auth-field">
          <label for="password">
            Password
            <a href="<?= STORE_URL ?>/forgot-password.php" class="auth-forgot">Forgot password?</a>
          </label>
          <div class="auth-input-wrap">
            <i class="fas fa-lock auth-input-icon"></i>
            <input type="password" id="password" name="password"
                   placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="auth-eye" onclick="togglePassword(this)" aria-label="Toggle password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <label class="auth-check-label">
          <input type="checkbox" name="remember" <?= isset($_POST['remember']) ? 'checked' : '' ?>>
          <span class="auth-check-box"></span>
          Keep me signed in for 30 days
        </label>

        <button type="submit" class="auth-submit">
          <span>Sign In</span>
          <i class="fas fa-arrow-right"></i>
        </button>

      </form>
<!-- Fonts 
      // <div class="auth-divider"><span>or continue with</span></div>

      //<div class="auth-socials">
      //  <button class="auth-social-btn" type="button" onclick="showToast('Google login coming soon.','info')">
      //    <svg viewBox="0 0 24 24" width="18" height="18"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
          Google
       // </button>
      //  <button class="auth-social-btn" type="button" onclick="showToast('Facebook login coming soon.','info')">
       //   <i class="fab fa-facebook" style="color:#1877F2;font-size:1.1rem"></i>
      //    Facebook
      //  </button>
      </div>//
-->
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/auth-styles.php'; ?>

<script>
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
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>