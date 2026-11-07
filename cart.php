<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

// Handle POST actions (qty update / remove)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pid    = (int)($_POST['product_id'] ?? 0);
    $qty    = (int)($_POST['qty'] ?? 1);
    if ($action === 'update' && $pid) updateCartItem($pid, $qty);
    if ($action === 'remove' && $pid) removeFromCart($pid);
    if ($action === 'clear') clearCart();
    header('Location: ' . STORE_URL . '/cart.php');
    exit;
}

$pageTitle = 'My Cart — ' . SITE_NAME;

$cartItems   = getCartWithProducts($pdo);
$subtotal    = array_sum(array_column($cartItems, 'subtotal'));
$shipping    = $subtotal >= 1500 ? 0 : 150;
$total       = $subtotal + $shipping;
$cartCount   = cartCount();

include __DIR__ . '/includes/header.php';
?>

<div class="page-hero" style="padding:calc(var(--header-h) + 2rem) var(--gutter) 2rem">
  <div class="container">
    <h1 style="font-size:clamp(1.8rem,3.5vw,3rem); position:relative; z-index:1">
      My Cart <?php if ($cartCount > 0): ?>
        <span style="font-size:1rem; font-family:var(--font-body); font-weight:400;
                     color:var(--ink-soft); margin-left:.5rem">(<?= $cartCount ?> items)</span>
      <?php endif; ?>
    </h1>
  </div>
</div>

<div class="container section-pad-sm">

  <?php if (empty($cartItems)): ?>
    <!-- EMPTY CART -->
    <div style="text-align:center; padding:5rem 1rem">
      <div style="width:100px; height:100px; background:var(--ivory-deep); border-radius:50%;
                  display:flex; align-items:center; justify-content:center; margin:0 auto 2rem; font-size:2.5rem; color:var(--blush)">
        <i class="fas fa-bag-shopping"></i>
      </div>
      <h2 style="font-size:2rem; margin-bottom:.75rem">Your cart is empty</h2>
      <p style="color:var(--ink-soft); margin-bottom:2rem; font-size:.95rem">
        Looks like you haven't added anything yet. Discover our curated collection.
      </p>
      <a href="<?= STORE_URL ?>/shop.php" class="btn btn-primary btn-lg">
        <i class="fas fa-arrow-left"></i> Continue Shopping
      </a>
    </div>

  <?php else: ?>
    <!-- CART WITH ITEMS -->
    <div style="display:grid; grid-template-columns:1fr 360px; gap:2.5rem; align-items:start">

      <!-- LEFT: Cart Items -->
      <div>
                <!-- Header row -->
        <div class="cart-header-row" style="display:grid; grid-template-columns:1fr 100px 100px 80px 40px;
                    gap:1rem; padding:.75rem 1rem; font-size:.68rem; font-weight:600;
                    letter-spacing:.12em; text-transform:uppercase; color:var(--rose);
                    border-bottom:1px solid var(--mist); margin-bottom:.5rem">

          <span>Product</span>
          <span style="text-align:center">Quantity</span>
          <span style="text-align:right">Price</span>
          <span style="text-align:right">Subtotal</span>
          <span></span>
        </div>

        <!-- Cart rows -->
        <?php foreach ($cartItems as $item):
          $p      = $item['product'];
          $imgUrl = !empty($p['image']) ? productImageUrl($p['image']) : '';
        ?>
          <div class="cart-row" style="display:grid; grid-template-columns:1fr 100px 100px 80px 40px;
                                        gap:1rem; align-items:center; padding:1.25rem 1rem;
                                        border-bottom:1px solid var(--mist-light); transition:background .2s"
               onmouseenter="this.style.background='var(--ivory-deep)'"
               onmouseleave="this.style.background='transparent'">

            <!-- Product info -->
            <div style="display:flex; align-items:center; gap:1rem">
              <a href="<?= STORE_URL ?>/product.php?id=<?= (int)$p['id'] ?>"
                 style="flex-shrink:0; width:72px; height:72px; border-radius:var(--r-sm);
                        overflow:hidden; background:var(--ivory-deep); display:block">
                <?php if ($imgUrl): ?>
                  <img src="<?= e($imgUrl) ?>" alt="<?= e($p['name']) ?>"
                       style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                               background:linear-gradient(135deg,var(--blush),var(--gold-pale));color:var(--rose-mid)">
                    <i class="fas fa-spa"></i>
                  </div>
                <?php endif; ?>
              </a>
              <div>
                <a href="<?= STORE_URL ?>/product.php?id=<?= (int)$p['id'] ?>"
                   style="font-family:var(--font-display); font-size:1rem; color:var(--ink);
                          text-decoration:none; line-height:1.3; display:block; margin-bottom:.25rem">
                  <?= e($p['name']) ?>
                </a>
                <?php if (!empty($p['category_name'])): ?>
                  <span style="font-size:.68rem; color:var(--rose); letter-spacing:.1em; text-transform:uppercase">
                    <?= e($p['category_name']) ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Qty -->
            <div style="display:flex; align-items:center; justify-content:center; gap:.3rem">
              <form method="POST" style="display:contents">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                <button type="button" onclick="changeItemQty(this, -1)"
                        style="width:28px;height:28px;border:1px solid var(--mist);background:var(--ivory-warm);
                               border-radius:var(--r-xs);font-size:.9rem;cursor:pointer;color:var(--ink-soft);
                               display:flex;align-items:center;justify-content:center;flex-shrink:0">−</button>
                <input type="number" name="qty" value="<?= (int)$item['qty'] ?>"
                       min="1" max="99" onchange="submitQtyForm(this)"
                       style="width:40px;text-align:center;border:1px solid var(--mist);
                              border-radius:var(--r-xs);padding:.25rem;font-size:.85rem;
                              background:var(--ivory-warm)">
                <button type="button" onclick="changeItemQty(this, 1)"
                        style="width:28px;height:28px;border:1px solid var(--mist);background:var(--ivory-warm);
                               border-radius:var(--r-xs);font-size:.9rem;cursor:pointer;color:var(--ink-soft);
                               display:flex;align-items:center;justify-content:center;flex-shrink:0">+</button>
              </form>
            </div>

            <!-- Unit price -->
            <div style="text-align:right; font-size:.88rem; color:var(--ink-soft)">
              <?= formatPrice((float)$p['price']) ?>
            </div>

            <!-- Subtotal -->
            <div style="text-align:right; font-weight:500; color:var(--mocha)">
              <?= formatPrice($item['subtotal']) ?>
            </div>

            <!-- Remove -->
            <form method="POST" style="display:flex; justify-content:center">
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
              <button type="submit"
                      onclick="return confirm('Remove this item?')"
                      style="width:32px;height:32px;border:none;background:none;
                             color:#ccc;font-size:.85rem;cursor:pointer;border-radius:50%;
                             transition:.2s;display:flex;align-items:center;justify-content:center"
                      onmouseenter="this.style.color='var(--rose)';this.style.background='var(--blush)'"
                      onmouseleave="this.style.color='#ccc';this.style.background='none'">
                <i class="fas fa-xmark"></i>
              </button>
            </form>
          </div>
        <?php endforeach; ?>

        <!-- Cart Actions -->
        <div style="display:flex; justify-content:space-between; align-items:center; padding-top:1.5rem; flex-wrap:wrap; gap:1rem">
          <a href="<?= STORE_URL ?>/shop.php" class="btn btn-outline btn-sm">
            <i class="fas fa-arrow-left"></i> Continue Shopping
          </a>
          <form method="POST" onsubmit="return confirm('Clear entire cart?')">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-outline-rose btn-sm">
              <i class="fas fa-trash"></i> Clear Cart
            </button>
          </form>
        </div>
      </div>

      <!-- RIGHT: Order Summary -->
      <div class="order-summary">
        <h3 style="font-size:1.3rem; margin-bottom:1.25rem; padding-bottom:.75rem; border-bottom:1px solid var(--mist)">
          Order Summary
        </h3>

        <div class="summary-row">
          <span>Subtotal (<?= $cartCount ?> items)</span>
          <span><?= formatPrice($subtotal) ?></span>
        </div>
        <div class="summary-row">
          <span>Shipping</span>
          <span><?php if ($shipping === 0): ?>
            <span style="color:var(--rose); font-weight:500">FREE</span>
          <?php else: ?>
            <?= formatPrice($shipping) ?>
          <?php endif; ?></span>
        </div>

        <?php if ($shipping > 0): ?>
          <div style="background:var(--gold-pale); border-radius:var(--r-sm); padding:.65rem .85rem;
                      font-size:.76rem; color:var(--mocha); margin:.75rem 0">
            <i class="fas fa-truck-fast" style="color:var(--rose);margin-right:.4rem"></i>
            Add <strong><?= formatPrice(1500 - $subtotal) ?></strong> more for free shipping!
          </div>
        <?php endif; ?>

        <div class="summary-row total" style="font-size:1.1rem; margin-top:.75rem; padding-top:.85rem; border-top:1px solid var(--mist)">
          <span>Total</span>
          <strong style="color:var(--mocha); font-size:1.3rem"><?= formatPrice($total) ?></strong>
        </div>

        <!-- Coupon (UI only) -->
        <div style="margin-top:1.25rem; padding-top:1.25rem; border-top:1px solid var(--mist-light)">
          <div style="display:flex; gap:.5rem">
            <input type="text" placeholder="Coupon code"
                   style="flex:1; padding:.6rem .9rem; border:1.5px solid var(--mist);
                          border-radius:var(--r-xs); font-size:.82rem; background:var(--ivory-warm); outline:none">
            <button class="btn btn-outline btn-sm">Apply</button>
          </div>
        </div>

        <a href="<?= STORE_URL ?>/checkout.php" class="btn btn-primary"
           style="width:100%; justify-content:center; margin-top:1.5rem; padding:1rem">
          Proceed to Checkout <i class="fas fa-arrow-right"></i>
        </a>

        <!-- Trust badges -->
        <div style="margin-top:1.25rem; display:flex; flex-direction:column; gap:.6rem">
          <?php foreach ([
            ['fa-lock',         'Secure 256-bit SSL checkout'],
            ['fa-shield-halved','Buyer protection guarantee'],
            ['fa-rotate-left',  '14-day hassle-free returns'],
          ] as [$icon, $text]): ?>
            <div style="display:flex;align-items:center;gap:.6rem;font-size:.72rem;color:var(--ink-soft)">
              <i class="fas <?= $icon ?>" style="color:var(--rose);width:14px;text-align:center"></i>
              <?= $text ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  <?php endif; ?>

  <!-- You May Also Like -->
  <?php
  try {
    $hasStatus = in_array('status', getColumns($pdo,'products'));
    $sugg = $pdo->query(
      "SELECT p.id, p.name, p.price" .
      (in_array('image', getColumns($pdo,'products')) ? ', p.image' : '') .
      " FROM products p" .
      ($hasStatus ? " WHERE p.status='active'" : '') .
      " ORDER BY RAND() LIMIT 4"
    )->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) { $sugg = []; }
  ?>
  <?php if (!empty($sugg)): ?>
    <div style="margin-top:4rem; padding-top:3rem; border-top:1px solid var(--mist)">
      <div class="section-header" style="text-align:left; margin-bottom:2rem">
        <span class="section-eyebrow">Keep Shopping</span>
        <h2 style="font-size:1.8rem">You May Also Like</h2>
      </div>
      <div class="products-grid">
        <?php foreach ($sugg as $i => $p):
          $img = !empty($p['image']) ? productImageUrl($p['image']) : '';
        ?>
          <article class="product-card reveal reveal-delay-<?= $i+1 ?>">
            <div class="pc-img-wrap">
              <?php if ($img): ?>
                <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" class="pc-img" loading="lazy">
              <?php else: ?>
                <div class="pc-placeholder"><i class="fas fa-spa"></i></div>
              <?php endif; ?>
              <div class="pc-actions">
                <button class="pc-add-btn" data-add-cart="<?= (int)$p['id'] ?>" data-qty="1">
                  <i class="fas fa-bag-shopping"></i> Quick Add
                </button>
              </div>
            </div>
            <div class="pc-body">
              <a href="<?= STORE_URL ?>/product.php?id=<?= (int)$p['id'] ?>">
                <h3 class="pc-name"><?= e($p['name']) ?></h3>
              </a>
              <div class="pc-footer">
                <span class="pc-price-current"><?= formatPrice((float)$p['price']) ?></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
function changeItemQty(btn, delta) {
  const input = btn.closest('.cart-row').querySelector('input[name="qty"]');
  const newVal = Math.max(1, (parseInt(input.value)||1) + delta);
  input.value = newVal;
  submitQtyForm(input);
}

function submitQtyForm(input) {
  const form = input.closest('form') || input.closest('.cart-row').querySelector('form');
  if (form) form.submit();
}
</script>

<style>
@media(max-width:900px){
  .container > div[style*="grid-template-columns:1fr 360px"]{
    grid-template-columns:1fr!important;
  }
  .container > div[style*="grid-template-columns:1fr 360px"] > * {
    min-width: 0;
  }
}

@media(max-width:700px){
  .cart-header-row{ display:none; }

  .cart-row{
    display:grid!important;
    grid-template-columns: 1fr auto!important;
    grid-template-areas:
      "info   remove"
      "qty    qty"
      "price  subtotal";
    row-gap:.85rem;
    column-gap:.75rem;
    padding:1.5rem 0!important;
    align-items:start!important;
  }

  /* Product image + name */
  .cart-row > div:nth-child(1){ grid-area: info; min-width:0; }

  /* Quantity stepper */
  .cart-row > div:nth-child(2){
    grid-area: qty;
    justify-content:flex-start!important;
  }

  /* Unit price */
  .cart-row > div:nth-child(3){
    grid-area: price;
    text-align:left!important;
    font-size:.8rem;
  }
  .cart-row > div:nth-child(3)::before{
    content:"Unit price: ";
    color:var(--ink-soft);
    font-weight:400;
  }

  /* Line subtotal */
  .cart-row > div:nth-child(4){
    grid-area: subtotal;
    text-align:right!important;
    font-size:1.05rem;
  }
  .cart-row > div:nth-child(4)::before{
    content:"Subtotal: ";
    font-size:.72rem;
    font-weight:400;
    color:var(--ink-soft);
    display:block;
  }

  /* Remove button, top right corner */
  .cart-row > form{
    grid-area: remove;
    justify-content:flex-end!important;
  }
}

</style>


<?php include __DIR__ . '/includes/footer.php'; ?>