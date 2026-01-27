<?php
// Stateless: Default to 'user' role for rendering structure.
// Actual access control is handled by API token verification.
$role = 'user';
$navigationConfig = require __DIR__ . '/sidebar/navigation-config.php';

$navForClient = [];

foreach ($navigationConfig as $section) {
  if (!in_array($role, $section['roles'])) continue;

  $sectionData = [
    'section' => $section['section'],
    'menus' => [],
  ];

  foreach ($section['menus'] as $menu) {
    if (isset($menu['roles']) && !in_array($role, $menu['roles'])) continue;

    if (isset($menu['submenu']) && !empty($menu['submenu'])) {
      $menuData = [
        'type' => 'group',
        'title' => $menu['title'],
        'icon' => $menu['icon'],
        'description' => $menu['description'] ?? '',
        'badge' => $menu['badge'] ?? null,
        'disabled' => $menu['disabled'] ?? false,
        'submenu' => [],
      ];

      foreach ($menu['submenu'] as $sub) {
        $menuData['submenu'][] = [
          'title' => $sub['title'],
          'url' => getBaseUrl($sub['url']),
          'description' => $sub['description'] ?? '',
          'badge' => $sub['badge'] ?? null,
          'disabled' => $sub['disabled'] ?? false,
        ];
      }
    } else {
      $menuData = [
        'type' => 'item',
        'title' => $menu['title'],
        'icon' => $menu['icon'],
        'url' => getBaseUrl($menu['url']),
        'description' => $menu['description'] ?? '',
        'badge' => $menu['badge'] ?? null,
        'disabled' => $menu['disabled'] ?? false,
      ];
    }

    $sectionData['menus'][] = $menuData;
  }

  if (!empty($sectionData['menus'])) {
    $navForClient[] = $sectionData;
  }
}
?>
<div class="flex" style="display: flex;">
  <!-- Sidebar Overlay untuk Mobile -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <aside class="sidebar" id="sidebar">
    <!-- Sidebar Header -->
    <div style="height: 64px; display: flex; align-items: center; padding: 0 1.5rem; border-bottom: 1px solid var(--color-border-light);">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <!-- Logo placeholder -->
        <div style="width: 32px; height: 32px; background: var(--color-primary-100); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--color-primary-600);">
          <i class="bi bi-app-indicator"></i>
        </div>
        <div class="sidebar-label" style="margin: 0; font-size: 1rem; color: var(--color-primary-900);">
          APP
        </div>
      </div>
    </div>

    <!-- Sidebar Content -->
    <div style="flex: 1; overflow-y: auto; padding: 1rem 0;">
      <?php require __DIR__ . '/sidebar/index.php'; ?>
    </div>
  </aside>

  <!-- Main Content Wrapper -->
  <div class="main-wrapper flex-1">
    <header style="position: fixed; top: 0; left: var(--sidebar-offset, 256px); right: 0; height: 64px; background: rgba(255,255,255,0.8); backdrop-filter: blur(10px); border-bottom: 1px solid #e2e8f0; z-index: 40; display: flex; align-items: center; padding: 0 1.5rem; transition: left 0.3s ease;">
      <div style="width: 100%; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 1rem;">
          <button id="sidebarToggle" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b; display: flex; align-items: center; padding: 0.5rem; border-radius: 0.5rem; transition: background-color 0.2s;">
            <i class="bi bi-list"></i>
          </button>
          <h2 style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 0;">Task Manager</h2>
        </div>
        <div style="display: flex; gap: 1rem; align-items: center;">
          <span id="headerUserName" style="font-size: 0.875rem; color: #64748b;">Hi, Guest</span>
          <a data-spa href="<?= getBaseUrl('/logout') ?>"
            onclick="localStorage.removeItem('token'); localStorage.removeItem('user'); if(window.spa) window.spa.clearCache();"
            style="font-size: 0.875rem; color: #ef4444; text-decoration: none; font-weight: 500; cursor: pointer;">
            Logout
          </a>
        </div>
      </div>
    </header>

    <?php
    $layoutId = "(app)/layout/index.php";
    ?>
    <!-- Added top padding to account for fixed header -->
    <main class="container" style="margin-top: 64px; padding: 1.5rem; min-height: calc(100vh - 64px - 60px);" data-layout="<?= $layoutId ?>">
      <?= $children; ?>
    </main>

    <script>
      (function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggle = document.getElementById('sidebarToggle');
        const body = document.body;

        function toggleSidebar() {
          const isMobile = window.innerWidth < 1024;
          if (isMobile) {
            body.classList.toggle('sidebar-mobile-open');
          } else {
            body.classList.toggle('sidebar-collapsed');

            // Update header and main transition using offset
            const isCollapsed = body.classList.contains('sidebar-collapsed');
            document.documentElement.style.setProperty('--sidebar-offset', isCollapsed ? '0px' : '256px');
          }
        }

        if (toggle) toggle.addEventListener('click', toggleSidebar);
        if (overlay) overlay.addEventListener('click', toggleSidebar);

        // Close sidebar on mobile when navigating (SPA)
        window.addEventListener('spa:navigated', () => {
          if (window.innerWidth < 1024) {
            body.classList.remove('sidebar-mobile-open');
          }
          updateHeaderUser();
        });

        // Initial check
        updateHeaderUser();

        function updateHeaderUser() {
          const userStr = localStorage.getItem('user');
          const el = document.getElementById('headerUserName');
          if (userStr && el) {
            try {
              const user = JSON.parse(userStr);
              el.textContent = 'Hi, ' + (user.name || user.email || 'User');
            } catch (e) {
              // ignore error
            }
          }
        }
      })();
    </script>


    <footer style="padding: 1rem; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 0.75rem;">
      &copy; <?= date('Y') ?> <?= env('APP_NAME') ?>. All rights reserved.
    </footer>
  </div>
</div>

<style>
  @media (max-width: 1023px) {
    .lg\:hidden {
      display: flex !important;
    }

    header {
      left: 0 !important;
    }
  }

  @media (min-width: 1024px) {
    .lg\:hidden {
      display: none !important;
    }
  }
</style>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<script>
  window.NAV_DATA = <?= json_encode($navForClient, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  (function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainWrapper = document.querySelector('.main-wrapper');

    if (!sidebar || !sidebarOverlay || !mainWrapper) return;

    const iconNormal = `
      <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
        <g fill="none" stroke="currentColor" stroke-dasharray="24" stroke-dashoffset="24" stroke-linecap="round" stroke-width="2">
          <path d="M5 5H19">
            <animate fill="freeze" attributeName="stroke-dashoffset" dur="0.2s" values="24;0" />
          </path>
          <path d="M5 12H19">
            <animate fill="freeze" attributeName="stroke-dashoffset" begin="0.2s" dur="0.2s" values="24;0" />
          </path>
          <path d="M5 19H19">
            <animate fill="freeze" attributeName="stroke-dashoffset" begin="0.4s" dur="0.2s" values="24;0" />
          </path>
        </g>
      </svg>
    `;

    const iconFold = `
      <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
        <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2">
          <path stroke-dasharray="10" stroke-dashoffset="10" d="M7 9L4 12L7 15">
            <animate fill="freeze" attributeName="stroke-dashoffset" begin="0.6s" dur="0.2s" values="10;0" />
          </path>
          <path stroke-dasharray="16" stroke-dashoffset="16" d="M19 5H5">
            <animate fill="freeze" attributeName="stroke-dashoffset" dur="0.2s" values="16;0" />
          </path>
          <path stroke-dasharray="12" stroke-dashoffset="12" d="M19 12H10">
            <animate fill="freeze" attributeName="stroke-dashoffset" begin="0.2s" dur="0.2s" values="12;0" />
          </path>
          <path stroke-dasharray="16" stroke-dashoffset="16" d="M19 19H5">
            <animate fill="freeze" attributeName="stroke-dashoffset" begin="0.4s" dur="0.2s" values="16;0" />
          </path>
        </g>
      </svg>
    `;

    const savedMinimized = localStorage.getItem('sidebarMinimized') === 'true';
    if (savedMinimized) {
      sidebar.classList.add('minimized');
      mainWrapper.classList.add('sidebar-minimized');
      document.documentElement.style.setProperty('--sidebar-width', '80px');
      const minimizeBtn = document.querySelector('[data-action="minimize"]');
      if (minimizeBtn) {
        const iconWrapper = minimizeBtn.querySelector('.icon-wrapper') || minimizeBtn;
        iconWrapper.innerHTML = iconFold;
      }
    } else {
      document.documentElement.style.setProperty('--sidebar-width', '256px');
    }

    sidebarOverlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      sidebarOverlay.classList.remove('open');
    });

    // Gunakan event delegation pada document tapi pastikan hanya satu kali (atau cek target)
    // Untuk layout yang sering di-replace, lebih baik attach ke elemen wrapper layoutnya
    const layoutWrapper = document.getElementById('app-layout-wrapper'); // Pastikan ID ini ada di HTML
    const targetForEvents = layoutWrapper || document;

    targetForEvents.addEventListener('click', (e) => {
      const button = e.target.closest('[data-action]');
      if (!button) return;

      const action = button.getAttribute('data-action');

      switch (action) {
        case 'toggle':
          sidebar.classList.toggle('open');
          sidebarOverlay.classList.toggle('open');
          break;
        case 'close':
          sidebar.classList.remove('open');
          sidebarOverlay.classList.remove('open');
          break;
        case 'minimize':
          toggleMinimize();
          break;
      }
    });

    /**
     * Helper untuk toggle minimize sidebar
     */
    function toggleMinimize() {
      sidebar.classList.toggle('minimized');
      mainWrapper.classList.toggle('sidebar-minimized');

      const isMinimized = sidebar.classList.contains('minimized');
      const sidebarWidth = isMinimized ? '80px' : '256px';
      document.documentElement.style.setProperty('--sidebar-width', sidebarWidth);

      localStorage.setItem('sidebarMinimized', isMinimized);

      const minimizeBtn = document.querySelector('[data-action="minimize"]');
      if (minimizeBtn) {
        const iconWrapper = minimizeBtn.querySelector('.icon-wrapper') || minimizeBtn;
        iconWrapper.innerHTML = isMinimized ? iconFold : iconNormal;
      }
    }

    /**
     * Keyboard Shortcuts Handler (Mac Friendly)
     */
    window.addEventListener('keydown', (e) => {
      // Cmd + B (Mac) atau Ctrl + B (Lainnya) untuk Toggle Sidebar
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'b') {
        e.preventDefault();
        if (window.innerWidth <= 1023) {
          sidebar.classList.toggle('open');
          sidebarOverlay.classList.toggle('open');

          // Auto focus ke sidebar jika dibuka
          if (sidebar.classList.contains('open')) {
            setTimeout(() => {
              const activeOrFirst = sidebar.querySelector('.menu-item.active') || sidebar.querySelector('.menu-item');
              activeOrFirst?.focus();
            }, 300); // Tunggu animasi transisi
          }
        } else {
          toggleMinimize();
          // Tetap fokus ke sidebar saat minimize/expand untuk kenyamanan keyboard
          const activeOrFirst = sidebar.querySelector('.menu-item.active') || sidebar.querySelector('.menu-item');
          activeOrFirst?.focus();
        }
      }

      // Escape untuk menutup mobile sidebar
      if (e.key === 'Escape' && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('open');
      }

      // Option + N (Mac) atau Alt + N untuk Fokus ke Sidebar
      if (e.altKey && e.key.toLowerCase() === 'n') {
        e.preventDefault();
        const firstMenuItem = sidebar.querySelector('.menu-item');
        if (firstMenuItem) firstMenuItem.focus();
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 1023) {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('open');
      }
    });
  })();
</script>