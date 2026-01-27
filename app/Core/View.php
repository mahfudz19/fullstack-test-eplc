<?php

namespace App\Core;

use App\Services\ViewService;

class View implements RenderableInterface
{
  public string $path;
  public array $props;
  public PageMeta $meta;

  /**
   * Menyimpan daftar path CSS yang ditemukan secara otomatis.
   * @var array<string>
   */
  protected static array $styles = [];

  public function __construct(
    Container $container,
    ?string $path = null,
    array $props = [],
    ?PageMeta $meta = null
  ) {
    if ($path === null) {
      /** @var Request $request */
      $request = $container->resolve(Request::class);
      $this->path = $request->getMatchedRoutePattern() ?? $request->getBasePath();
    } else {
      $this->path = $path;
    }

    $this->props = $props;
    $this->meta = $meta ?? new PageMeta('Untitled Page');
  }

  /**
   * Menambahkan path CSS ke antrian.
   * Path harus relatif terhadap folder Views, contoh: '(app)/layout/sidebar.css'
   */
  public static function addStyle(string $path): void
  {
    if (!in_array($path, self::$styles)) {
      self::$styles[] = $path;
    }
  }

  /**
   * Mengambil semua style yang terdaftar.
   * @return array<string>
   */
  public static function getStyles(): array
  {
    return self::$styles;
  }

  /**
   * Merender tag <link> untuk semua CSS yang terkumpul.
   */
  public static function renderStyles(): string
  {
    $html = '';
    foreach (self::$styles as $stylePath) {
      // Mengarah ke build/assets/... (Dev: dilayani AssetController, Prod: static file)
      $url = getBaseUrl('build/assets/' . $stylePath);
      $html .= '<link rel="stylesheet" href="' . $url . '">' . PHP_EOL;
    }
    return $html;
  }

  /**
   * Merender semua meta tag SEO dan tag head standar.
   */
  public static function renderMeta(PageMeta $meta): string
  {
    $html = '';

    // Basic Meta
    $html .= '<meta charset="UTF-8">' . PHP_EOL;
    $html .= '<title>' . htmlspecialchars($meta->title) . '</title>' . PHP_EOL;

    if ($meta->description) {
      $html .= '<meta name="description" content="' . htmlspecialchars($meta->description) . '">' . PHP_EOL;
    }
    if ($meta->keywords) {
      $html .= '<meta name="keywords" content="' . htmlspecialchars($meta->keywords) . '">' . PHP_EOL;
    }
    $html .= '<meta name="robots" content="' . htmlspecialchars($meta->robots) . '">' . PHP_EOL;
    if ($meta->canonical) {
      $html .= '<link rel="canonical" href="' . htmlspecialchars($meta->canonical) . '">' . PHP_EOL;
    }

    $html .= PHP_EOL . '  <!-- Open Graph / Facebook -->' . PHP_EOL;
    $html .= '<meta property="og:type" content="' . htmlspecialchars($meta->ogType ?? $meta->type) . '">' . PHP_EOL;
    $html .= '<meta property="og:title" content="' . htmlspecialchars($meta->ogTitle ?? $meta->title) . '">' . PHP_EOL;
    if ($meta->ogDescription ?? $meta->description) {
      $html .= '<meta property="og:description" content="' . htmlspecialchars($meta->ogDescription ?? $meta->description) . '">' . PHP_EOL;
    }
    if ($meta->ogImage ?? $meta->image) {
      $html .= '<meta property="og:image" content="' . htmlspecialchars($meta->ogImage ?? $meta->image) . '">' . PHP_EOL;
    }
    if ($meta->canonical || $meta->ogUrl) {
      $html .= '<meta property="og:url" content="' . htmlspecialchars($meta->ogUrl ?? $meta->canonical) . '">' . PHP_EOL;
    }
    if ($meta->ogSiteName ?? $meta->siteName) {
      $html .= '<meta property="og:site_name" content="' . htmlspecialchars($meta->ogSiteName ?? $meta->siteName) . '">' . PHP_EOL;
    }
    if ($meta->locale) {
      $html .= '<meta property="og:locale" content="' . htmlspecialchars($meta->locale) . '">' . PHP_EOL;
    }

    $html .= PHP_EOL . '  <!-- Twitter -->' . PHP_EOL;
    if ($meta->twitterCard) {
      $html .= '<meta name="twitter:card" content="' . htmlspecialchars($meta->twitterCard) . '">' . PHP_EOL;
    }
    if ($meta->twitterSite) {
      $html .= '<meta name="twitter:site" content="' . htmlspecialchars($meta->twitterSite) . '">' . PHP_EOL;
    }
    if ($meta->twitterCreator) {
      $html .= '<meta name="twitter:creator" content="' . htmlspecialchars($meta->twitterCreator) . '">' . PHP_EOL;
    }
    if ($meta->twitterTitle) {
      $html .= '<meta name="twitter:title" content="' . htmlspecialchars($meta->twitterTitle) . '">' . PHP_EOL;
    }
    if ($meta->twitterDescription) {
      $html .= '<meta name="twitter:description" content="' . htmlspecialchars($meta->twitterDescription) . '">' . PHP_EOL;
    }
    if ($meta->twitterImage ?? $meta->ogImage ?? $meta->image) {
      $html .= '<meta name="twitter:image" content="' . htmlspecialchars($meta->twitterImage ?? $meta->ogImage ?? $meta->image) . '">' . PHP_EOL;
    }

    $html .= PHP_EOL . '  <!-- CSRF Token -->' . PHP_EOL;
    $html .= '<meta name="csrf-token" content="' . csrf_token() . '">' . PHP_EOL;

    $html .= PHP_EOL . '  <!-- Viewport & Favicon -->' . PHP_EOL;
    $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . PHP_EOL;
    $html .= '<link rel="icon" type="image/x-icon" href="' . getBaseUrl('/logo_app/favicon.ico') . '">' . PHP_EOL;

    return $html;
  }

  /**
   * Merender script core engine (SPA, Ripple, Component Loader).
   */
  public static function renderScripts(): string
  {
    $html = '';
    $html .= '<script src="' . getBaseUrl('build/js/spa.js') . '"></script>' . PHP_EOL;
    return $html;
  }

  /**
   * Merender view ini menjadi sebuah Response.
   *
   * @param Container $container
   * @return Response
   */
  public function render(Container $container): Response
  {
    /** @var ViewService $viewService */
    $viewService = $container->resolve(ViewService::class);
    $output = $viewService->render($this);

    /** @var Request $request */
    $request = $container->resolve(Request::class);

    if ($request->isSpaRequest()) {
      // 1. Minify HTML Output (Hapus spasi berlebih, enter, dan tab)
      // Ini akan memangkas ukuran payload secara drastis
      $output = preg_replace('/>\s+</', '><', $output);
      $output = preg_replace('/\s+/', ' ', $output);
      $output = trim($output);

      // --- ETag Implementation ---
      // Generate hash unik dari konten
      $etag = '"' . md5($output) . '"';

      // Cek apakah browser mengirim ETag yang sama
      if (
        isset($request->server['HTTP_IF_NONE_MATCH']) &&
        trim($request->server['HTTP_IF_NONE_MATCH']) === $etag
      ) {

        // Konten tidak berubah, kirim 304 Not Modified
        header('HTTP/1.1 304 Not Modified');
        header('ETag: ' . $etag);
        header('Cache-Control: no-cache'); // Pastikan browser selalu validasi ke server
        exit; // Stop eksekusi, hemat bandwidth
      }

      // Aktifkan GZIP Compression jika didukung browser
      if (strpos($request->server['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false) {
        $output = gzencode($output, 9); // Level 9 = Maksimum Kompresi
        $response = new Response($container, $output);
        $response->setHeader('Content-Encoding', 'gzip');
        $response->setHeader('Content-Length', (string)strlen($output));
      } else {
        $response = new Response($container, $output);
      }

      $response->setHeader('Content-Type', 'application/json');
      $response->setHeader('ETag', $etag); // Kirim ETag ke browser
      $response->setHeader('Cache-Control', 'no-cache'); // Instruksikan browser untuk selalu validasi ETag
      $response->setHeader('Vary', 'X-SPA-REQUEST, X-SPA-TARGET-LAYOUT, X-SPA-LAYOUTS'); // Cegah browser menggunakan cache ini untuk request biasa
      return $response;
    }

    return new Response($container, $output);
  }
}
