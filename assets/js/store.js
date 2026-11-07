/* ============================================================
   AMARIA AESTHETICS — store.js
   Handles: cursor, header, nav, search, reveal, toast, cart
   ============================================================ */

(function () {
  'use strict';

  /* ── CUSTOM CURSOR ───────────────────────────────────────── */
  if (window.matchMedia('(pointer: fine)').matches) {
    const cursor     = document.createElement('div'); cursor.className = 'cursor';
    const cursorRing = document.createElement('div'); cursorRing.className = 'cursor-ring';
    document.body.prepend(cursor, cursorRing);

    let mx = 0, my = 0, rx = 0, ry = 0;

    document.addEventListener('mousemove', e => {
      mx = e.clientX; my = e.clientY;
      cursor.style.left = mx + 'px'; cursor.style.top = my + 'px';
    });

    (function animRing() {
      rx += (mx - rx) * .14;
      ry += (my - ry) * .14;
      cursorRing.style.left = rx + 'px';
      cursorRing.style.top  = ry + 'px';
      requestAnimationFrame(animRing);
    })();

    document.querySelectorAll('a, button, [data-hover]').forEach(el => {
      el.addEventListener('mouseenter', () => { cursor.classList.add('hovering'); cursorRing.classList.add('hovering'); });
      el.addEventListener('mouseleave', () => { cursor.classList.remove('hovering'); cursorRing.classList.remove('hovering'); });
    });
  }

  /* ── HEADER SCROLL ───────────────────────────────────────── */
  const header = document.getElementById('siteHeader');
  if (header) {
    const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 40);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ── MOBILE NAV ──────────────────────────────────────────── */
  const hamburger  = document.getElementById('hamburger');
  const mainNav    = document.getElementById('mainNav');
  const navOverlay = document.getElementById('navOverlay');

  function closeNav() {
    hamburger?.classList.remove('is-open');
    mainNav?.classList.remove('is-open');
    navOverlay?.classList.remove('is-open');
    hamburger?.setAttribute('aria-expanded', 'false');
  }

  hamburger?.addEventListener('click', () => {
    const open = mainNav?.classList.toggle('is-open');
    hamburger.classList.toggle('is-open', open);
    navOverlay?.classList.toggle('is-open', open);
    hamburger.setAttribute('aria-expanded', String(open));
  });

  navOverlay?.addEventListener('click', closeNav);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeNav(); closeSearch(); } });

  /* ── SEARCH DRAWER ───────────────────────────────────────── */
  const searchToggle = document.getElementById('searchToggle');
  const searchClose  = document.getElementById('searchClose');
  const searchDrawer = document.getElementById('searchDrawer');
  const searchInput  = document.getElementById('searchInput');

  function openSearch()  {
    searchDrawer?.classList.add('is-open');
    searchDrawer?.setAttribute('aria-hidden', 'false');
    setTimeout(() => searchInput?.focus(), 200);
  }
  function closeSearch() {
    searchDrawer?.classList.remove('is-open');
    searchDrawer?.setAttribute('aria-hidden', 'true');
  }

  searchToggle?.addEventListener('click', () => {
    searchDrawer?.classList.contains('is-open') ? closeSearch() : openSearch();
  });
  searchClose?.addEventListener('click', closeSearch);

  /* ── SCROLL REVEAL ───────────────────────────────────────── */
  const revealEls = document.querySelectorAll('.reveal');
  if (revealEls.length) {
    const revealObs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-revealed');
          revealObs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    revealEls.forEach(el => revealObs.observe(el));
  }

  /* ── TOAST SYSTEM ────────────────────────────────────────── */
  window.showToast = function (msg, type = 'success', duration = 4000) {
    const stack = document.getElementById('toastStack');
    if (!stack) return;

    const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', info: 'fa-circle-info' };
    const toast = document.createElement('div');
    toast.className = `toast-item is-${type}`;
    toast.innerHTML = `
      <i class="fas ${icons[type] || icons.info} toast-icon"></i>
      <span class="toast-msg">${msg}</span>
    `;
    stack.appendChild(toast);

    requestAnimationFrame(() => {
      requestAnimationFrame(() => toast.classList.add('is-visible'));
    });

    setTimeout(() => {
      toast.classList.remove('is-visible');
      toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }, duration);
  };

  /* ── ADD TO CART (AJAX) ──────────────────────────────────── */
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-add-cart]');
    if (!btn) return;

    e.preventDefault();
    const productId = btn.dataset.addCart;
    const qty       = btn.dataset.qty || 1;

    btn.disabled = true;
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
      const res  = await fetch(STORE_URL + '/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&product_id=${productId}&qty=${qty}`
      });
      const data = await res.json();

      if (data.success) {
        showToast(data.message || 'Added to cart!', 'success');
        updateCartDot(data.cart_count);
        btn.innerHTML = '<i class="fas fa-check"></i> Added';
        setTimeout(() => { btn.innerHTML = original; btn.disabled = false; }, 2200);
      } else {
        showToast(data.message || 'Could not add to cart.', 'error');
        btn.innerHTML = original; btn.disabled = false;
      }
    } catch {
      showToast('Something went wrong.', 'error');
      btn.innerHTML = original; btn.disabled = false;
    }
  });

  function updateCartDot(count) {
    const dot = document.getElementById('cartDot');
    if (!dot) return;
    dot.textContent = count > 0 ? count : '';
    dot.classList.toggle('visible', count > 0);
  }

  /* ── WISHLIST TOGGLE ─────────────────────────────────────── */
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.pc-wishlist');
    if (!btn) return;
    btn.classList.toggle('is-wishlisted');
    const wishlisted = btn.classList.contains('is-wishlisted');
    btn.querySelector('i')?.classList.toggle('fas', wishlisted);
    btn.querySelector('i')?.classList.toggle('far', !wishlisted);
    showToast(wishlisted ? 'Added to wishlist ♥' : 'Removed from wishlist', wishlisted ? 'success' : 'info');
  });

  /* ── NEWSLETTER ──────────────────────────────────────────── */
  window.handleNewsletter = function(e) {
    e.preventDefault();
    const input = e.target.querySelector('input[type="email"]');
    if (!input?.value) return;
    showToast('Thank you! You\'re on the list ✦', 'success');
    input.value = '';
  };

  /* ── QUANTITY CONTROLS ───────────────────────────────────── */
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-qty-change]');
    if (!btn) return;
    const wrap  = btn.closest('[data-qty-wrap]');
    const input = wrap?.querySelector('[data-qty-input]');
    if (!input) return;
    const delta = parseInt(btn.dataset.qtyChange, 10);
    const val   = Math.max(1, (parseInt(input.value, 10) || 1) + delta);
    input.value = val;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });

  /* ── PRODUCT IMAGE ZOOM (single page) ───────────────────── */
  document.addEventListener('click', (e) => {
    const thumb = e.target.closest('[data-thumb]');
    if (!thumb) return;
    const src  = thumb.dataset.thumb;
    const main = document.getElementById('mainProductImg');
    if (main && src) {
      main.src = src;
      document.querySelectorAll('[data-thumb]').forEach(t => t.classList.remove('is-active'));
      thumb.classList.add('is-active');
    }
  });

  /* ── STORE URL GLOBAL ────────────────────────────────────── */
  // Exposed for inline scripts that need the base URL
  if (typeof STORE_URL === 'undefined') {
    window.STORE_URL = '';
  }

})();
