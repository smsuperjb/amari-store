<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pageTitle = 'Checkout — ' . SITE_NAME;
$cartItems = getCartWithProducts($pdo);
if (empty($cartItems)) { header('Location: ' . STORE_URL . '/cart.php'); exit; }

$subtotal = array_sum(array_column($cartItems, 'subtotal'));
$shipping = $subtotal >= 1500 ? 0 : 150;
$total    = $subtotal + $shipping;

$user   = currentUser();
$errors = [];
$success = false;
$orderId = '';

// ── HANDLE ORDER SUBMISSION ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    
    $fields = ['first_name','last_name','email','phone','address','region','city','barangay','payment_method'];
    
    foreach ($fields as $f) {
        if ($f === 'province' && ($_POST['region_code'] ?? '') === '130000000') continue;
        if (empty(trim($_POST[$f] ?? ''))) {
            $errors[] = ucfirst(str_replace('_', ' ', $f)) . ' is required.';
        }
    }
    
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $fn = trim($_POST['first_name']);
        $ln = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $addr = trim($_POST['address']);
        $prov = trim($_POST['province'] ?? 'Metro Manila');
        $city = trim($_POST['city']);
        $brgy = trim($_POST['barangay']);
        $payment = trim($_POST['payment_method']);
        $notes = trim($_POST['notes'] ?? '');

        $customer_name = "$fn $ln";
        $full_address = "$addr, $brgy, $city, $prov";

        $orderId = generateOrderId();
        $user_id = isLoggedIn() ? $user['id'] : null;

        // === BUILD ITEMS SUMMARY STRING ===
        $items_list = [];
        foreach ($cartItems as $item) {
            $name = $item['product']['name'];
            $qty = (int)$item['qty'];
            $items_list[] = "$name ×$qty";
        }
        $items_summary = implode(', ', $items_list);

        $sql = "INSERT INTO orders (
                    order_id, 
                    user_id, 
                    customer_name, 
                    email, 
                    phone, 
                    full_address, 
                    province, 
                    city, 
                    barangay, 
                    items_summary, 
                    total_amount, 
                    address, 
                    payment_method, 
                    payment_status, 
                    created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )";

        $values = [
            $orderId,
            $user_id,
            $customer_name,
            $email,
            $phone,
            $full_address,
            $prov,
            $city,
            $brgy,
            $items_summary,           // ← Items saved here
            $total,
            $addr,
            $payment,
            'Pending',
            date('Y-m-d H:i:s')
        ];

        try {
            $pdo->prepare($sql)->execute($values);

            // Optional: Create guest account
            if (!isLoggedIn() && !empty($_POST['create_account']) && !empty($_POST['password'])) {
                $pw = password_hash($_POST['password'], PASSWORD_DEFAULT);
                try {
                    $pdo->prepare("INSERT IGNORE INTO users (name, email, password) VALUES (?, ?, ?)")
                        ->execute([$customer_name, $email, $pw]);
                } catch (Exception $e) {}
            }

            clearCart();
            $success = true;
        } catch (Exception $e) {
            $errors[] = 'Could not place order. Please try again. (' . $e->getMessage() . ')';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="page-hero" style="padding:calc(var(--header-h) + 2rem) var(--gutter) 2rem">
  <div class="container">
    <div class="breadcrumb" style="margin-bottom:.75rem">
      <a href="<?= STORE_URL ?>/index.php">Home</a>
      <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
      <a href="<?= STORE_URL ?>/cart.php">Cart</a>
      <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
      <span>Checkout</span>
    </div>
    <h1 style="font-size:clamp(1.8rem,3.5vw,3rem); position:relative; z-index:1">Checkout</h1>

    <div class="checkout-steps" style="margin-top:1.5rem; justify-content:flex-start; gap:1rem">
      <div class="step <?= !$success ? 'active' : 'done' ?>">
        <span class="step-num"><?= $success ? '<i class="fas fa-check"></i>' : '1' ?></span>
        <span>Details</span>
      </div>
      <span class="step-line"></span>
      <div class="step <?= $success ? 'active' : '' ?>">
        <span class="step-num">2</span>
        <span>Confirmation</span>
      </div>
    </div>
  </div>
</div>

<div class="container section-pad-sm">

  <?php if ($success): ?>
    <!-- ── SUCCESS ── -->
    <div style="max-width:580px; margin:0 auto; text-align:center; padding:3rem 1rem">
      <div style="width:88px; height:88px; background:linear-gradient(135deg,var(--rose),var(--mocha));
                  border-radius:50%; display:flex; align-items:center; justify-content:center;
                  margin:0 auto 2rem; font-size:2rem; color:#fff; box-shadow:var(--sh-lg)">
        <i class="fas fa-check"></i>
      </div>
      <h2 style="margin-bottom:.75rem">Order Placed Successfully!</h2>
      <p style="color:var(--ink-soft); margin-bottom:.5rem">Your order ID is:</p>
      <div style="display:inline-block; padding:.6rem 1.5rem; background:var(--mocha); color:var(--ivory);
                  border-radius:var(--r-pill); font-weight:600; letter-spacing:.1em;
                  font-size:1.1rem; margin-bottom:1.5rem">
        <?= e($orderId) ?>
      </div>
      <p style="font-size:.88rem; color:var(--ink-soft); margin-bottom:2.5rem; line-height:1.7">
        We've received your order and will process it shortly.
        You'll be contacted for delivery updates.
      </p>
      <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap">
        <a href="<?= STORE_URL ?>/shop.php" class="btn btn-primary">Continue Shopping</a>
        <?php if (isLoggedIn()): ?>
          <a href="<?= STORE_URL ?>/account.php" class="btn btn-outline">My Orders</a>
        <?php endif; ?>
      </div>
    </div>

  <?php else: ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error" style="margin-bottom:1.5rem">
        <i class="fas fa-circle-xmark"></i>
        <div>
          <?php if (count($errors) === 1): echo htmlspecialchars($errors[0]);
          else: ?><ul style="margin:.25rem 0 0; padding-left:1.25rem">
            <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
          </ul><?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 360px; gap:2.5rem; align-items:start">

      <!-- ── LEFT: Form ── -->
      <form method="POST" id="checkoutForm">

        <!-- Guest / Create account toggle -->
        <?php if (!isLoggedIn()): ?>
          <div style="background:var(--ivory-warm); border:1px solid var(--mist); border-radius:var(--r-md);
                      padding:1.25rem 1.5rem; margin-bottom:2rem">
            <p style="font-size:.85rem; color:var(--ink-soft); margin-bottom:.75rem">
              Already have an account?
              <a href="<?= STORE_URL ?>/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                 style="color:var(--rose-deep); font-weight:500">Login for faster checkout</a>
            </p>
            <div style="display:flex; gap:.75rem; flex-wrap:wrap">
              <button type="button" onclick="setMode('guest')" id="modeGuest"
                      class="btn btn-outline btn-sm" style="flex:1 1 140px">
                <i class="fas fa-user-slash"></i> Guest Checkout
              </button>
              <button type="button" onclick="setMode('account')" id="modeAccount"
                      class="btn btn-outline btn-sm" style="flex:1 1 140px">
                <i class="fas fa-user-plus"></i> Create Account
              </button>
            </div>
          </div>
        <?php endif; ?>

        <!-- ── STEP 1: Contact Info ── -->
        <div class="checkout-card">
          <h3 class="checkout-card-title">
            <span class="checkout-step-num">1</span>
            Contact Information
          </h3>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem">
            <div class="form-group">
              <label class="form-label">First Name *</label>
              <input type="text" name="first_name" class="form-control" required
                     value="<?= e($_POST['first_name'] ?? $user['name'] ?? '') ?>"
                     placeholder="Maria">
            </div>
            <div class="form-group">
              <label class="form-label">Last Name *</label>
              <input type="text" name="last_name" class="form-control" required
                     value="<?= e($_POST['last_name'] ?? '') ?>" placeholder="Santos">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address *</label>
            <input type="email" name="email" class="form-control" required
                   value="<?= e($_POST['email'] ?? $user['email'] ?? '') ?>"
                   placeholder="maria@email.com">
            <p class="form-hint">Order confirmation will be sent here</p>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Mobile Number *</label>
            <input type="tel" name="phone" class="form-control" required
                   value="<?= e($_POST['phone'] ?? '') ?>" placeholder="09XXXXXXXXX">
          </div>
        </div>

        <!-- ── STEP 2: Shipping Address ── -->
        <div class="checkout-card">
          <h3 class="checkout-card-title">
            <span class="checkout-step-num">2</span>
            Shipping Address
          </h3>

          <!-- Hidden fields submitted with form -->
          <input type="hidden" name="region"       id="regionName"   value="<?= e($_POST['region']   ?? '') ?>">
          <input type="hidden" name="province"     id="provinceName" value="<?= e($_POST['province'] ?? '') ?>">
          <input type="hidden" name="city"         id="cityName"     value="<?= e($_POST['city']     ?? '') ?>">
          <input type="hidden" name="barangay"     id="barangayName" value="<?= e($_POST['barangay'] ?? '') ?>">
          <input type="hidden" name="region_code"  id="regionCode"   value="">

          <!-- Region -->
          <div class="form-group">
            <label class="form-label">Region *</label>
            <div class="psgc-select-wrap">
              <select id="selRegion" class="form-control psgc-select" onchange="onRegionChange(this)">
                <option value="">— Select Region —</option>
              </select>
              <span class="psgc-spinner" id="spinRegion" style="display:none">
                <i class="fas fa-spinner fa-spin"></i>
              </span>
            </div>
          </div>

          <!-- Province (hidden for NCR) -->
          <div class="form-group" id="rowProvince" style="display:none">
            <label class="form-label">Province *</label>
            <div class="psgc-select-wrap">
              <select id="selProvince" class="form-control psgc-select" onchange="onProvinceChange(this)" disabled>
                <option value="">— Select Province —</option>
              </select>
              <span class="psgc-spinner" id="spinProvince" style="display:none">
                <i class="fas fa-spinner fa-spin"></i>
              </span>
            </div>
          </div>

          <!-- City / Municipality -->
          <div class="form-group" id="rowCity" style="display:none">
            <label class="form-label">City / Municipality *</label>
            <div class="psgc-select-wrap">
              <select id="selCity" class="form-control psgc-select" onchange="onCityChange(this)" disabled>
                <option value="">— Select City / Municipality —</option>
              </select>
              <span class="psgc-spinner" id="spinCity" style="display:none">
                <i class="fas fa-spinner fa-spin"></i>
              </span>
            </div>
          </div>

          <!-- Barangay -->
          <div class="form-group" id="rowBarangay" style="display:none">
            <label class="form-label">Barangay *</label>
            <div class="psgc-select-wrap">
              <select id="selBarangay" class="form-control psgc-select" onchange="onBarangayChange(this)" disabled>
                <option value="">— Select Barangay —</option>
              </select>
              <span class="psgc-spinner" id="spinBarangay" style="display:none">
                <i class="fas fa-spinner fa-spin"></i>
              </span>
            </div>
          </div>

          <!-- Street address -->
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Full Street Address *</label>
            <input type="text" name="address" class="form-control" required
                   value="<?= e($_POST['address'] ?? '') ?>"
                   placeholder="House/Lot/Unit No., Street Name, Subdivision">
            <p class="form-hint">Include landmarks for faster delivery</p>
          </div>
        </div>

        <!-- ── STEP 3: Payment ── -->
        <div class="checkout-card">
          <h3 class="checkout-card-title">
            <span class="checkout-step-num">3</span>
            Payment Method
          </h3>
          <?php $methods = [
            'Cash on delivery'   => ['fa-money-bill-wave', 'Cash on Delivery (COD)', 'Pay when your order arrives'],
            // 'Payments via GCash' => ['fa-mobile-screen',   'GCash',                  'We will send payment details via SMS'],
            // 'Payments via Maya'  => ['fa-credit-card',     'Maya / PayMaya',          'We will send payment instructions'],
          ];
          foreach ($methods as $val => [$icon, $label, $sub]): ?>
            <label class="payment-option" id="lbl_<?= md5($val) ?>">
              <input type="radio" name="payment_method" value="<?= e($val) ?>"
                     <?= ($_POST['payment_method'] ?? 'Cash on delivery') === $val ? 'checked' : '' ?>
                     onchange="highlightPayment()">
              <div class="payment-icon"><i class="fas <?= $icon ?>"></i></div>
              <div>
                <div style="font-weight:500; font-size:.88rem; color:var(--ink)"><?= $label ?></div>
                <div style="font-size:.73rem; color:var(--ink-soft)"><?= $sub ?></div>
              </div>
            </label>
          <?php endforeach; ?>
        </div>

        <!-- ── Notes ── -->
        <div class="checkout-card">
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Order Notes (optional)</label>
            <textarea name="notes" class="form-control" rows="3"
                      placeholder="Special delivery instructions, landmarks, etc."
                      style="resize:vertical"><?= e($_POST['notes'] ?? '') ?></textarea>
          </div>
        </div>

        <!-- Create account (guest only) -->
        <?php if (!isLoggedIn()): ?>
          <div id="createAccountSection" style="display:none; background:var(--gold-pale);
               border:1px solid var(--gold-light); border-radius:var(--r-md);
               padding:1.5rem; margin-bottom:1.5rem">
            <label style="display:flex; align-items:center; gap:.6rem; margin-bottom:1rem; cursor:pointer">
              <input type="checkbox" name="create_account" value="1" id="createAccountCheck"
                     style="accent-color:var(--rose)" onchange="togglePwField(this)">
              <span style="font-size:.88rem; font-weight:500">Save my info & create an account</span>
            </label>
            <div id="passwordField" style="display:none">
              <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Create Password</label>
                <input type="password" name="password" class="form-control" placeholder="Min. 6 characters">
              </div>
            </div>
          </div>
        <?php endif; ?>

        <button type="submit" name="place_order" value="1"
                class="btn btn-primary btn-lg"
                style="width:100%; justify-content:center; font-size:.85rem">
          <i class="fas fa-lock"></i> Place Order — <?= formatPrice($total) ?>
        </button>
        <p style="text-align:center; font-size:.73rem; color:var(--ink-soft); margin-top:.85rem">
          <i class="fas fa-shield-halved" style="color:var(--rose)"></i>
          Your information is encrypted and secure.
        </p>
      </form>

      <!-- ── RIGHT: Order Summary ── -->
      <div class="order-summary">
        <h3 style="font-size:1.2rem; margin-bottom:1.25rem; padding-bottom:.75rem; border-bottom:1px solid var(--mist)">
          Your Order (<?= count($cartItems) ?> items)
        </h3>
        <?php foreach ($cartItems as $item):
          $p   = $item['product'];
          $img = !empty($p['image']) ? productImageUrl($p['image']) : '';
        ?>
          <div style="display:flex; gap:.85rem; padding:.85rem 0; border-bottom:1px solid var(--mist-light)">
            <div style="position:relative; flex-shrink:0">
              <div style="width:54px;height:54px;border-radius:var(--r-sm);overflow:hidden;background:var(--ivory-deep)">
                <?php if ($img): ?>
                  <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                               background:linear-gradient(135deg,var(--blush),var(--gold-pale));color:var(--rose-mid)">
                    <i class="fas fa-spa" style="font-size:.9rem"></i>
                  </div>
                <?php endif; ?>
              </div>
              <span style="position:absolute;top:-6px;right:-6px;width:20px;height:20px;
                           background:var(--mocha);color:#fff;border-radius:50%;
                           font-size:.65rem;display:flex;align-items:center;justify-content:center;font-weight:600">
                <?= (int)$item['qty'] ?>
              </span>
            </div>
            <div style="flex:1; min-width:0">
              <div style="font-size:.83rem; font-weight:500; color:var(--ink);
                          white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
                <?= e($p['name']) ?>
              </div>
              <div style="font-size:.75rem; color:var(--ink-soft)">
                <?= formatPrice((float)$p['price']) ?> × <?= (int)$item['qty'] ?>
              </div>
            </div>
            <div style="font-weight:500; font-size:.88rem; color:var(--mocha); flex-shrink:0">
              <?= formatPrice($item['subtotal']) ?>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="summary-row" style="margin-top:.75rem">
          <span>Subtotal</span><span><?= formatPrice($subtotal) ?></span>
        </div>
        <div class="summary-row">
          <span>Shipping</span>
          <span><?= $shipping === 0
            ? '<span style="color:var(--rose);font-weight:500">FREE</span>'
            : formatPrice($shipping) ?></span>
        </div>
        <div class="summary-row total"
             style="font-size:1.1rem; margin-top:.5rem; padding-top:.75rem; border-top:1px solid var(--mist)">
          <span>Total</span>
          <strong style="color:var(--mocha); font-size:1.25rem"><?= formatPrice($total) ?></strong>
        </div>

        <div style="margin-top:1.5rem; padding:1rem; background:var(--ivory-deep);
                    border-radius:var(--r-sm); display:flex; flex-direction:column; gap:.55rem">
          <?php foreach ([
            ['fa-lock',       'SSL Secured Checkout'],
            ['fa-truck-fast', $shipping === 0 ? 'Free Shipping Applied' : 'Shipping: ' . formatPrice($shipping)],
            ['fa-headset',    'Support: Mon–Sat 9am–6pm'],
          ] as [$ico, $txt]): ?>
            <div style="display:flex;align-items:center;gap:.6rem;font-size:.72rem;color:var(--ink-soft)">
              <i class="fas <?= $ico ?>" style="color:var(--rose);width:14px;text-align:center"></i> <?= $txt ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- ── PSGC API SCRIPT ── -->
<script>
const PSGC = 'https://psgc.gitlab.io/api';
const NCR_CODE = '130000000';

/* ── Helpers ── */
function spin(id, show) {
  const el = document.getElementById('spin' + id);
  if (el) el.style.display = show ? 'inline' : 'none';
}
function resetSelect(sel, placeholder) {
  sel.innerHTML = `<option value="">${placeholder}</option>`;
  sel.disabled = true;
}
function sortByName(arr) {
  return arr.sort((a, b) => a.name.localeCompare(b.name));
}
async function fetchPSGC(path) {
  const res = await fetch(PSGC + path);
  if (!res.ok) throw new Error('PSGC fetch failed: ' + path);
  return res.json();
}
function populateSelect(sel, items, valueKey = 'code', labelKey = 'name') {
  items.forEach(item => {
    const opt = document.createElement('option');
    opt.value       = item[valueKey];
    opt.textContent = item[labelKey];
    sel.appendChild(opt);
  });
  sel.disabled = false;
}

/* ── Load regions on init ── */
async function loadRegions() {
  const sel = document.getElementById('selRegion');
  spin('Region', true);
  try {
    const regions = await fetchPSGC('/regions/');
    populateSelect(sel, sortByName(regions));
  } catch(e) {
    console.error('Could not load regions', e);
  }
  spin('Region', false);
}

/* ── Region changed ── */
async function onRegionChange(sel) {
  const code = sel.value;
  const name = sel.options[sel.selectedIndex]?.text || '';

  // Set hidden field
  document.getElementById('regionName').value = name;
  document.getElementById('regionCode').value = code;

  // Reset downstream
  const selProv = document.getElementById('selProvince');
  const selCity = document.getElementById('selCity');
  const selBrgy = document.getElementById('selBarangay');
  resetSelect(selProv, '— Select Province —');
  resetSelect(selCity, '— Select City / Municipality —');
  resetSelect(selBrgy, '— Select Barangay —');
  document.getElementById('provinceName').value = '';
  document.getElementById('cityName').value     = '';
  document.getElementById('barangayName').value = '';

  document.getElementById('rowBarangay').style.display = 'none';

  if (!code) {
    document.getElementById('rowProvince').style.display = 'none';
    document.getElementById('rowCity').style.display = 'none';
    return;
  }

  // NCR has no provinces — load cities directly
  if (code === NCR_CODE) {
    document.getElementById('rowProvince').style.display = 'none';
    document.getElementById('provinceName').value = 'Metro Manila';
    await loadCitiesForRegion(code);
  } else {
    document.getElementById('rowProvince').style.display = 'block';
    document.getElementById('rowCity').style.display = 'none';
    await loadProvinces(code);
  }
}

/* ── Load provinces for a region ── */
async function loadProvinces(regionCode) {
  const sel = document.getElementById('selProvince');
  spin('Province', true);
  try {
    const provinces = await fetchPSGC('/regions/' + regionCode + '/provinces/');
    populateSelect(sel, sortByName(provinces));
  } catch(e) {
    console.error('Could not load provinces', e);
  }
  spin('Province', false);
}

/* ── Province changed ── */
async function onProvinceChange(sel) {
  const code = sel.value;
  const name = sel.options[sel.selectedIndex]?.text || '';
  document.getElementById('provinceName').value = name;

  const selCity = document.getElementById('selCity');
  const selBrgy = document.getElementById('selBarangay');
  resetSelect(selCity, '— Select City / Municipality —');
  resetSelect(selBrgy, '— Select Barangay —');
  document.getElementById('cityName').value     = '';
  document.getElementById('barangayName').value = '';
  document.getElementById('rowBarangay').style.display = 'none';

  if (!code) { document.getElementById('rowCity').style.display = 'none'; return; }

  await loadCitiesForProvince(code);
}

/* ── Load cities for NCR (region endpoint) ── */
async function loadCitiesForRegion(regionCode) {
  const sel = document.getElementById('selCity');
  document.getElementById('rowCity').style.display = 'block';
  spin('City', true);
  try {
    const cities = await fetchPSGC('/regions/' + regionCode + '/cities-municipalities/');
    populateSelect(sel, sortByName(cities));
  } catch(e) {
    console.error('Could not load cities', e);
  }
  spin('City', false);
}

/* ── Load cities for a province ── */
async function loadCitiesForProvince(provinceCode) {
  const sel = document.getElementById('selCity');
  document.getElementById('rowCity').style.display = 'block';
  spin('City', true);
  try {
    const cities = await fetchPSGC('/provinces/' + provinceCode + '/cities-municipalities/');
    populateSelect(sel, sortByName(cities));
  } catch(e) {
    console.error('Could not load cities', e);
  }
  spin('City', false);
}

/* ── City changed ── */
async function onCityChange(sel) {
  const code = sel.value;
  const name = sel.options[sel.selectedIndex]?.text || '';
  document.getElementById('cityName').value = name;

  const selBrgy = document.getElementById('selBarangay');
  resetSelect(selBrgy, '— Select Barangay —');
  document.getElementById('barangayName').value = '';
  document.getElementById('rowBarangay').style.display = 'none';

  if (!code) return;

  document.getElementById('rowBarangay').style.display = 'block';
  spin('Barangay', true);
  try {
    const barangays = await fetchPSGC('/cities-municipalities/' + code + '/barangays/');
    populateSelect(selBrgy, sortByName(barangays));
  } catch(e) {
    console.error('Could not load barangays', e);
  }
  spin('Barangay', false);
}

/* ── Barangay changed ── */
function onBarangayChange(sel) {
  document.getElementById('barangayName').value = sel.options[sel.selectedIndex]?.text || '';
}

/* ── Init ── */
loadRegions();

/* ── Payment highlight ── */
function highlightPayment() {
  document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    const lbl = radio.closest('.payment-option');
    lbl.style.borderColor = radio.checked ? 'var(--rose)' : 'var(--mist)';
    lbl.style.background  = radio.checked ? 'var(--gold-pale)' : '';
  });
}
highlightPayment();

/* ── Guest mode ── */
function setMode(mode) {
  const sec = document.getElementById('createAccountSection');
  if (mode === 'account') {
    if (sec) sec.style.display = 'block';
    document.getElementById('modeAccount')?.classList.replace('btn-outline','btn-primary');
    document.getElementById('modeGuest')?.classList.replace('btn-primary','btn-outline');
  } else {
    if (sec) sec.style.display = 'none';
    document.getElementById('modeGuest')?.classList.replace('btn-outline','btn-primary');
    document.getElementById('modeAccount')?.classList.replace('btn-primary','btn-outline');
  }
}
function togglePwField(cb) {
  document.getElementById('passwordField').style.display = cb.checked ? 'block' : 'none';
}

/* ── Form validation: ensure selects have values ── */
document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
  const region   = document.getElementById('regionName').value;
  const city     = document.getElementById('cityName').value;
  const barangay = document.getElementById('barangayName').value;
  if (!region)   { e.preventDefault(); alert('Please select a region.'); return; }
  if (!city)     { e.preventDefault(); alert('Please select a city / municipality.'); return; }
  if (!barangay) { e.preventDefault(); alert('Please select a barangay.'); return; }
});
</script>

<style>
/* Checkout card */
.checkout-card {
  background: var(--ivory-warm);
  border: 1px solid var(--mist);
  border-radius: var(--r-md);
  padding: 1.75rem;
  margin-bottom: 1.5rem;
}
.checkout-card-title {
  font-size: 1.1rem;
  margin-bottom: 1.25rem;
  display: flex;
  align-items: center;
  gap: .6rem;
}
.checkout-step-num {
  width: 28px; height: 28px;
  background: var(--mocha);
  color: var(--ivory);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: .75rem;
  flex-shrink: 0;
}

/* PSGC select wrap */
.psgc-select-wrap {
  position: relative;
}
.psgc-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23999' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  padding-right: 2.5rem !important;
}
.psgc-select:disabled {
  opacity: .5;
  cursor: not-allowed;
}
.psgc-spinner {
  position: absolute;
  right: 2.5rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--rose);
  font-size: .85rem;
  pointer-events: none;
}

/* Payment options */
.payment-option {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.1rem;
  border: 1.5px solid var(--mist);
  border-radius: var(--r-sm);
  margin-bottom: .65rem;
  cursor: pointer;
  transition: border-color .2s, background .2s;
}
.payment-option:hover { border-color: var(--rose); }
.payment-option input[type="radio"] {
  accent-color: var(--rose);
  width: 18px; height: 18px;
  flex-shrink: 0;
}
.payment-icon {
  width: 36px; height: 36px;
  background: var(--blush);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: var(--rose-deep);
  flex-shrink: 0;
}

/* Responsive */
@media(max-width:900px){
  .container > div[style*="grid-template-columns:1fr 360px"]{
    grid-template-columns:1fr!important;
  }
}
@media(max-width:600px){
  div[style*="grid-template-columns:1fr 1fr"]{
    grid-template-columns:1fr!important;
  }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>