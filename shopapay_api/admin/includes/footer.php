</div><!-- /main-content -->
</div><!-- /layout-wrap -->

<script>
/* ═══════════════════════════════════════════════════════════════
   SIDEBAR TOGGLE — works on all screen sizes
═══════════════════════════════════════════════════════════════ */
(function () {
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');
    const toggle    = document.getElementById('sidebarToggle');
    const icon      = document.getElementById('toggleIcon');
    const topBar    = document.getElementById('mobileTopBar');

    function isMobile() { return window.innerWidth <= 768; }

    /* Show/hide mobile top bar */
    function syncTopBar() {
        if (topBar) topBar.style.display = isMobile() ? 'flex' : 'none';
    }

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('open');
        toggle.setAttribute('aria-expanded', 'true');
        icon.classList.replace('fa-bars', 'fa-xmark');
        document.body.style.overflow = 'hidden'; // prevent bg scroll
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
        icon.classList.replace('fa-xmark', 'fa-bars');
        document.body.style.overflow = '';
    }

    function toggleSidebar() {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    }

    /* Attach events */
    toggle?.addEventListener('click', toggleSidebar);
    overlay?.addEventListener('click', closeSidebar);

    /* Close on Escape */
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) closeSidebar();
    });

    /* Close sidebar when a nav link is tapped on mobile */
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', () => {
            if (isMobile()) closeSidebar();
        });
    });

    /* Sync on resize */
    window.addEventListener('resize', () => {
        syncTopBar();
        if (!isMobile()) {
            closeSidebar();
            document.body.style.overflow = '';
        }
    });

    /* Init */
    syncTopBar();

    /* Expose globally for any inline onclick="toggleSidebar()" calls */
    window.toggleSidebar = toggleSidebar;
})();


/* ═══════════════════════════════════════════════════════════════
   ACTIVE NAV HIGHLIGHT (supports subdirectory pages)
═══════════════════════════════════════════════════════════════ */
(function () {
    const current = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-item[href]').forEach(link => {
        const href = link.getAttribute('href').split('/').pop();
        if (href && href === current) {
            link.classList.add('active');
            // add pip if missing
            if (!link.querySelector('.nav-pip')) {
                const pip = document.createElement('span');
                pip.className = 'nav-pip';
                pip.setAttribute('aria-hidden', 'true');
                link.appendChild(pip);
            }
        }
    });
})();


/* ═══════════════════════════════════════════════════════════════
   TABLE SCROLL HINT — removes hint once user scrolls table
═══════════════════════════════════════════════════════════════ */
document.querySelectorAll('.tbl-wrap').forEach(wrap => {
    wrap.addEventListener('scroll', function onScroll() {
        wrap.dataset.scrolled = 'true';
        wrap.removeEventListener('scroll', onScroll);
    }, { passive: true });
});
</script>

</body>
</html>