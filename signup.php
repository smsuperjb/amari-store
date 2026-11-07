<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Already logged in → redirect
if (isLoggedIn()) {
    header('Location: ' . STORE_URL . '/account.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $agree    = isset($_POST['agree']);

    if (!$name || !$email || !$phone || !$password || !$confirm) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($phone) < 10) {
        $error = 'Please enter a valid phone number.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!$agree) {
        $error = 'Please agree to the Terms and Conditions.';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, phone, password, created_at)
                     VALUES (?, ?, ?, ?, NOW())"
                );
                $stmt->execute([$name, $email, $phone, $hashed]);

                header('Location: ' . STORE_URL . '/login.php?registered=1');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Something went wrong. Please try again.';
        }
    }
}

$pageTitle = 'Create Account — ' . SITE_NAME;
$pageDesc  = 'Create your Amaria Aesthetics account.';
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

      <a href="<?= STORE_URL ?>/index" class="auth-back">
        <i class="fas fa-arrow-left"></i></i> Back to store
      </a>

      <div class="auth-heading">
        <span class="auth-eyebrow">Join Amari</span>
        <h1>Create Account</h1>
        <p>Already have an account?
          <a href="<?= STORE_URL ?>/login">Sign in</a>
        </p>
      </div>

      <?php if ($error): ?>
        <div class="auth-alert auth-alert-error" role="alert">
          <i class="fas fa-circle-xmark"></i> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="auth-form" novalidate>

        <div class="auth-field">
          <label for="name">Full name</label>
          <div class="auth-input-wrap">
            <i class="fas fa-user auth-input-icon"></i>
            <input type="text" id="name" name="name"
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                   placeholder="Juan Dela Cruz" autocomplete="name" required>
          </div>
        </div>

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
          <label for="phone">Phone number</label>
          <div class="auth-input-wrap">
            <i class="fas fa-phone auth-input-icon"></i>
            <input type="tel" id="phone" name="phone"
                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                   placeholder="09XXXXXXXXX" autocomplete="tel" required>
          </div>
        </div>

        <div class="auth-field">
          <label for="password">Password</label>
          <div class="auth-input-wrap">
            <i class="fas fa-lock auth-input-icon"></i>
            <input type="password" id="password" name="password"
                   placeholder="At least 6 characters" autocomplete="new-password" required>
            <button type="button" class="auth-eye" onclick="togglePassword(this)" aria-label="Toggle password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="auth-field">
          <label for="confirm_password">Confirm password</label>
          <div class="auth-input-wrap">
            <i class="fas fa-lock auth-input-icon"></i>
            <input type="password" id="confirm_password" name="confirm_password"
                   placeholder="Re-enter your password" autocomplete="new-password" required>
            <button type="button" class="auth-eye" onclick="togglePassword(this)" aria-label="Toggle password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <label class="auth-check-label">
          <input type="checkbox" name="agree" <?= isset($_POST['agree']) ? 'checked' : '' ?>>
          <span class="auth-check-box"></span>
          I agree to the <a href="<?= STORE_URL ?>/terms.php" target="_blank">Terms and Conditions</a>
        </label>

        <button type="submit" class="auth-submit">
          <span>Create Account</span>
          <i class="fas fa-arrow-right"></i>
        </button>

      </form>

     

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