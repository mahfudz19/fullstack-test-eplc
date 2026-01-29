<?php
// Stateless: Default to 'user' role so the menu renders.
// If user is not logged in, client-side script will redirect to login.
$role = 'user';
$current = currentUrl();
$navigation = require __DIR__ . '/navigation-config.php';
?>

<!-- Main Menu Section -->
<nav class="px-4 space-y-1">
  <?php
  // Helper function to check if menu is active
  $isMenuActive = function ($menuUrl, $currentPath) {
    if ($menuUrl === '#') return false;

    // Normalize paths
    $menuPath = parse_url($menuUrl, PHP_URL_PATH);
    $currentPath = parse_url($currentPath, PHP_URL_PATH);

    $menuPath = rtrim($menuPath, '/');
    $currentPath = rtrim($currentPath, '/');

    if (empty($menuPath)) $menuPath = '/';
    if (empty($currentPath)) $currentPath = '/';

    // Exact match
    if ($menuPath === $currentPath) return true;

    // Sub-path match (e.g., /mahasiswa/detail matches /mahasiswa)
    // But avoid matching '/' with everything
    if ($menuPath !== '/' && strpos($currentPath, $menuPath) === 0) {
      // Ensure it's a true sub-path (followed by / or end of string)
      $nextChar = substr($currentPath, strlen($menuPath), 1);
      if ($nextChar === false || $nextChar === '/') return true;
    }

    return false;
  };
  ?>

  <?php foreach ($navigation as $section) : ?>
    <?php
    // Filter section by role
    if (!in_array($role, $section['roles'])) continue;
    ?>

    <h3 class="sidebar-label mt-4 mb-2"><?= $section['section'] ?></h3>

    <?php foreach ($section['menus'] as $menu) : ?>
      <?php
      // Filter menu by role
      if (isset($menu['roles']) && !in_array($role, $menu['roles'])) continue;

      $hasSubmenu = isset($menu['submenu']) && !empty($menu['submenu']);
      $isActive = false;

      if (!$hasSubmenu) {
        $menuUrl = getBaseUrl($menu['url']) ?: '/';
        $isActive = $isMenuActive($menuUrl, $current);
      } else {
        // Check if any submenu is active
        foreach ($menu['submenu'] as $sub) {
          $subUrl = getBaseUrl($sub['url']) ?: '/';
          if ($isMenuActive($subUrl, $current)) {
            $isActive = true;
            break;
          }
        }
      }
      ?>

      <?php if (!$hasSubmenu) : ?>
        <a data-spa href="<?= $menuUrl ?>" data-ripple="primary" class="menu-item <?= $isActive ? 'active' : '' ?> <?= isset($menu['disabled']) && $menu['disabled'] ? 'opacity-50 pointer-events-none' : '' ?>">
          <i class="bi <?= $menu['icon'] ?> menu-icon"></i>
          <span class="menu-text flex-1"><?= $menu['title'] ?></span>
          <?php if (isset($menu['badge'])) : ?>
            <span class="px-1.5 py-0.5 rounded text-xs font-bold bg-primary-100 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400"><?= $menu['badge'] ?></span>
          <?php endif; ?>
        </a>
      <?php else : ?>
        <?php
        // Sanitize title for ID: lowercase, replace non-alphanumeric with single dash, trim dashes
        $safeTitle = trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $menu['title'])), '-');
        $menuId = 'menu-' . $safeTitle;
        ?>
        <a data-spa href="#<?= $menuId ?>"
          data-toggle="collapse"
          data-ripple="primary"
          aria-expanded="<?= $isActive ? 'true' : 'false' ?>"
          class="menu-item <?= $isActive ? 'active' : '' ?>">
          <i class="bi <?= $menu['icon'] ?> menu-icon"></i>
          <span class="menu-text flex-1"><?= $menu['title'] ?></span>
          <i class="bi bi-chevron-down submenu-arrow transition-transform text-xs <?= $isActive ? 'rotate-180' : '' ?>"></i>
        </a>

        <div id="<?= $menuId ?>" class="submenu collapse-menu <?= $isActive ? 'show' : '' ?>">
          <div class="submenu-inner">
            <?php foreach ($menu['submenu'] as $sub) : ?>
              <?php
              $subUrl = getBaseUrl($sub['url']) ?: '/';
              $isSubActive = ($current === $subUrl || $current === $subUrl . '/');
              ?>
              <a data-spa href="<?= $subUrl ?>" data-ripple="primary" class="submenu-item <?= $isSubActive ? 'active' : '' ?>">
                <div class="submenu-dot"></div>
                <span class="menu-text"><?= $sub['title'] ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endforeach; ?>

  <?php if ($role !== 'guest') : ?>
    <a data-spa data-spa-method="POST" href="<?php echo getBaseUrl('/logout'); ?>" onclick="localStorage.removeItem('token'); localStorage.removeItem('user'); if(window.spa && window.spa.clearCache) window.spa.clearCache()" class="menu-item">
      <i class="bi bi-box-arrow-right menu-icon"></i>
      <span class="menu-text">Logout</span>
    </a>
  <?php else: ?>
  <?php endif; ?>
</nav>

<script>
  (function() {
    // 1. Cache elements & collections
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar) return;

    const nav = sidebar.querySelector('nav');
    const submenus = sidebar.querySelectorAll('.submenu');

    /**
     * Update active state secara efisien
     */
    function updateSidebarActiveState() {
      const currentPath = window.location.pathname;
      const links = sidebar.querySelectorAll('a[href]:not([href^="#"])');

      // Helper untuk normalisasi path (remove trailing slash & handle root)
      const normalize = (path) => {
        let p = path.split('?')[0].split('#')[0].replace(/\/$/, '');
        return p === '' ? '/' : p;
      };

      const normalizedCurrent = normalize(currentPath);

      // Reset state yang ada
      sidebar.querySelectorAll('.active').forEach(el => el.classList.remove('active'));
      sidebar.querySelectorAll('[aria-expanded="true"]').forEach(el => el.setAttribute('aria-expanded', 'false'));
      sidebar.querySelectorAll('.rotate-180').forEach(el => el.classList.remove('rotate-180'));
      sidebar.querySelectorAll('.submenu.show').forEach(el => el.classList.remove('show'));

      let activeLink = null;
      let maxMatchScore = -1;

      // Cari link dengan kecocokan terbaik
      for (const link of links) {
        try {
          const linkPath = normalize(new URL(link.href).pathname);

          if (normalizedCurrent === linkPath) {
            // Exact match - prioritas tertinggi
            activeLink = link;
            maxMatchScore = 1000;
            break;
          } else if (linkPath !== '/' && normalizedCurrent.startsWith(linkPath + '/')) {
            // Sub-path match - prioritas berdasarkan panjang path
            if (linkPath.length > maxMatchScore) {
              activeLink = link;
              maxMatchScore = linkPath.length;
            }
          }
        } catch (e) {}
      }

      if (activeLink) {
        activeLink.classList.add('active');

        // Buka parent submenu jika ada
        let parentSub = activeLink.closest('.submenu');
        while (parentSub) {
          parentSub.classList.add('show');
          const toggle = sidebar.querySelector(`a[href="#${parentSub.id}"]`);
          if (toggle) {
            toggle.classList.add('active');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.querySelector('.submenu-arrow')?.classList.add('rotate-180');
          }
          parentSub = parentSub.parentElement.closest('.submenu');
        }
      }
    }

    /**
     * Handler tunggal untuk navigasi
     */
    function handleNavigation() {
      updateSidebarActiveState();

      // Auto-close pada mobile/tablet
      if (window.innerWidth <= 1023 && (sidebar.classList.contains('open') || sidebar.classList.contains('active'))) {
        sidebar.classList.remove('open', 'active');
        overlay?.classList.remove('open', 'active');
        document.body.style.overflow = '';
      }
    }

    /**
     * Inisialisasi Sidebar
     */
    function initSidebar() {
      // Atur initial tabindex: hanya menu aktif atau pertama yang bisa di-tab
      const allItems = Array.from(nav.querySelectorAll('.menu-item, .submenu-item'));
      const activeItem = allItems.find(el => el.classList.contains('active')) || allItems[0];

      allItems.forEach(item => {
        item.setAttribute('tabindex', item === activeItem ? '0' : '-1');
      });

      // Keyboard Navigation (Arrow Keys + Tab Management)
      nav.addEventListener('keydown', (e) => {
        const focused = document.activeElement;
        if (!focused || (!focused.classList.contains('menu-item') && !focused.classList.contains('submenu-item'))) return;

        const visibleItems = allItems.filter(item => {
          const submenu = item.closest('.submenu');
          return !submenu || submenu.classList.contains('show');
        });

        const index = visibleItems.indexOf(focused);

        // Helper untuk update tabindex (Roving Tabindex)
        const updateFocus = (nextItem) => {
          if (!nextItem) return;
          allItems.forEach(item => item.setAttribute('tabindex', '-1'));
          nextItem.setAttribute('tabindex', '0');
          nextItem.focus();
        };

        switch (e.key) {
          case 'ArrowDown':
            e.preventDefault();
            updateFocus(visibleItems[index + 1] || visibleItems[0]);
            break;
          case 'ArrowUp':
            e.preventDefault();
            updateFocus(visibleItems[index - 1] || visibleItems[visibleItems.length - 1]);
            break;
          case 'ArrowRight':
            if (focused.hasAttribute('data-toggle') && focused.getAttribute('aria-expanded') === 'false') {
              e.preventDefault();
              focused.click();
            }
            break;
          case 'ArrowLeft':
            if (focused.hasAttribute('data-toggle') && focused.getAttribute('aria-expanded') === 'true') {
              e.preventDefault();
              focused.click();
            } else if (focused.classList.contains('submenu-item')) {
              e.preventDefault();
              const parentToggle = focused.closest('.submenu').previousElementSibling;
              updateFocus(parentToggle);
            }
            break;
        }
      });

      // Event Delegation: Satu listener untuk semua interaksi klik di nav
      nav.addEventListener('click', (e) => {
        const item = e.target.closest('.menu-item, .submenu-item');
        if (item) {
          allItems.forEach(el => el.setAttribute('tabindex', '-1'));
          item.setAttribute('tabindex', '0');
        }

        const toggle = e.target.closest('[data-toggle="collapse"]');

        if (toggle) {
          e.preventDefault();
          const targetId = toggle.getAttribute('href');
          const target = sidebar.querySelector(targetId);
          if (!target) return;

          const isOpening = !target.classList.contains('show');

          // Accordion effect: Tutup submenu lain yang sedang terbuka
          submenus.forEach(menu => {
            if (menu !== target && menu.classList.contains('show')) {
              menu.classList.remove('show');
              const otherToggle = sidebar.querySelector(`a[href="#${menu.id}"]`);
              if (otherToggle) {
                otherToggle.setAttribute('aria-expanded', 'false');
                otherToggle.querySelector('.submenu-arrow')?.classList.remove('rotate-180');
              }
            }
          });

          // Toggle submenu ini
          target.classList.toggle('show', isOpening);
          toggle.setAttribute('aria-expanded', isOpening);
          toggle.querySelector('.submenu-arrow')?.classList.toggle('rotate-180', isOpening);
          return;
        }

        // Jika klik link biasa di mobile, trigger penutupan
        if (window.innerWidth <= 1023 && e.target.closest('a[href]')) {
          setTimeout(handleNavigation, 50);
        }
      });

      updateSidebarActiveState();
    }

    // Jalankan inisialisasi
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
      initSidebar();
    }

    window.addEventListener('spa:navigated', handleNavigation);
    window.addEventListener('popstate', handleNavigation);
  })();
</script>