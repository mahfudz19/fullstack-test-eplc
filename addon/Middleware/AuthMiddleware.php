<?php

namespace Addon\Middleware;

use App\Core\Interfaces\MiddlewareInterface;
use App\Core\Response;
use App\Core\Container;

class AuthMiddleware implements MiddlewareInterface
{
  private Container $container;

  public function __construct(Container $container)
  {
    $this->container = $container;
  }

  public function handle($request, \Closure $next, array $params = [])
  {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if ($authHeader === 'Bearer ' . env('TOKEN', 'secret-token-123')) {
      return $next($request);
    }

    if ($request->isSpaRequest() || $request->wantsJson()) {
      $response = new Response();
      return $response->json(['message' => 'Unauthorized', 'redirect' => '/login'], 401);
    }

    $response = new Response($this->container);
    return $response->renderPage([], ['path' => 'auth/checkpoint']);
  }
}
