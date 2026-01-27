/**
 * Simple SPA Router for Hybrid Rendering
 */

document.addEventListener("DOMContentLoaded", () => {
  initSpaNavigation();
});

function initSpaNavigation() {
  if ("scrollRestoration" in history) {
    history.scrollRestoration = "manual";
  }

  document.body.addEventListener("click", (e) => {
    // Cari elemen anchor terdekat
    const link = e.target.closest("a");

    // Hanya proses link yang secara eksplisit meminta SPA navigation
    if (!link || !link.hasAttribute("data-spa")) {
      return;
    }

    // Validasi tambahan (tetap diperlukan untuk keamanan)
    const href = link.getAttribute("href");
    if (
      !href ||
      href.trim() === "" ||
      href.startsWith("#") ||
      href.startsWith("javascript:") ||
      link.target === "_blank" ||
      link.hasAttribute("download")
    ) {
      return;
    }

    // Cegah navigasi default
    e.preventDefault();

    const url = link.href;

    // Default scroll is true unless explicitly disabled
    let shouldScroll = true;
    if (
      link.hasAttribute("data-no-scroll") ||
      link.getAttribute("data-scroll") === "false"
    ) {
      shouldScroll = false;
    }

    const options = {
      scroll: shouldScroll,
    };
    navigateTo(url, options);
  });

  // Handle Back/Forward browser buttons
  window.addEventListener("popstate", (e) => {
    if (e.state && e.state.url) {
      // Ambil posisi scroll yang tersimpan (jika ada)
      const restoreScrollPos = e.state.scrollPosition || null;

      // Teruskan options yang tersimpan + instruksi restore scroll
      loadContent(e.state.url, {
        ...e.state.options,
        pushState: false,
        restoreScroll: restoreScrollPos,
      });
    } else {
      // Fallback reload jika state kosong (misal initial load)
      window.location.reload();
    }
  });
}

async function navigateTo(url, options = {}) {
  saveCurrentScrollPosition();

  // Update URL di browser dulu (optimistic)
  if (options.replace) {
    history.replaceState({ url: url, options: options }, "", url);
  } else {
    history.pushState({ url: url, options: options }, "", url);
  }

  await loadContent(url, { ...options, pushState: true });
}

// --- CACHE SYSTEM (SWR Support) ---
const spaCache = new Map();
// Kita tidak butuh TTL ketat karena kita akan selalu revalidate di background
// Tapi kita bisa hapus cache yang sudah terlalu lama (misal 5 menit) untuk hemat memori
const CACHE_TTL = 5 * 60 * 1000;

function clearSpaCache() {
  spaCache.clear();
}

const spaConfig = {
  requestInterceptors: [],
};

// Expose API untuk mendaftarkan interceptor
// Contoh usage: window.spa.onRequest((headers, url) => { headers['Authorization'] = '...' })
function registerRequestInterceptor(callback) {
  if (typeof callback === "function") {
    spaConfig.requestInterceptors.push(callback);
  }
}

async function loadContent(url, options = {}) {
  const {
    pushState = true,
    scroll = true,
    ignoreCache = false,
  } = typeof options === "boolean" ? { pushState: options } : options;
  startProgressBar();

  // Cari container layout terdalam yang aktif saat ini
  const targetContainer = getDeepestLayoutContainer();
  const loadingContainer =
    targetContainer || document.getElementById("app-content");

  // 1. STRATEGI SWR: Cek Cache & Tampilkan Dulu (Stale)
  const cached = spaCache.get(url);
  const now = Date.now();
  let isServedFromCache = false;

  if (!ignoreCache && cached && now - cached.timestamp < CACHE_TTL) {
    // VALIDASI PENTING: Cek apakah container target layout benar-benar ada di DOM saat ini?
    // Jika kita punya cache untuk "settings/layout", tapi saat ini kita di "dashboard" (yang tidak punya container settings),
    // maka cache ini TIDAK BOLEH dipakai. Kita harus fetch ulang agar server mengirim layout pembungkusnya.
    const targetExists =
      cached.data.meta && cached.data.meta.layout
        ? document.querySelector(`[data-layout="${cached.data.meta.layout}"]`)
        : true; // Jika tidak ada meta layout, asumsikan aman (atau root)

    if (targetExists) {
      renderSpaResponse(cached.data, url, options);
      isServedFromCache = true;
      // Jangan return! Kita lanjut ke bawah untuk Revalidate (fetch server)
    } else {
    }
  }

  // Jika belum ada cache, tampilkan loading spinner
  if (!isServedFromCache && loadingContainer) {
    loadingContainer.style.opacity = "0.5";
    loadingContainer.style.pointerEvents = "none";
  }

  // Persiapan Request
  const targetLayout = targetContainer
    ? targetContainer.getAttribute("data-layout")
    : "layout.php";

  const activeLayouts = [];
  // Ambil semua layout dari document root ke dalam (atau sebaliknya)
  // QuerySelectorAll mengembalikan urutan dokumen (parent dulu, baru child)
  document.querySelectorAll("[data-layout]").forEach((el) => {
    activeLayouts.push(el.getAttribute("data-layout"));
  });

  // Siapkan headers
  const headers = {
    "X-SPA-REQUEST": "true",
    "X-SPA-TARGET-LAYOUT": targetLayout,
    "X-SPA-LAYOUTS": JSON.stringify(activeLayouts),
    // Browser otomatis menambahkan If-None-Match jika ada di cache HTTP browser
  };

  // [INTERCEPTOR ENGINE]
  // Jalankan semua registered interceptors untuk memodifikasi headers secara dinamis
  // Ini membuat engine flexible: Support Token, API Key, Custom Headers, dll.
  spaConfig.requestInterceptors.forEach((interceptor) => {
    try {
      interceptor(headers, url);
    } catch (e) {
      console.error("SPA Interceptor Error:", e);
    }
  });

  try {
    // 2. REVALIDATE: Fetch ke server (Background jika cache ada)
    const response = await fetch(url, {
      headers: headers,
    });

    // 3. HANDLE RESPONSE
    if (response.status === 304) {
      // Data di cache (dan di layar) sudah benar. Tidak perlu update apa-apa.
      // Update timestamp cache agar tidak expire
      if (cached) cached.timestamp = Date.now();
      return;
    }

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const contentType = response.headers.get("content-type");
    if (contentType && contentType.includes("application/json")) {
      const data = await response.json();

      if (data.redirect) {
        if (data.new_tab) {
          window.open(data.redirect, "_blank");
          return; // Stay on current page
        }

        if (data.force_reload) {
          window.location.href = data.redirect;
        } else {
          // Gunakan replace: true agar history tidak bertumpuk aneh (menggantikan URL lama)
          navigateTo(data.redirect, { replace: true });
        }
        return;
      }

      // Update Cache dengan data baru
      spaCache.set(url, {
        data: data,
        timestamp: Date.now(),
      });

      // Render data baru ke layar (User akan melihat update jika konten berubah)
      renderSpaResponse(data, url, options);
    } else {
      // Fallback jika bukan JSON (Full page reload)
      window.location.href = url;
    }
  } catch (error) {
    console.error("SPA Navigation Error:", error);
    // Jika error dan kita tidak punya cache, baru redirect/reload
    if (!isServedFromCache) {
      window.location.href = url;
    }
  } finally {
    // Bersihkan loading state
    if (loadingContainer) {
      loadingContainer.style.opacity = "1";
      loadingContainer.style.pointerEvents = "auto";
    }
    finishProgressBar();
  }
}

// --- Progress Bar Control ---
function startProgressBar() {
  const bar = document.getElementById("global-progress-bar");
  const inner = document.getElementById("global-progress-bar-inner");
  if (!bar || !inner) return;

  // Reset
  if (window.spaProgressInterval) clearInterval(window.spaProgressInterval);

  bar.style.opacity = 1;
  inner.style.width = "0%";
  inner.style.transition = "width 0.2s ease-out";

  let width = 0;
  window.spaProgressInterval = setInterval(() => {
    if (width < 90) {
      // Logarithmic increment: slower as it gets higher
      const remaining = 90 - width;
      const increment = Math.max(0.5, remaining / 20);
      width += increment;
      inner.style.width = `${width}%`;
    }
  }, 100);
}

function finishProgressBar() {
  const bar = document.getElementById("global-progress-bar");
  const inner = document.getElementById("global-progress-bar-inner");
  if (!bar || !inner) return;

  if (window.spaProgressInterval) clearInterval(window.spaProgressInterval);

  inner.style.width = "100%";

  setTimeout(() => {
    bar.style.opacity = 0;
    setTimeout(() => {
      inner.style.width = "0%";
    }, 300);
  }, 500);
}

function renderSpaResponse(data, url, options = {}) {
  // Update Title
  if (data.meta && data.meta.title) {
    document.title = data.meta.title;
  }

  // Update CSRF Token
  if (data.meta && data.meta.csrf_token) {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) {
      csrfMeta.setAttribute("content", data.meta.csrf_token);
    }
  }

  // Inject New Styles
  if (data.meta && data.meta.styles) {
    handleNewStyles(data.meta.styles);
  }

  // Logika Smart Layout Replacement
  let containerToUpdate = null;

  if (data.meta && data.meta.layout) {
    // Support Array of Layouts (Nested) atau String tunggal
    const potentialLayouts = Array.isArray(data.meta.layout)
      ? data.meta.layout
      : [data.meta.layout];

    // Cari container terdalam yang COCOK dengan yang ada di DOM
    // Kita iterasi array layout yang dikirim server.
    // Server mengirim urutan: [Layout Anak, Layout Induk, ...]
    // Kita cari yang PERTAMA kali ketemu di DOM.
    for (const layoutId of potentialLayouts) {
      const el = document.querySelector(`[data-layout="${layoutId}"]`);
      if (el) {
        containerToUpdate = el;
        break; // Ketemu yang paling spesifik yang kita punya!
      }
    }

    // Debugging jika tidak ketemu
    if (!containerToUpdate) {
    }
  }

  if (containerToUpdate) {
    const activeElement = document.activeElement;
    const isFocusInside = containerToUpdate.contains(activeElement);

    // Dispatch event BEFORE replacing content to allow cleanup
    window.dispatchEvent(new Event("spa:before-navigate"));

    containerToUpdate.innerHTML = data.html;
    executeScripts(containerToUpdate);
    handleAfterNavigation(options);

    if (isFocusInside) {
      containerToUpdate.setAttribute("tabindex", "-1");
      containerToUpdate.focus({ preventScroll: true });
      // Remove tabindex after focus to keep DOM clean, but keep it if you want it focusable
      containerToUpdate.style.outline = "none";
    }

    window.dispatchEvent(new Event("spa:navigated"));
  } else {
    // Fallback: Jika kontainer layout spesifik tidak ditemukan,
    // coba update app-content (root container) secara langsung
    const rootContainer = document.getElementById("app-content");
    if (rootContainer) {
      const activeElement = document.activeElement;
      const isFocusInside = rootContainer.contains(activeElement);

      window.dispatchEvent(new Event("spa:before-navigate"));
      rootContainer.innerHTML = data.html;
      executeScripts(rootContainer);
      handleAfterNavigation(options);

      if (isFocusInside) {
        rootContainer.setAttribute("tabindex", "-1");
        rootContainer.focus({ preventScroll: true });
        rootContainer.style.outline = "none";
      }

      window.dispatchEvent(new Event("spa:navigated"));
    } else {
      window.location.href = url;
    }
  }
}

/**
 * Helper: Simpan posisi scroll saat ini ke History State
 */
function saveCurrentScrollPosition() {
  const mainEl = document.querySelector("main");
  let scrollY = window.scrollY;
  let scrollX = window.scrollX;

  // Cek apakah scroll ada di container khusus (bukan window)
  if (mainEl) {
    const scrollParent = getScrollParent(mainEl);
    if (scrollParent && scrollParent !== window) {
      scrollY = scrollParent.scrollTop;
      scrollX = scrollParent.scrollLeft;
    }
  }

  // Update state saat ini dengan posisi scroll
  const currentState = history.state || {};
  history.replaceState(
    { ...currentState, scrollPosition: { x: scrollX, y: scrollY } },
    "",
  );
}

// Helper untuk menangani CSS baru
function handleNewStyles(styles) {
  if (!Array.isArray(styles)) return;

  const head = document.head;
  const existingLinks = Array.from(
    head.querySelectorAll('link[rel="stylesheet"]'),
  ).map((link) => link.getAttribute("href"));

  styles.forEach((stylePath) => {
    // Encode path agar aman di URL
    const encodedPath = encodeURIComponent(stylePath);

    // Construct URL pattern yang kita cari
    const searchPattern = `build/assets/${stylePath}`;

    // Cek keberadaan
    const exists = existingLinks.some((href) => href.includes(searchPattern));

    if (!exists) {
      const link = document.createElement("link");
      link.rel = "stylesheet";

      // Deteksi Base URL
      const globalCssLink = document.querySelector(
        'link[href*="css/global.css"]',
      );
      let baseUrl = "";
      if (globalCssLink) {
        baseUrl = globalCssLink.href.split("css/global.css")[0];
      }

      link.href = `${baseUrl}build/assets/${stylePath}`;
      head.appendChild(link);
    }
  });
}

// Expose global API
window.navigateTo = navigateTo;
window.spa = {
  push: navigateTo,
  refresh: () =>
    loadContent(window.location.href, { pushState: false, ignoreCache: true }),
  back: () => window.history.back(),
  clearCache: clearSpaCache,
  onRequest: registerRequestInterceptor, // API Baru yang Profesional
};

function getDeepestLayoutContainer() {
  const containers = document.querySelectorAll("[data-layout]");
  if (containers.length === 0) return null;

  let deepest = containers[0];
  let maxDepth = 0;

  containers.forEach((el) => {
    let depth = 0;
    let parent = el.parentNode;
    while (parent) {
      depth++;
      parent = parent.parentNode;
    }
    if (depth > maxDepth) {
      maxDepth = depth;
      deepest = el;
    }
  });

  return deepest;
}

// Helper untuk mengeksekusi script tag yang baru dimuat
function executeScripts(element) {
  const scripts = element.querySelectorAll("script");
  scripts.forEach((oldScript) => {
    const newScript = document.createElement("script");
    Array.from(oldScript.attributes).forEach((attr) =>
      newScript.setAttribute(attr.name, attr.value),
    );
    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
    oldScript.parentNode.replaceChild(newScript, oldScript);
  });
}

/**
 * Logika pasca-navigasi (Scroll ke atas & Cleanup UI)
 */
function handleAfterNavigation(options = {}) {
  // 1. Cek apakah ada permintaan Restore Scroll (dari tombol Back/Forward)
  if (options.restoreScroll) {
    const { x, y } = options.restoreScroll;
    const scrollOptions = { top: y, left: x, behavior: "auto" }; // Instant jump

    // Coba restore ke Window
    window.scrollTo(scrollOptions);

    // Coba restore ke Container (jika ada)
    const mainEl = document.querySelector("main");
    if (mainEl) {
      mainEl.scrollTo(scrollOptions);
      const scrollParent = getScrollParent(mainEl);
      if (scrollParent && scrollParent !== window) {
        scrollParent.scrollTo(scrollOptions);
      }
    }
    return; // Selesai, jangan scroll to top
  }

  // 2. Scroll ke atas (Default: true, untuk navigasi baru)
  // Bisa dimatikan via data-scroll="false"
  if (options.scroll !== false) {
    const scrollOptions = { top: 0, left: 0 };
    window.scrollTo(scrollOptions);

    // Dynamic Scroll Reset: Cari container scrolling terdekat dari <main>
    const mainEl = document.querySelector("main");
    if (mainEl) {
      // 1. Cek main itu sendiri (jika dia yang scroll)
      mainEl.scrollTo(scrollOptions);

      // 2. Cek parent-nya (untuk layout dengan wrapper overflow seperti di Admin Panel)
      const scrollParent = getScrollParent(mainEl);
      if (scrollParent && scrollParent !== window) {
        scrollParent.scrollTo(scrollOptions);
      }
    }
  }

  // 3. Tutup sidebar di mobile (jika ada)
  if (window.innerWidth < 1024) {
    const sidebar = document.getElementById("sidebar");
    const sidebarOverlay = document.getElementById("sidebarOverlay");
    if (sidebar) sidebar.classList.remove("open");
    if (sidebarOverlay) sidebarOverlay.classList.remove("open");
  }
}

/**
 * Helper: Find the nearest scrolling ancestor
 * Mencari elemen parent terdekat yang memiliki properti scroll
 */
function getScrollParent(node) {
  if (!node || node === document.body || node === document.documentElement) {
    return window;
  }

  // Cek computed style untuk overflow
  const style = window.getComputedStyle(node);
  const overflowY = style.overflowY;
  const isScrollable = ["auto", "scroll", "overlay"].includes(overflowY);

  // Kita anggap scrollable jika overflow di-set DAN (opsional) kontennya memang panjang
  // Tapi untuk reset scroll, cukup cek overflow property-nya saja agar aman.
  if (isScrollable) {
    return node;
  }

  return getScrollParent(node.parentNode);
}
