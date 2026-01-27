<?php

namespace App\Core;

use App\Exceptions\HttpException;
use App\Core\Kernel;

class Application
{
  private Router $router;
  private Request $request;
  private Response $response;
  private Container $container;
  private bool $isBooted = false;

  public function __construct()
  {
    // Constructor sekarang hanya membuat objek inti.

    $this->container = new Container();
    $this->request = new Request();
    $this->response = new Response($this->container);
    $this->router = new Router($this->container);
    $this->registerServices();


    // Daftarkan instance Request sebagai singleton di container
    $this->container->singleton(Request::class, fn() => $this->request);
  }

  public function getContainer(): Container
  {
    return $this->container;
  }

  public function getRouter(): Router
  {
    return $this->router;
  }

  public function boot(): void
  {
    if ($this->isBooted) {
      return;
    }

    $this->registerServices();
    $this->registerMiddleware();
    $this->registerRoutes();

    $this->isBooted = true;
  }

  /**
   * Metode utama yang menjalankan seluruh siklus hidup aplikasi.
   */
  public function run(): void
  {
    try {
      $this->boot();
      $result = $this->router->dispatch($this->request, $this->response);
      if ($result instanceof Response) {
        $response = $result;
      } elseif ($result instanceof RenderableInterface) {
        $response = $result->render($this->container);
      } else {
        throw new \LogicException('Controller harus mengembalikan instance dari Response atau objek yang mengimplementasikan RenderableInterface.');
      }
    } catch (HttpException $e) {
      try {
        /** @var \App\Services\ViewService $viewService */
        $viewService = $this->container->resolve(\App\Services\ViewService::class);

        $errorMeta = new PageMeta('Error ' . $e->getStatusCode());

        // [SMART DETECT] Cek apakah menggunakan error/index.php atau error.php
        $errorViewPath = 'error'; // Default
        if (file_exists(__DIR__ . '/../../addon/Views/error/index.php')) {
          $errorViewPath = 'error/index';
        }

        $errorView = new View(
          $this->container,
          $errorViewPath,
          ['code' => $e->getStatusCode(), 'message' => $e->getMessage()],
          $errorMeta
        );

        $html = $viewService->render($errorView);
        $response = new Response($this->container, $html, $e->getStatusCode());
      } catch (\Throwable $renderError) {
        $response = $this->renderFallbackError(500, 'Terjadi kesalahan kritis saat menampilkan halaman error.', $renderError);
      }
    } catch (RenderableInterface $e) {
      $response = $e->render($this->container);
    } catch (\Throwable $e) {
      if (env('APP_DEBUG') === 'true') dump($e);
      $response = $this->renderFallbackError(500, 'Terjadi kesalahan internal pada server.', $e);
    }

    $response->send();
  }

  private function registerServices(): void
  {
    $providers = require __DIR__ . '/../config/providers.php';

    foreach ($providers as $providerClass) {
      if (class_exists($providerClass)) {
        $providerInstance = new $providerClass();
        $providerInstance->register($this->container);
      } else {
        error_log("[ERROR] Provider class not found: {$providerClass}");
      }
    }
  }

  /**
   * Mendaftarkan middleware dari HttpKernel ke Router.
   */
  private function registerMiddleware(): void
  {
    $kernel = new Kernel();

    foreach ($kernel->getRouteMiddleware() as $alias => $class) {
      $this->router->mapMiddleware($alias, $class);
    }
  }

  private function registerRoutes(): void
  {
    $cachePath = __DIR__ . '/../../storage/cache/routes.php';

    // Cek cache rute (HANYA jika di mode Production)
    // Best Practice: Di development, kita bypass cache agar perubahan rute langsung terlihat.
    // Kita gunakan helper isProduction() yang sudah tersedia di framework ini.
    if (isProduction() && file_exists($cachePath)) {
      $this->router->setRoutes(require $cachePath);
      return;
    }

    // Buat variabel $router tersedia untuk file rute
    $router = $this->router;

    // [SYSTEM ROUTE] Asset Handler (Dev Mode / Fallback)
    // Menangani request ke /build/assets/... dan /build/js/... jika file statis belum ada
    $router->get('build/assets/(.*)', [\App\Core\Controllers\AssetController::class, 'serve']);
    $router->get('build/js/(.*)', [\App\Core\Controllers\AssetController::class, 'serve']);

    require_once __DIR__ . '/../../addon/Router/index.php';
  }

  private function renderFallbackError(int $code, string $message, ?\Throwable $e = null): Response
  {
    if (function_exists('logger')) {
      $logMessage = "Fallback Error Triggered: {$message}";
      logger()->error($logMessage, ['exception' => $e]);
    }

    $displayMessage = ($GLOBALS['config']['debug'] ?? false) && $e
      ? htmlspecialchars($e->getMessage())
      : htmlspecialchars($message);

    // [SMART DETECT] Cek error/index.php dulu, baru error.php
    $errorPagePath = __DIR__ . '/../../addon/Views/error/index.php';
    if (!file_exists($errorPagePath)) {
      $errorPagePath = __DIR__ . '/../../addon/Views/error/index.php';
    }

    try {
      if (file_exists($errorPagePath)) {
        $title = "Error {$code}";
        $message = $displayMessage;

        ob_start();
        require $errorPagePath;
        $html = ob_get_clean();
        return new Response($this->container, $html, $code);
      }
    } catch (\Throwable $renderException) {
      if (function_exists('logger')) {
        logger()->critical('CRITICAL: Gagal merender file error.php. Kembali ke fallback hardcoded.', ['exception' => $renderException]);
      }
    }

    $getBaseUrl = getBaseUrl('/');
    $html = <<<HTML
      <!DOCTYPE html>
      <html lang="en">
      <head>
          <meta charset="UTF-8">
          <title>Error {$code}</title>
          <style>
              body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #f8f9fa; color: #333; text-align: center; padding: 50px; margin: 0; }
              .container { max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
              h1 { color: #dc3545; font-size: 3rem; margin-bottom: 0.5rem; }
              p { font-size: 1.1rem; color: #6c757d; }
              a { color: #0d6efd; }
          </style>
      </head>
      <body>
          <div class="container">
              <h1>Error {$code}</h1>
              <p>{$displayMessage}</p>
              <p><a data-spa href="{$getBaseUrl}">Kembali ke Halaman Utama</a></p>
          </div>
      </body>
      </html>
    HTML;

    return new Response($this->container, $html, $code);
  }
}
