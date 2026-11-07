<?php // store/includes/footer.php ?>
</main><!-- /main -->

<!-- ══════════ FOOTER ══════════ -->
<footer class="site-footer">

  <!-- Newsletter strip -->
  <div class="footer-newsletter">
    <div class="newsletter-inner">
      <div class="newsletter-copy">
        <span class="section-eyebrow" style="color:var(--gold)">Stay Radiant</span>
        <h3 class="newsletter-title">Join the inner circle</h3>
        <p>Get first access to new arrivals, exclusive offers & skincare rituals.</p>
      </div>
      <form class="newsletter-form" onsubmit="handleNewsletter(event)">
        <div class="nl-field">
          <input type="email" placeholder="Your email address" aria-label="Email for newsletter">
          <button type="submit">Subscribe <i class="fas fa-arrow-right"></i></button>
        </div>
        <p class="nl-note">No spam. Unsubscribe anytime. ✦</p>
      </form>
    </div>
  </div>

  <!-- Main footer -->
  <div class="footer-main">
    <div class="footer-brand-col">
      <a href="<?= STORE_URL ?>/index.php" class="site-logo footer-logo">
          <img src="<?= STORE_URL ?>/assets/images/logo-white.png">
        <!--<span class="logo-mark">A</span>-->
        <!--<div class="logo-text-wrap">-->
        <!--  <span class="logo-name">Amaria</span>-->
        <!--  <span class="logo-sub">Aesthetics</span>-->
        <!--</div>-->
      </a>
      <p class="footer-about">
        Beauty rooted in science,<br>defined by elegance. Philippine-made,<br>world-class quality.
      </p>
      <div class="footer-socials">
        <a href="#" aria-label="Instagram" class="social-link"><i class="fab fa-instagram"></i></a>
        <a href="#" aria-label="Facebook"  class="social-link"><i class="fab fa-facebook-f"></i></a>
        <a href="#" aria-label="TikTok"    class="social-link"><i class="fab fa-tiktok"></i></a>
        <a href="#" aria-label="YouTube"   class="social-link"><i class="fab fa-youtube"></i></a>
      </div>
    </div>

    <div class="footer-links-col">
      <h4 class="footer-col-title">Shop</h4>
      <ul>
        <li><a href="<?= STORE_URL ?>/shop">All Products</a></li>
        <li><a href="<?= STORE_URL ?>/shop?sort=newest">New Arrivals</a></li>
        <li><a href="<?= STORE_URL ?>/shop?sale=1">On Sale</a></li>
        <li><a href="<?= STORE_URL ?>/shop?featured=1">Bestsellers</a></li>
      </ul>
    </div>

    <div class="footer-links-col">
      <h4 class="footer-col-title">Help</h4>
      <ul>
        <li><a href="<?= STORE_URL ?>/about">About Us</a></li>
        <li><a href="<?= STORE_URL ?>/contact">Contact</a></li>
        <li><a href="<?= STORE_URL ?>/shipping-policy">Shipping Policy</a></li>
        <li><a href="<?= STORE_URL ?>/returns">Returns & Refunds</a></li>
      </ul>
    </div>

    <div class="footer-links-col">
      <h4 class="footer-col-title">Account</h4>
      <ul>
        <?php if (isLoggedIn()): ?>
          <li><a href="<?= STORE_URL ?>/profile">My Profile</a></li>
          <li><a href="<?= STORE_URL ?>/profile?tab=orders">My Orders</a></li>
          <li><a href="<?= STORE_URL ?>/cart">My Cart</a></li>
          <li><a href="<?= STORE_URL ?>/logout">Logout</a></li>
        <?php else: ?>
          <li><a href="<?= STORE_URL ?>/login">Login</a></li>
          <li><a href="<?= STORE_URL ?>/signupp">Create Account</a></li>
          <li><a href="<?= STORE_URL ?>/cart">My Cart</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <!-- Footer bottom bar -->
  <div class="footer-bar">
    <p>© <?= date('Y') ?> Amari Aesthetics. All rights reserved. Crafted with care in the Philippines 🇵🇭</p>
    <div class="footer-badges">
      <span class="footer-badge"><i class="fas fa-lock"></i> Secure Checkout</span>
      <span class="footer-badge"><i class="fas fa-rotate-left"></i> Easy Returns</span>
      <span class="footer-badge"><i class="fas fa-truck"></i> Fast Delivery</span>
    </div>
  </div>

</footer>

<!-- ══════════ SCRIPTS ══════════ -->
<script src="<?= ASSETS_URL ?>/js/store.js?v=<?= file_exists(__DIR__ . '/../assets/js/store.js') ? filemtime(__DIR__ . '/../assets/js/store.js') : 1 ?>"></script>
</body>
</html>
