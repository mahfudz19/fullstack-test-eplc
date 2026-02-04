<?php

namespace App\Console\Commands;

use App\Core\Foundation\Application;
use App\Console\Contracts\CommandInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BuildCommand implements CommandInterface
{
  public function __construct(
    private Application $app,
  ) {}

  public function getName(): string
  {
    return 'build';
  }

  public function getDescription(): string
  {
    return 'Build assets dan cache untuk produksi (JS, CSS, Routes)';
  }

  public function handle(array $arguments): int
  {
    echo color("Mazu Build System\n", "cyan");

    // 1. Build Route Cache
    echo "1. Building Route Cache...\n";
    require_once __DIR__ . '/../../../scripts/route-cache.php';
    echo "\n";

    // 2. Publish Core Assets (SPA Engine)
    echo "2. Publishing Core Assets...\n";
    $this->publishCoreAssets();

    // 3. Publish Addon Assets (Views CSS/JS)
    echo "3. Publishing Addon Assets...\n";
    $this->publishAddonAssets();

    // 4. Minify Assets (via Node.js)
    echo "4. Minifying Assets...\n";
    $this->minifyAssets();

    echo color("\nBuild Complete! 🚀\n", "green");
    return 0;
  }

  private function minifyAssets(): void
  {
    echo "   Running PHP Native Minification...\n";
    $buildDir = realpath(__DIR__ . '/../../../public/build');

    if (!$buildDir || !is_dir($buildDir)) {
      echo color("   Build directory not found.\n", "yellow");
      return;
    }

    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($buildDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    $count = 0;
    foreach ($files as $file) {
      if ($file->isFile()) {
        $ext = $file->getExtension();
        if ($ext === 'css') {
          $this->minifyCss($file->getRealPath());
          $count++;
        } elseif ($ext === 'js') {
          $this->minifyJs($file->getRealPath());
          $count++;
        }
      }
    }

    echo "   Minified $count files successfully.\n";
  }

  private function minifyCss(string $path): void
  {
    $content = file_get_contents($path);
    // Remove comments
    $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
    // Remove whitespace
    $content = str_replace(["\r\n", "\r", "\n", "\t"], '', $content);
    $content = preg_replace('/\s{2,}/', ' ', $content);
    $content = str_replace([': ', ' :'], ':', $content);
    $content = str_replace([' {', '{ '], '{', $content);
    $content = str_replace(['; ', ' ;'], ';', $content);
    $content = str_replace([', ', ' ,'], ',', $content);
    file_put_contents($path, $content);
    echo "   Minified CSS: " . basename($path) . "\n";
  }

  private function minifyJs(string $path): void
  {
    $content = file_get_contents($path);

    // Simple JS Minifier (Safe Mode)
    // 1. Remove block comments
    $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);

    // 2. Remove line comments (hati-hati dengan URL http://)
    // Regex ini mencari // yang tidak didahului oleh : (untuk menghindari http://)
    // dan tidak berada dalam string. Ini cukup kompleks, jadi kita gunakan pendekatan aman:
    // Hanya hapus baris yang diawali dengan // (setelah trim) atau // di akhir baris yang aman.

    // Split lines
    $lines = explode("\n", $content);
    $newLines = [];
    foreach ($lines as $line) {
      $trim = trim($line);
      // Skip empty lines or full comment lines
      if (empty($trim) || str_starts_with($trim, '//')) {
        continue;
      }
      // Remove trailing comments (simple check)
      // Note: This is risky without a parser if // appears inside a string like "http://..."
      // So we will just trim whitespace for safety in "Pure PHP" mode without tokenizer.
      $newLines[] = $trim;
    }
    $content = implode("\n", $newLines);

    // 3. Remove extra whitespace
    // $content = preg_replace('/\s+/', ' ', $content); // Risky for JS due to ASI (Automatic Semicolon Insertion)

    file_put_contents($path, $content);
    echo "   Minified JS: " . basename($path) . "\n";
  }

  private function publishCoreAssets(): void
  {
    $source = __DIR__ . '/../../Core/Assets/js/spa.js';
    $dest = __DIR__ . '/../../../public/build/js/spa.js';

    if (!file_exists($source)) {
      echo color("Warning: Source spa.js not found at {$source}\n", "yellow");
      echo "   Please move public/js/spa.js to app/Core/Assets/js/spa.js first.\n";
      return;
    }

    $this->ensureDir(dirname($dest));
    if (copy($source, $dest)) {
      echo "   Copied: spa.js\n";
    } else {
      echo color("   Failed to copy spa.js\n", "red");
    }
  }

  private function publishAddonAssets(): void
  {
    $sourceDir = __DIR__ . '/../../../addon/Views';
    $destDir = __DIR__ . '/../../../public/build/assets';

    if (!is_dir($sourceDir)) {
      echo "   No addon views directory found.\n";
      return;
    }

    $sourceDir = realpath($sourceDir);

    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::SELF_FIRST
    );

    $count = 0;
    foreach ($iterator as $item) {
      if ($item->isFile()) {
        $ext = $item->getExtension();
        // Publish only safe static assets
        if (in_array($ext, ['css', 'js', 'png', 'jpg', 'jpeg', 'svg', 'woff', 'woff2'])) {
          // Manual relative path calculation to satisfy static analyzers and runtime safety
          $subPath = substr($item->getPathname(), strlen($sourceDir) + 1);
          $target = $destDir . '/' . $subPath;

          $this->ensureDir(dirname($target));
          copy($item->getPathname(), $target);
          $count++;
        }
      }
    }
    echo "   Total assets published: {$count}\n";
  }

  private function ensureDir(string $path): void
  {
    if (!is_dir($path)) {
      mkdir($path, 0755, true);
    }
  }
}
