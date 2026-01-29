<?php

namespace Addon\Middleware;

use App\Core\Foundation\Container;
use App\Core\Http\Response;
use App\Core\Interfaces\MiddlewareInterface;
use App\Services\ConfigService;
use App\Services\SessionService;

class AuthMiddleware implements MiddlewareInterface
{
  private Container $container;

  public function __construct(Container $container)
  {
    $this->container = $container;
  }

  public function handle($request, \Closure $next, array $params = [])
  {
    $config = $this->container->resolve(ConfigService::class);
    $mode = $config->get('auth.mode', 'token');

    if ($mode === 'session') {
      $sessionKey = $config->get('auth.session_key', 'user_id');
      $session = $this->container->resolve(SessionService::class);
      if ($session->get($sessionKey)) {
        return $next($request);
      }
    } elseif ($mode === 'token') {
      $token = $request->bearerToken();
      if (!$token) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($authHeader, 'Bearer ')) {
          $token = substr($authHeader, 7);
        }
      }
      if (!$token) {
        $cookieName = $config->get('auth.token_cookie', $config->get('auth.token_key', 'token'));
        $token = $_COOKIE[$cookieName] ?? null;
      }
      $expectedToken = $config->get('auth.token_value', env('TOKEN', 'secret-token-123'));
      if ($token && hash_equals($expectedToken, $token)) {
        return $next($request);
      }
    } elseif ($mode === 'custom') {
      $guard = $config->get('auth.custom_guard');
      if (is_callable($guard)) {
        $passed = $guard($request, $this->container);
        if ($passed) {
          return $next($request);
        }
      }
    }

    if ($request->isSpaRequest() || $request->wantsJson()) {
      $response = new Response();
      return $response->json(['message' => 'Unauthorized', 'redirect' => getBaseUrl('/login')], 401);
    }

    $response = new Response($this->container);
    return $response->renderPage([], ['path' => 'auth/checkpoint']);
  }
}
