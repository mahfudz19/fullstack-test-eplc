<?php

namespace App\Core;

class JsonResponse extends Response
{
  public function __construct(?Container $container, array $data, int $statusCode = 200)
  {
    $content = json_encode($data);
    $headers = ['Content-Type' => 'application/json'];
    // Teruskan container ke parent constructor
    parent::__construct($container, $content, $statusCode, $headers);
  }
}
