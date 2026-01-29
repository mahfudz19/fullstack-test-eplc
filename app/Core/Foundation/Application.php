<?php

namespace App\Core\Foundation;

// Impor dari sub-direktori Core lainnya
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\Router;
use App\Core\View\View;
use App\Core\View\PageMeta;

// Impor Interface dan Exception
use App\Core\Interfaces\RenderableInterface;
use App\Exceptions\HttpException;

class Application
{
  private Router $router;
  private Request $request;
  private Response $response;
  private Container $container;
  private bool $isBooted = false;

  public function __construct()
  {
    // Karena Container, Kernel, dan Application berada di namespace yang sama (Foundation),
    // mereka bisa langsung dipanggil tanpa 'use'.
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
        throw new \LogicException('Controller harus mengembalikan instance dari Response atau RenderableInterface.');
      }
    } catch (HttpException $e) {
      try {
        /** @var \App\Services\ViewService $viewService */
        $viewService = $this->container->resolve(\App\Services\ViewService::class);

        $errorMeta = new PageMeta('Error ' . $e->getStatusCode());

        $errorViewPath = 'error';
        if (file_exists(__DIR__ . '/../../../addon/Views/error/index.php')) {
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
    // Path ke config disesuaikan karena posisi file Application sekarang lebih dalam 1 level
    $providers = require __DIR__ . '/../../config/providers.php';

    foreach ($providers as $providerClass) {
      if (class_exists($providerClass)) {
        $providerInstance = new $providerClass();
        $providerInstance->register($this->container);
      }
    }
  }

  private function registerMiddleware(): void
  {
    $kernel = new Kernel();

    foreach ($kernel->getRouteMiddleware() as $alias => $class) {
      $this->router->mapMiddleware($alias, $class);
    }
  }

  private function registerRoutes(): void
  {
    // Path ke cache disesuaikan (naik 2 level ke root project, lalu ke storage)
    $cachePath = __DIR__ . '/../../../storage/cache/routes.php';

    if (isProduction() && file_exists($cachePath)) {
      $this->router->setRoutes(require $cachePath);
      return;
    }

    $router = $this->router;

    // Asset Handler
    $router->get('build/assets/(.*)', [\App\Core\Controllers\AssetController::class, 'serve']);
    $router->get('build/js/(.*)', [\App\Core\Controllers\AssetController::class, 'serve']);

    // Path ke rute addon disesuaikan
    require_once __DIR__ . '/../../../addon/Router/index.php';
  }

  private function renderFallbackError(int $code, string $message, ?\Throwable $e = null): Response
  {
    // ... logika fallback error tetap sama, sesuaikan path file jika perlu ...
    return new Response($this->container, "Critical Error: " . $message, $code);
  }
}
