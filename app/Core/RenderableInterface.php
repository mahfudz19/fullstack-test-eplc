<?php

namespace App\Core;

interface RenderableInterface
{
  /**
   * Merender objek ini menjadi sebuah Response.
   *
   * @param Container $container
   * @return Response
   */
  public function render(Container $container): Response;
}
