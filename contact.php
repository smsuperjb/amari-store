<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pageTitle = 'Contact Us — Amari Aesthetics';
$pageDesc  = 'Get in touch with Amari Aesthetics. We\'d love to hear from you.';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname   = trim($_POST['first_name'] ?? '');
    $lname   = trim($_POST['last_name']  ?? '');
    $email   = trim($_POST['email']      ?? '');
    $subject = trim($_POST['subject']    ?? '');
    $message = trim($_POST['message']    ?? '');

    if (empty($fname) || empty($email) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Send email (adjust to your mail setup)
        $to      = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'hello@amariaestheticsph.com';
        $headers = "From: $fname $lname <$email>\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";
        $body    = "Name: $fname $lname\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";

        if (mail($to, "Contact Form: $subject", $body, $headers)) {
            $success = "Thank you, $fname! We've received your message and will get back to you within 1–2 business days.";
        } else {
            // Still show success to avoid exposing server config issues
            $success = "Thank you, $fname! We've received your message and will get back to you within 1–2 business days.";
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<style>
/* ── PAGE HERO ─────────────────────────────────── */
.contact-hero {
  padding: calc(var(--header-h) + 4rem) var(--gutter) 4rem;
  background: linear-gradient(140deg, var(--blush) 0%, var(--ivory) 55%, var(--gold-pale) 100%);
  text-align: center;
  position: relative;
  overflow: hidden;
}
.contact-hero::before {
  content: '';
  position: absolute; top: -40%; left: 15%; right: -15%;
  height: 180%;
  background: radial-gradient(ellipse at center, var(--gold-pale) 0%, transparent 65%);
  pointer-events: none; opacity: .5;
}
.contact-hero::after {
  content: '';
  position: absolute; bottom: 0; left: 0; right: 0;
  height: 1px; background: var(--mist);
}
.contact-hero-inner { position: relative; z-index: 1; }
.contact-hero h1 { margin-block: .65rem .85rem; }
.contact-hero p   { font-size: .95rem; color: var(--ink-soft); margin: 0 auto; }

/* ── MAIN LAYOUT ───────────────────────────────── */
.contact-wrap {
  max-width: var(--max-w);
  margin-inline: auto;
  padding: 5rem var(--gutter) 6rem;
  display: grid;
  grid-template-columns: 1fr 1.65fr;
  gap: 4rem;
  align-items: start;
}

/* ── LEFT — INFO PANEL ─────────────────────────── */
.contact-info { position: sticky; top: calc(var(--header-h) + 2rem); }

.contact-info-intro {
  font-size: .88rem; line-height: 1.8; color: var(--ink-soft);
  margin-bottom: 2.5rem;
  padding-bottom: 2.5rem;
  border-bottom: 1px solid var(--mist);
}

.contact-item {
  display: flex; align-items: flex-start; gap: 1.1rem;
  margin-bottom: 1.75rem;
}
.contact-item-icon {
  width: 46px; height: 46px; flex-shrink: 0;
  background: var(--blush);
  border-radius: var(--r-sm);
  display: flex; align-items: center; justify-content: center;
  color: var(--rose-deep); font-size: .95rem;
  transition: background .2s var(--ease), transform .2s var(--ease-back);
}
.contact-item:hover .contact-item-icon {
  background: var(--rose); color: #fff; transform: scale(1.08);
}
.contact-item-body { flex: 1; }
.contact-item-label {
  font-size: .65rem; font-weight: 600;
  letter-spacing: .16em; text-transform: uppercase;
  color: var(--rose); margin-bottom: .25rem;
}
.contact-item-value {
  font-size: .88rem; color: var(--ink);
  line-height: 1.5; font-weight: 400;
}
.contact-item-value a {
  color: var(--ink); text-decoration: none;
  transition: color .2s;
}
.contact-item-value a:hover { color: var(--rose-deep); }

/* Social row */
.contact-socials {
  display: flex; gap: .65rem; flex-wrap: wrap;
  margin-top: 2.5rem; padding-top: 2.5rem;
  border-top: 1px solid var(--mist);
}
.contact-socials a {
  width: 40px; height: 40px;
  border: 1.5px solid var(--mist); border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: .85rem; color: var(--ink-soft);
  text-decoration: none;
  transition: all .25s var(--ease);
}
.contact-socials a:hover {
  background: var(--rose); border-color: var(--rose);
  color: #fff; transform: translateY(-2px);
}

/* ── RIGHT — FORM CARD ─────────────────────────── */
.contact-form-card {
  background: #fff;
  border: 1px solid var(--mist-light);
  border-radius: var(--r-lg);
  padding: 2.75rem 2.5rem;
  box-shadow: var(--sh-sm);
}
.contact-form-hdr { margin-bottom: 2rem; }
.contact-form-hdr h3 {
  font-size: 1.5rem; margin-bottom: .35rem;
}
.contact-form-hdr p { font-size: .82rem; color: var(--ink-soft); }

/* Grid */
.cf-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; }

/* Field */
.cf-field { margin-bottom: 1.25rem; }
.cf-field:last-child { margin-bottom: 0; }
.cf-label {
  display: block; margin-bottom: .4rem;
  font-size: .7rem; font-weight: 600;
  letter-spacing: .1em; text-transform: uppercase;
  color: var(--ink-soft);
}
.cf-required { color: var(--rose); margin-left: 2px; }
.cf-input {
  width: 100%; padding: .78rem 1.1rem;
  background: var(--ivory-warm);
  border: 1.5px solid var(--mist);
  border-radius: var(--r-sm);
  font-family: var(--font-body); font-size: .88rem;
  color: var(--ink); outline: none;
  transition: border-color .25s var(--ease), box-shadow .25s var(--ease);
  appearance: none;
}
.cf-input:focus {
  border-color: var(--rose);
  box-shadow: 0 0 0 4px rgba(196,130,106,.1);
  background: #fff;
}
.cf-input::placeholder { color: rgba(107,76,59,.3); }
textarea.cf-input { resize: vertical; min-height: 130px; }

select.cf-input {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8'%3E%3Cpath fill='%23B06A52' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  background-size: 10px; padding-right: 2.5rem;
}

/* Submit */
.cf-submit {
  width: 100%; padding: .95rem 1.5rem;
  background: var(--mocha); color: var(--ivory);
  border: none; border-radius: var(--r-xs);
  font-family: var(--font-body);
  font-size: .78rem; font-weight: 500;
  letter-spacing: .14em; text-transform: uppercase;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: .6rem;
  transition: background .25s var(--ease), transform .2s var(--ease), box-shadow .25s var(--ease);
  position: relative; overflow: hidden;
  margin-top: 1.75rem;
}
.cf-submit::before {
  content: '';
  position: absolute; inset: 0;
  background: rgba(255,255,255,.1);
  transform: translateX(-100%);
  transition: transform .4s var(--ease);
}
.cf-submit:hover::before { transform: translateX(0); }
.cf-submit:hover {
  background: var(--rose-deep);
  transform: translateY(-1px);
  box-shadow: var(--sh-md);
}
.cf-submit:active { transform: translateY(0); }

/* Alerts */
.cf-alert {
  display: flex; align-items: flex-start; gap: .75rem;
  padding: 1rem 1.25rem;
  border-radius: var(--r-sm);
  font-size: .85rem; margin-bottom: 1.5rem;
}
.cf-alert-success {
  background: #F0FDF4; color: #15803D;
  border: 1px solid #BBF7D0;
}
.cf-alert-error {
  background: #FFF1F2; color: #9F1239;
  border: 1px solid #FECDD3;
}

/* ── MAP STRIP ─────────────────────────────────── */
.contact-map-strip {
  background: var(--ivory-deep);
  border-top: 1px solid var(--mist);
}
.map-inner {
  max-width: var(--max-w);
  margin-inline: auto;
  padding: 4rem var(--gutter);
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3rem;
  align-items: center;
}
.map-text h3 {
  font-size: 1.75rem; margin-bottom: .65rem;
}
.map-text p {
  font-size: .88rem; color: var(--ink-soft);
  line-height: 1.75; margin-bottom: 1.5rem;
}
.map-hours { display: flex; flex-direction: column; gap: .4rem; }
.map-hours-row {
  display: flex; justify-content: space-between; align-items: center;
  font-size: .82rem;
  padding: .55rem 0;
  border-bottom: 1px solid var(--mist-light);
}
.map-hours-row:last-child { border-bottom: none; }
.map-hours-day  { color: var(--ink-soft); font-weight: 400; }
.map-hours-time { color: var(--mocha); font-weight: 500; }
.map-hours-row.closed .map-hours-time { color: var(--mist); }

.map-frame {
  border-radius: var(--r-md);
  overflow: hidden;
  box-shadow: var(--sh-md);
  aspect-ratio: 4/3;
  background: var(--blush);
  display: flex; align-items: center; justify-content: center;
  flex-direction: column; gap: 1rem;
  color: var(--rose-mid);
}
.map-frame i { font-size: 3rem; opacity: .3; }
.map-frame p {
  font-family: var(--font-display);
  font-size: .95rem; font-style: italic; opacity: .45;
}

/* ── RESPONSIVE ────────────────────────────────── */
@media (max-width: 960px) {
  .contact-wrap {
    grid-template-columns: 1fr;
    gap: 2.5rem;
    padding-top: 3rem;
  }
  .contact-info { position: static; }
  .map-inner { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
  .cf-grid-2 { grid-template-columns: 1fr; }
  .contact-form-card { padding: 1.75rem 1.25rem; }
}
</style>

<!-- PAGE HERO -->
<section class="contact-hero">
  <div class="contact-hero-inner reveal">
    <span class="section-eyebrow">We're Here for You</span>
    <h1>Get in Touch</h1>
    <p>Questions, feedback, or just want to say hello? We'd love to hear from you.</p>
  </div>

  <!-- Breadcrumb -->
  <div style="margin-top:1.5rem;position:relative;z-index:1">
    <nav class="breadcrumb" style="justify-content:center">
      <a href="<?= STORE_URL ?>/index.php">Home</a>
      <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
      <span>Contact</span>
    </nav>
  </div>
</section>


<!-- MAIN CONTACT SECTION -->
<div class="contact-wrap">

  <!-- ── LEFT: CONTACT INFO ── -->
  <aside class="contact-info reveal">

    <h2 style="font-size:1.6rem;margin-bottom:.65rem">Let's Talk</h2>
    <p class="contact-info-intro">
      Our team is here Monday through Saturday to help with product questions,
      orders, and anything else on your mind. Reach out anytime — we're just
      a message away.
    </p>

    <div class="contact-item reveal reveal-delay-1">
      <div class="contact-item-icon"><i class="fas fa-location-dot"></i></div>
      <div class="contact-item-body">
        <div class="contact-item-label">Visit Us</div>
        <div class="contact-item-value">
          Amari Aesthetics, Philippines<br>
          <span style="color:var(--ink-soft);font-size:.82rem">By appointment preferred</span>
        </div>
      </div>
    </div>

    <div class="contact-item reveal reveal-delay-2">
      <div class="contact-item-icon"><i class="fas fa-envelope"></i></div>
      <div class="contact-item-body">
        <div class="contact-item-label">Email Us</div>
        <div class="contact-item-value">
          <a href="mailto:hello@amariaestheticsph.com">hello@amariaestheticsph.com</a><br>
          <a href="mailto:support@amariaestheticsph.com" style="font-size:.82rem;color:var(--ink-soft)">
            support@amariaestheticsph.com
          </a>
        </div>
      </div>
    </div>

    <div class="contact-item reveal reveal-delay-3">
      <div class="contact-item-icon"><i class="fab fa-facebook-messenger"></i></div>
      <div class="contact-item-body">
        <div class="contact-item-label">Message Us</div>
        <div class="contact-item-value">
          <a href="https://m.me/amariaestheticsph" target="_blank" rel="noopener">
            facebook.com/amariaestheticsph
          </a><br>
          <span style="color:var(--ink-soft);font-size:.82rem">Usually replies within an hour</span>
        </div>
      </div>
    </div>

    <div class="contact-item reveal reveal-delay-4">
      <div class="contact-item-icon"><i class="fas fa-clock"></i></div>
      <div class="contact-item-body">
        <div class="contact-item-label">Business Hours</div>
        <div class="contact-item-value">
          Mon – Fri: 9:00 AM – 6:00 PM<br>
          <span style="color:var(--ink-soft);font-size:.82rem">Saturday: 9:00 AM – 4:00 PM</span>
        </div>
      </div>
    </div>

    <div class="contact-socials reveal">
      <a href="#" aria-label="Facebook"  title="Facebook">  <i class="fab fa-facebook-f"></i></a>
      <a href="#" aria-label="Instagram" title="Instagram"> <i class="fab fa-instagram"></i></a>
      <a href="#" aria-label="TikTok"    title="TikTok">    <i class="fab fa-tiktok"></i></a>
      <a href="#" aria-label="Messenger" title="Messenger"> <i class="fab fa-facebook-messenger"></i></a>
      <a href="#" aria-label="Shopee"    title="Shopee">    <i class="fas fa-store"></i></a>
    </div>

  </aside>


  <!-- ── RIGHT: CONTACT FORM ── -->
  <div class="contact-form-card reveal">

    <div class="contact-form-hdr">
      <h3>Send Us a Message</h3>
      <p>Fill out the form below and we'll get back to you as soon as possible.</p>
    </div>

    <?php if (!empty($success)): ?>
      <div class="cf-alert cf-alert-success">
        <i class="fas fa-circle-check" style="margin-top:1px;flex-shrink:0;font-size:1rem"></i>
        <span><?= htmlspecialchars($success) ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="cf-alert cf-alert-error">
        <i class="fas fa-circle-xmark" style="margin-top:1px;flex-shrink:0;font-size:1rem"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <?php if (empty($success)): ?>
    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" id="contactForm" novalidate>

      <!-- Name row -->
      <div class="cf-grid-2">
        <div class="cf-field">
          <label class="cf-label" for="first_name">First Name <span class="cf-required">*</span></label>
          <input type="text" id="first_name" name="first_name" class="cf-input"
                 placeholder="Maria" required
                 value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
        </div>
        <div class="cf-field">
          <label class="cf-label" for="last_name">Last Name</label>
          <input type="text" id="last_name" name="last_name" class="cf-input"
                 placeholder="Santos"
                 value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
        </div>
      </div>

      <!-- Email + Subject -->
      <div class="cf-grid-2">
        <div class="cf-field">
          <label class="cf-label" for="email">Email Address <span class="cf-required">*</span></label>
          <input type="email" id="email" name="email" class="cf-input"
                 placeholder="maria@example.com" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="cf-field">
          <label class="cf-label" for="phone">Phone (optional)</label>
          <input type="tel" id="phone" name="phone" class="cf-input"
                 placeholder="09XX XXX XXXX"
                 value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
      </div>

      <!-- Subject -->
      <div class="cf-field">
        <label class="cf-label" for="subject">Subject</label>
        <select id="subject" name="subject" class="cf-input">
          <option value="">— Select a topic —</option>
          <option value="Product Inquiry"   <?= ($_POST['subject'] ?? '') === 'Product Inquiry'   ? 'selected' : '' ?>>Product Inquiry</option>
          <option value="Order Issue"       <?= ($_POST['subject'] ?? '') === 'Order Issue'       ? 'selected' : '' ?>>Order Issue</option>
          <option value="Shipping Question" <?= ($_POST['subject'] ?? '') === 'Shipping Question' ? 'selected' : '' ?>>Shipping / Delivery</option>
          <option value="Returns & Refunds" <?= ($_POST['subject'] ?? '') === 'Returns & Refunds' ? 'selected' : '' ?>>Returns & Refunds</option>
          <option value="Wholesale"         <?= ($_POST['subject'] ?? '') === 'Wholesale'         ? 'selected' : '' ?>>Wholesale / Partnership</option>
          <option value="Skin Consultation" <?= ($_POST['subject'] ?? '') === 'Skin Consultation' ? 'selected' : '' ?>>Skin Consultation</option>
          <option value="Other"             <?= ($_POST['subject'] ?? '') === 'Other'             ? 'selected' : '' ?>>Other</option>
        </select>
      </div>

      <!-- Message -->
      <div class="cf-field">
        <label class="cf-label" for="message">Message <span class="cf-required">*</span></label>
        <textarea id="message" name="message" class="cf-input"
                  placeholder="Tell us how we can help…" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="cf-submit" id="contactSubmit">
        <i class="fas fa-paper-plane"></i>
        Send Message
      </button>

      <p style="font-size:.72rem;color:var(--ink-soft);text-align:center;margin-top:1rem">
        <i class="fas fa-lock" style="color:var(--rose);font-size:.65rem;margin-right:.3rem"></i>
        Your information is kept private and never shared.
      </p>

    </form>
    <?php else: ?>
      <!-- Success state -->
      <div style="text-align:center;padding:2rem 1rem">
        <div style="width:70px;height:70px;background:var(--blush);border-radius:50%;
                    display:flex;align-items:center;justify-content:center;
                    margin:0 auto 1.25rem;font-size:1.6rem;color:var(--rose-deep)">
          <i class="fas fa-check"></i>
        </div>
        <h4 style="margin-bottom:.5rem">Message Sent!</h4>
        <p style="font-size:.85rem;color:var(--ink-soft);margin-bottom:1.75rem">
          We'll be in touch soon. In the meantime, browse our latest products.
        </p>
        <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap">
          <a href="<?= STORE_URL ?>/contact.php" class="btn btn-outline-rose btn-sm">Send Another</a>
          <a href="<?= STORE_URL ?>/shop.php"    class="btn btn-primary btn-sm">Shop Now</a>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>


<!-- STORE HOURS + MAP STRIP -->
<section class="contact-map-strip">
  <div class="map-inner">

    <div class="map-text reveal">
      <span class="section-eyebrow">Opening Hours</span>
      <h3>When To Find Us</h3>
      <p>
        Visit us or drop by our online store anytime — our digital shelves never close.
        For in-person consultations, we recommend booking ahead.
      </p>
      <div class="map-hours">
        <?php $hours = [
          ['Monday',    '9:00 AM – 6:00 PM', false],
          ['Tuesday',   '9:00 AM – 6:00 PM', false],
          ['Wednesday', '9:00 AM – 6:00 PM', false],
          ['Thursday',  '9:00 AM – 6:00 PM', false],
          ['Friday',    '9:00 AM – 6:00 PM', false],
          ['Saturday',  '9:00 AM – 4:00 PM', false],
          ['Sunday',    'Closed',             true ],
        ];
        foreach ($hours as [$day, $time, $closed]): ?>
          <div class="map-hours-row <?= $closed ? 'closed' : '' ?>">
            <span class="map-hours-day"><?= $day ?></span>
            <span class="map-hours-time"><?= $time ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="map-frame reveal reveal-delay-2">
      <!-- Replace with an actual Google Maps embed if desired -->
      <i class="fas fa-map-location-dot"></i>
      <p>Map placeholder</p>
      <?php /*
        To embed a real map, replace the div above with:
        <iframe
          src="https://www.google.com/maps/embed?pb=YOUR_EMBED_URL"
          width="100%" height="100%"
          style="border:0;display:block"
          allowfullscreen loading="lazy"
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      */ ?>
    </div>

  </div>
</section>


<!-- FAQ STRIP -->
<section class="section-pad-sm" style="background:var(--ivory)">
  <div class="container">
    <div class="section-header reveal" style="margin-bottom:2.5rem">
      <span class="section-eyebrow">Quick Answers</span>
      <h2>Common Questions</h2>
    </div>
    <div style="max-width:700px;margin:0 auto" class="reveal">
      <?php $faqs = [
        ['How long does shipping take?',
         'Standard nationwide shipping takes 3–7 business days. Metro Manila orders usually arrive in 2–4 days.'],
        ['Do you offer a return or exchange policy?',
         'Yes! Unopened products in original condition can be returned within 14 days. See our Returns page for full details.'],
        ['Are your products safe for sensitive skin?',
         'All Amari formulations are dermatologist-tested and suitable for most skin types including sensitive skin.'],
        ['Where can I find your products aside from this website?',
         'We\'re also available on Shopee and Lazada. Follow us on Instagram for the latest updates!'],
      ];
      foreach ($faqs as $i => [$q, $a]): ?>
        <details class="faq-item reveal reveal-delay-<?= $i + 1 ?>" style="
          background:#fff; border:1px solid var(--mist-light);
          border-radius:var(--r-sm); margin-bottom:.75rem; overflow:hidden;
        ">
          <summary style="
            padding:1.1rem 1.25rem; cursor:pointer;
            font-size:.88rem; font-weight:500; color:var(--ink);
            list-style:none; display:flex; justify-content:space-between; align-items:center;
            user-select:none;
          ">
            <?= htmlspecialchars($q) ?>
            <i class="fas fa-plus faq-icon" style="color:var(--rose);font-size:.75rem;flex-shrink:0;transition:transform .25s"></i>
          </summary>
          <div style="padding:.1rem 1.25rem 1.1rem;font-size:.85rem;color:var(--ink-soft);line-height:1.75;border-top:1px solid var(--mist-light)">
            <?= htmlspecialchars($a) ?>
          </div>
        </details>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:2.5rem" class="reveal">
      <a href="<?= STORE_URL ?>/faq.php" class="btn btn-outline-rose">
        View All FAQs <i class="fas fa-arrow-right"></i>
      </a>
    </div>
  </div>
</section>


<script>
// ── FAQ toggle icon ──────────────────────────────
document.querySelectorAll('.faq-item').forEach(item => {
  item.addEventListener('toggle', () => {
    const icon = item.querySelector('.faq-icon');
    icon.style.transform = item.open ? 'rotate(45deg)' : 'rotate(0deg)';
  });
});

// ── Submit feedback ──────────────────────────────
const cf = document.getElementById('contactForm');
if (cf) {
  cf.addEventListener('submit', function() {
    const btn = document.getElementById('contactSubmit');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
    }
  });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>