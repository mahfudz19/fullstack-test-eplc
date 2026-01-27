<?php

// File ini sekarang hanya mengembalikan array konfigurasi.

// Fungsi untuk mendeteksi environment
if (!function_exists('detectEnvironment')) {
  function detectEnvironment(): string
  {
    if (env('APP_ENV')) {
      return env('APP_ENV');
    }
    if (isset($_SERVER['HTTP_HOST'])) {
      $host = $_SERVER['HTTP_HOST'];
      if (
        strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false ||
        strpos($host, '.local') !== false || strpos($host, ':8080') !== false
      ) {
        return 'development';
      }
    }
    return 'production';
  }
}

$environment = detectEnvironment();

if ($environment === 'development') {
  return [
    'db' => [
      'host'     => env('DB_HOST', 'localhost'),
      'dbname'   => env('DB_NAME', 'talent'),
      'username' => env('DB_USER', 'root'),
      'password' => env('DB_PASS', 'root')
    ],
    'debug' => env('APP_DEBUG', 'true') === 'true',
    'app_name' => env('APP_NAME', 'Sub Sistem Talent (Dev)'),
    'database' => require __DIR__ . '/database.php',
  ];
} else {
  return [
    'db' => [
      'host'     => env('DB_HOST', 'localhost'),
      'dbname'   => env('DB_NAME', 'talent'),
      'username' => env('DB_USER', 'root'),
      'password' => env('DB_PASS', '')
    ],
    'debug' => env('APP_DEBUG', 'false') === 'true',
    'app_name' => env('APP_NAME', 'Sub Sistem Talent'),
    'database' => require __DIR__ . '/config/database.php',
  ];
}
