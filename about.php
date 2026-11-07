<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pageTitle = 'About Us — ' . SITE_NAME;
$pageDesc  = 'Learn the story behind Amaria Aesthetics — science-backed skincare crafted for Filipino skin.';

include __DIR__ . '/includes/header.php';
?>

<!-- ══ HERO ══════════════════════════════════════════════════ -->
<section style="
  min-height: 90vh;
  display: grid;
  grid-template-columns: 1fr 1fr;
  padding-top: var(--header-h);
  overflow: hidden;
">
  <!-- Left copy -->
  <div style="
    display: flex; flex-direction: column; justify-content: center;
    padding: 6rem max(3rem, var(--gutter)) 6rem max(var(--gutter), calc((100vw - var(--max-w)) / 2 + var(--gutter)));
  ">
    <span class="section-eyebrow" style="margin-bottom: 1.5rem">Our Story</span>
    <h1 style="
      font-family: var(--font-display);
      font-size: clamp(2.8rem, 5.5vw, 5rem);
      font-weight: 300; line-height: 1.08;
      letter-spacing: -.025em;
      color: var(--ink); margin-bottom: 1.75rem;
    ">
      Beauty rooted<br>in <em style="color:var(--rose-mid); font-style:italic">science</em>,<br>
      defined by<br><strong style="font-weight:400; border-bottom:2px solid var(--gold)">elegance</strong>.
    </h1>
    <p style="font-size: .95rem; line-height: 1.85; color: var(--ink-soft); max-width: 400px; margin-bottom: 2.5rem">
      Founded in the Philippines by a team of dermatologists and beauty enthusiasts,
      Amaria Aesthetics was built on a single belief — premium skincare should be
      accessible to every Filipino.
    </p>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap">
      <a href="<?= STORE_URL ?>/shop.php" class="btn btn-primary btn-lg">
        Shop the Collection <i class="fas fa-arrow-right"></i>
      </a>
      <a href="<?= STORE_URL ?>/contact.php" class="btn btn-ghost btn-lg">
        Get in Touch
      </a>
    </div>
  </div>

  <!-- Right visual -->
  <div style="
    position: relative; overflow: hidden;
    background: linear-gradient(160deg, var(--blush) 0%, var(--gold-pale) 50%, var(--ivory-deep) 100%);
  ">
    <div style="
      width: 100%; height: 100%;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      gap: 1rem; color: var(--rose-mid);
    ">
      <i class="fas fa-spa" style="font-size: 6rem; opacity: .15"></i>
      <p style="font-family:var(--font-display); font-size:1.3rem; font-style:italic; opacity:.35">
        Your brand image here
      </p>
    </div>

    <!-- Floating stat card -->
    <div style="
      position: absolute; bottom: 2.5rem; left: -1rem;
      background: rgba(255,249,244,.95); backdrop-filter: blur(20px);
      border: 1px solid rgba(229,216,207,.7);
      border-radius: var(--r-md); padding: 1.25rem 1.5rem;
      box-shadow: var(--sh-xl); min-width: 240px;
    ">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem">
        <?php foreach ([
          ['5,000+',  'Happy Clients'],
          ['100%',    'Derma-Tested'],
          ['2019',    'Founded'],
          ['PH-Made', 'With Love'],
        ] as [$num, $lbl]): ?>
          <div>
            <div style="font-family:var(--font-display); font-size:1.3rem; font-weight:500; color:var(--mocha)">
              <?= $num ?>
            </div>
            <div style="font-size:.63rem; font-weight:500; letter-spacing:.12em;
                        text-transform:uppercase; color:var(--rose)">
              <?= $lbl ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>


<!-- ══ MISSION STRIP ════════════════════════════════════════════ -->
<div style="background:var(--mocha); padding:3.5rem 0">
  <div class="container">
    <div style="
      display: grid; grid-template-columns: repeat(3,1fr);
      gap: 0; text-align: center;
    ">
      <?php foreach ([
        ['fa-flask',         'Science-First',   'Every formula is clinically tested and dermatologist-approved before it reaches your skin.'],
        ['fa-leaf',          'Clean Beauty',    'Free from harmful parabens, sulphates, and artificial fragrances. Skin-safe, always.'],
        ['fa-heart',         'Made for You',    'Crafted specifically for the diverse skin tones and concerns of Filipino women and men.'],
      ] as [$icon, $title, $desc]): ?>
        <div style="
          padding: 2rem 2.5rem;
          border-right: 1px solid rgba(255,255,255,.1);
        ">
          <div style="
            width: 52px; height: 52px;
            background: rgba(201,169,110,.2); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem; color: var(--gold); font-size: 1.1rem;
          ">
            <i class="fas <?= $icon ?>"></i>
          </div>
          <h4 style="font-family:var(--font-display); font-size:1.2rem; color:var(--ivory); margin-bottom:.65rem">
            <?= $title ?>
          </h4>
          <p style="font-size:.82rem; line-height:1.75; color:rgba(250,246,241,.55); margin:0">
            <?= $desc ?>
          </p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>


<!-- ══ STORY SECTION ════════════════════════════════════════════ -->
<section class="section-pad">
  <div class="container">
    <div style="
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 5rem; align-items: center;
    ">
      <!-- Visual -->
      <div class="reveal" style="
        aspect-ratio: 4/5; border-radius: var(--r-xl); overflow: hidden;
        background: linear-gradient(145deg, var(--blush) 0%, var(--gold-pale) 100%);
        display: flex; align-items: center; justify-content: center;
        position: relative;
      ">
        <i class="fas fa-leaf" style="font-size: 5rem; color: var(--rose-mid); opacity: .2"></i>
        <!-- Decorative ring -->
        <div style="
          position: absolute; inset: 1.5rem;
          border: 1px solid rgba(196,130,106,.2);
          border-radius: calc(var(--r-xl) - 6px);
          pointer-events: none;
        "></div>
      </div>

      <!-- Copy -->
      <div class="reveal reveal-delay-2">
        <span class="section-eyebrow">How It Started</span>
        <h2 style="margin-block: .85rem 1.5rem">From a clinic to your doorstep</h2>
        <p style="font-size:.9rem; line-height:1.85; color:var(--ink-soft); margin-bottom:1.25rem">
          Amaria Aesthetics began inside a small dermatology clinic in Cebu City in 2019.
          Our founder, frustrated by the lack of affordable clinical-grade skincare for Filipino
          complexions, began formulating solutions in-house — products that worked with the
          natural melanin, humidity, and lifestyle of Filipinos.
        </p>
        <p style="font-size:.9rem; line-height:1.85; color:var(--ink-soft); margin-bottom:2rem">
          What started as a single brightening serum distributed among clinic patients has grown
          into a full collection trusted by thousands across the archipelago. Every product
          still carries the same founding promise: clinical efficacy, at a price that respects you.
        </p>

        <blockquote style="
          padding-left: 1.5rem;
          border-left: 2px solid var(--gold);
          font-family: var(--font-display);
          font-size: 1.3rem; font-style: italic;
          color: var(--mocha); line-height: 1.4;
          margin: 0;
        ">
          "Every Filipino deserves skin they're proud of."
          <cite style="display:block; margin-top:.5rem; font-size:.75rem;
                       font-style:normal; letter-spacing:.1em;
                       text-transform:uppercase; color:var(--rose)">
            — Amaria, Founder
          </cite>
        </blockquote>
      </div>
    </div>
  </div>
</section>


<!-- ══ VALUES ════════════════════════════════════════════════════ -->
<section class="section-pad" style="background: var(--ivory-deep)">
  <div class="container">
    <div class="section-header reveal">
      <span class="section-eyebrow">What We Stand For</span>
      <h2>Our Core Values</h2>
      <p>Everything we create is guided by these principles — from lab to shelf to your skin.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem">
      <?php foreach ([
        ['fa-shield-halved', 'Safety Above All',
         'No product leaves our lab without passing rigorous patch, stability, and efficacy testing. Your safety is non-negotiable.'],
        ['fa-magnifying-glass', 'Radical Transparency',
         'We list every ingredient, every concentration. No proprietary blends used to hide poor formulation.'],
        ['fa-earth-asia', 'Sustainably Sourced',
         'We partner with local Philippine suppliers where possible, reducing our carbon footprint while supporting local communities.'],
        ['fa-hand-holding-heart', 'Cruelty-Free Always',
         'Never tested on animals. We believe effective skincare should never come at the cost of another creature\'s wellbeing.'],
        ['fa-microscope', 'Clinically Validated',
         'Partnered with licensed dermatologists and skin specialists to validate every claim we make on every product.'],
        ['fa-peso-sign', 'Accessible Luxury',
         'Premium ingredients and clinical results shouldn\'t require a premium price tag. Beauty for every budget.'],
      ] as $i => [$icon, $title, $desc]): ?>
        <div class="reveal reveal-delay-<?= ($i % 3) + 1 ?>" style="
          background: var(--ivory-warm); border: 1px solid var(--mist);
          border-radius: var(--r-md); padding: 2rem 1.75rem;
          transition: transform .35s var(--ease), box-shadow .35s var(--ease);
        " onmouseenter="this.style.transform='translateY(-5px)';this.style.boxShadow='var(--sh-lg)'"
           onmouseleave="this.style.transform='';this.style.boxShadow=''">
          <div style="
            width: 50px; height: 50px;
            background: var(--blush); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--rose-deep); font-size: 1.1rem;
            margin-bottom: 1.25rem;
          ">
            <i class="fas <?= $icon ?>"></i>
          </div>
          <h4 style="font-family:var(--font-display); font-size:1.15rem;
                     margin-bottom:.65rem; color:var(--ink)">
            <?= $title ?>
          </h4>
          <p style="font-size:.83rem; line-height:1.75; color:var(--ink-soft); margin:0">
            <?= $desc ?>
          </p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<!-- ══ PROCESS ════════════════════════════════════════════════════ -->
<section class="section-pad">
  <div class="container">
    <div class="section-header reveal">
      <span class="section-eyebrow">How We Work</span>
      <h2>From Concept to Complexion</h2>
      <p>Every Amaria product goes through a rigorous journey before it reaches your skin.</p>
    </div>

    <div style="position: relative">
      <!-- Connector line -->
      <div style="
        position: absolute; top: 28px; left: 10%; right: 10%; height: 1px;
        background: linear-gradient(to right, var(--blush), var(--gold), var(--blush));
        z-index: 0;
      "></div>

      <div style="display: grid; grid-template-columns: repeat(5,1fr); gap: 1rem; position:relative; z-index:1">
        <?php foreach ([
          ['01', 'fa-lightbulb',   'Research',    'Identify skin concerns specific to Filipino complexions'],
          ['02', 'fa-flask',       'Formulation', 'Dermatologists craft the formula from clinical-grade ingredients'],
          ['03', 'fa-vial',        'Testing',     '6-month stability and safety testing across diverse skin types'],
          ['04', 'fa-certificate', 'Validation',  'Independent lab verification of all efficacy claims'],
          ['05', 'fa-box-open',    'You',         'Carefully packaged and delivered to your door'],
        ] as $i => [$num, $icon, $title, $desc]): ?>
          <div class="reveal reveal-delay-<?= $i + 1 ?>" style="text-align:center">
            <div style="
              width: 56px; height: 56px;
              background: var(--mocha); color: var(--ivory);
              border-radius: 50%;
              display: flex; align-items: center; justify-content: center;
              font-size: 1.1rem; margin: 0 auto 1.25rem;
              box-shadow: var(--sh-md);
              border: 3px solid var(--ivory);
            ">
              <i class="fas <?= $icon ?>"></i>
            </div>
            <div style="font-size:.6rem; font-weight:700; letter-spacing:.2em;
                        text-transform:uppercase; color:var(--gold); margin-bottom:.35rem">
              Step <?= $num ?>
            </div>
            <h5 style="font-family:var(--font-display); font-size:1rem;
                       color:var(--ink); margin-bottom:.5rem">
              <?= $title ?>
            </h5>
            <p style="font-size:.75rem; line-height:1.65; color:var(--ink-soft); margin:0">
              <?= $desc ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>


<!-- ══ TEAM ══════════════════════════════════════════════════════ -->
<section class="section-pad" style="background: var(--ivory-deep)">
  <div class="container">
    <div class="section-header reveal">
      <span class="section-eyebrow">The People</span>
      <h2>Meet the Team</h2>
      <p>A passionate group of dermatologists, formulators, and beauty lovers based in the Philippines.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(4,1fr); gap: 1.5rem">
      <?php foreach ([
        ['A',  'Amaria dela Cruz',     'Founder & Formulator',         'Dermatologist with 12 years clinical experience specializing in Filipino skin.'],
        ['R',  'Rafael Santos',        'Head of R&D',                  'Cosmetic chemist with a background in clinical dermatology research.'],
        ['M',  'Maya Reyes',           'Brand & Creative Director',    'Former luxury beauty editor passionate about clean, intentional aesthetics.'],
        ['J',  'Dr. Jose Villanueva', 'Dermatology Advisor',          'Board-certified dermatologist and clinical skin research consultant.'],
      ] as $i => [$initial, $name, $role, $bio]): ?>
        <div class="reveal reveal-delay-<?= $i + 1 ?>" style="text-align: center">
          <!-- Avatar -->
          <div style="
            width: 110px; height: 110px; border-radius: 50%;
            background: linear-gradient(135deg, var(--blush), var(--gold-pale));
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
            font-family: var(--font-display); font-size: 2.5rem;
            color: var(--rose-deep); font-style: italic;
            border: 3px solid var(--mist);
            box-shadow: var(--sh-sm);
          ">
            <?= $initial ?>
          </div>
          <h4 style="font-family:var(--font-display); font-size:1.1rem;
                     color:var(--ink); margin-bottom:.25rem">
            <?= $name ?>
          </h4>
          <div style="font-size:.68rem; font-weight:500; letter-spacing:.12em;
                      text-transform:uppercase; color:var(--rose); margin-bottom:.75rem">
            <?= $role ?>
          </div>
          <p style="font-size:.8rem; line-height:1.7; color:var(--ink-soft); margin:0">
            <?= $bio ?>
          </p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<!-- ══ STATS ═════════════════════════════════════════════════════ -->
<section style="background: linear-gradient(135deg, var(--mocha) 0%, var(--rose-deep) 100%); padding: 5rem 0">
  <div class="container">
    <div style="display: grid; grid-template-columns: repeat(4,1fr); gap: 2rem; text-align:center">
      <?php foreach ([
        ['5,000+',  'Satisfied Clients',       'Across the Philippines'],
        ['15+',     'Product Formulations',    'Clinically tested'],
        ['6 Years', 'In Business',             'Since 2019'],
        ['100%',    'Dermatologist Approved',  'Every single product'],
      ] as $i => [$num, $title, $sub]): ?>
        <div class="reveal reveal-delay-<?= $i + 1 ?>">
          <div style="
            font-family: var(--font-display);
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 300; color: var(--ivory);
            line-height: 1; margin-bottom: .5rem;
          "><?= $num ?></div>
          <div style="font-size:.82rem; font-weight:500; color:var(--gold-light); margin-bottom:.25rem">
            <?= $title ?>
          </div>
          <div style="font-size:.7rem; color:rgba(250,246,241,.45)">
            <?= $sub ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<!-- ══ CTA ════════════════════════════════════════════════════════ -->
<section class="section-pad" style="text-align:center; background:var(--ivory)">
  <div class="container reveal">
    <span class="section-eyebrow">Ready to Glow?</span>
    <h2 style="margin-block:.85rem 1rem">Discover Your Ritual</h2>
    <p style="color:var(--ink-soft); margin:0 auto 2.5rem; max-width:420px; font-size:.95rem">
      Explore our full collection and find the perfect products for your skin type and concerns.
    </p>
    <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap">
      <a href="<?= STORE_URL ?>/shop.php" class="btn btn-primary btn-lg">
        Shop Now <i class="fas fa-arrow-right"></i>
      </a>
      <a href="<?= STORE_URL ?>/contact.php" class="btn btn-outline btn-lg">
        Talk to an Expert
      </a>
    </div>
  </div>
</section>

<style>
@media (max-width:1024px) {
  section[style*="grid-template-columns:1fr 1fr"]:first-of-type { grid-template-columns:1fr!important; }
  div[style*="grid-template-columns:1fr 1fr"] { grid-template-columns:1fr!important; }
  div[style*="grid-template-columns:repeat(5,1fr)"] { grid-template-columns:repeat(3,1fr)!important; }
  div[style*="grid-template-columns:repeat(4,1fr)"] { grid-template-columns:repeat(2,1fr)!important; }
}
@media (max-width:768px) {
  div[style*="grid-template-columns:repeat(3,1fr)"] { grid-template-columns:1fr!important; }
  div[style*="grid-template-columns:repeat(5,1fr)"] { grid-template-columns:repeat(2,1fr)!important; }
  div[style*="grid-template-columns:repeat(3,1fr"]:has(.reveal) { grid-template-columns:1fr!important; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>