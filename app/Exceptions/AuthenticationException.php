<?php

namespace App\Exceptions;

use App\Core\Container;
use App\Core\RedirectResponse;
use App\Core\Response;
use App\Core\RenderableInterface;

class AuthenticationException extends \Exception implements RenderableInterface
{
  /**
   * Membuat Response yang sesuai untuk exception ini.
   *
   * @param Container $container
   * @return Response
   */
  public function render(Container $container): Response
  {
    // Tugas exception ini sederhana: selalu redirect ke halaman login.
    return new RedirectResponse($container, 'login');
  }
}
