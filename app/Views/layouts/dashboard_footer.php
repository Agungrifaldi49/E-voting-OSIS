<?php
// app/Views/layouts/dashboard_footer.php
// Dedicated Footer Ringkas untuk Panel Dashboard (Admin, Guru, Siswa)
?>
        <!-- Dedicated Compact Dashboard Footer -->
        <footer class="dashboard-footer bg-white border-top py-3 px-4 mt-auto">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 small text-muted">
                <div>
                    &copy; <?= date('Y') ?> <strong><?= SCHOOL_NAME ?></strong> &bull; <?= APP_NAME ?>
                </div>
                <div>
                    Versi <?= APP_VERSION ?> &bull; Zona Waktu: <strong>WIB (Asia/Jakarta)</strong>
                </div>
            </div>
        </footer>
    </div> <!-- Close main content wrapper -->
</div> <!-- Close dashboard wrapper -->

<?php if (AuthHelper::check() && in_array(AuthHelper::role(), ['siswa', 'guru'], true)): 
    $userRole = AuthHelper::role();
    $currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!-- Mobile Bottom Navigation Bar for Students & Teachers (HP App-Style) -->
<nav class="mobile-bottom-nav d-lg-none" aria-label="Navigasi Bawah">
    <a href="<?= base_url($userRole . '/dashboard.php') ?>" class="nav-item <?= ($currentScript === 'dashboard.php') ? 'active' : '' ?>">
        <i class="bi bi-grid-fill"></i>
        <span>Beranda</span>
    </a>
    <a href="<?= base_url($userRole . '/candidates.php') ?>" class="nav-item <?= ($currentScript === 'candidates.php') ? 'active' : '' ?>">
        <i class="bi bi-people-fill"></i>
        <span>Paslon</span>
    </a>
    <a href="<?= base_url('voting.php') ?>" class="nav-item nav-item-vote <?= ($currentScript === 'voting.php') ? 'active' : '' ?>">
        <div class="vote-icon-bubble">
            <i class="bi bi-check2-square"></i>
        </div>
        <span>Bilik Suara</span>
    </a>
    <a href="<?= base_url($userRole . '/status.php') ?>" class="nav-item <?= ($currentScript === 'status.php') ? 'active' : '' ?>">
        <i class="bi bi-patch-check-fill"></i>
        <span>Status</span>
    </a>
    <a href="<?= base_url($userRole . '/profile.php') ?>" class="nav-item <?= ($currentScript === 'profile.php') ? 'active' : '' ?>">
        <i class="bi bi-person-circle"></i>
        <span>Profil</span>
    </a>
</nav>
<?php endif; ?>

<!-- Bootstrap 5.3 JS Bundle (Ensured loaded from head with safety check) -->
<script>
if (typeof bootstrap === 'undefined') {
    var bsScript = document.createElement('script');
    bsScript.src = "<?= asset('assets/js/bootstrap.bundle.min.js') ?>";
    bsScript.onerror = function() {
        var cdnScript = document.createElement('script');
        cdnScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
        document.head.appendChild(cdnScript);
    };
    document.head.appendChild(bsScript);
}
</script>

<!-- Chart.js 4.4 CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Main Interactive JS with cache busting -->
<script src="<?= asset('assets/js/main.js') ?>?v=<?= file_exists(PUBLIC_PATH . '/assets/js/main.js') ? filemtime(PUBLIC_PATH . '/assets/js/main.js') : '1.0.1' ?>"></script>

<!-- Mobile Sidebar & Header Dropdown Interactive Controller -->
<script>
window.toggleSidebarMobile = function(e) {
    if (e && e.preventDefault) {
        e.preventDefault();
        e.stopPropagation();
    }
    const sidebar = document.querySelector('.sidebar') || document.getElementById('siswa-sidebar') || document.getElementById('admin-sidebar');
    const overlay = document.getElementById('sidebarBackdrop');
    if (sidebar) {
        sidebar.classList.toggle('show-mobile');
    }
    if (overlay) {
        overlay.classList.toggle('d-none');
    }
};

window.closeSidebarMobile = function(e) {
    if (e && e.preventDefault) {
        e.preventDefault();
        e.stopPropagation();
    }
    const sidebar = document.querySelector('.sidebar') || document.getElementById('siswa-sidebar') || document.getElementById('admin-sidebar');
    const overlay = document.getElementById('sidebarBackdrop');
    if (sidebar) {
        sidebar.classList.remove('show-mobile');
    }
    if (overlay) {
        overlay.classList.add('d-none');
    }
};

window.toggleUserDropdown = function(e) {
    if (e && e.preventDefault) {
        e.preventDefault();
        e.stopPropagation();
    }
    const btn = document.getElementById('dashboardUserDropdown');
    const menu = document.getElementById('dashboardUserDropdownMenu') || document.querySelector('#userMenuDropdownContainer .dropdown-menu');
    
    // Check if Bootstrap JS instance exists
    if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
        try {
            const bsDropdown = bootstrap.Dropdown.getOrCreateInstance(btn);
            if (bsDropdown) {
                bsDropdown.toggle();
                return;
            }
        } catch (err) {
            console.warn('Bootstrap dropdown error, falling back:', err);
        }
    }
    
    // Fallback toggle
    if (menu) {
        const isShown = menu.classList.contains('show');
        if (isShown) {
            menu.classList.remove('show');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        } else {
            menu.classList.add('show');
            if (btn) btn.setAttribute('aria-expanded', 'true');
        }
    }
};

// Document Click Outside Handler to close dropdown & sidebar
document.addEventListener('click', function(e) {
    const dropdownContainer = document.getElementById('userMenuDropdownContainer');
    const menu = document.getElementById('dashboardUserDropdownMenu') || (dropdownContainer ? dropdownContainer.querySelector('.dropdown-menu') : null);
    const btn = document.getElementById('dashboardUserDropdown');
    
    if (menu && menu.classList.contains('show')) {
        if (dropdownContainer && !dropdownContainer.contains(e.target)) {
            menu.classList.remove('show');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
    }
});

// Escape key closes menus
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const menu = document.getElementById('dashboardUserDropdownMenu');
        const btn = document.getElementById('dashboardUserDropdown');
        if (menu && menu.classList.contains('show')) {
            menu.classList.remove('show');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
        window.closeSidebarMobile();
    }
});

// Bind backdrop click to close sidebar on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('sidebarBackdrop');
    if (overlay) {
        overlay.removeEventListener('click', window.closeSidebarMobile);
        overlay.addEventListener('click', window.closeSidebarMobile);
    }
});
</script>
</body>
</html>
