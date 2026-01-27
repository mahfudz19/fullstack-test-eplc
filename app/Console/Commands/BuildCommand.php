<?php

namespace App\Console\Commands;

use App\Core\Application;
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
    $script = realpath(__DIR__ . '/../../../scripts/minify.js');
    if (!$script || !file_exists($script)) {
      echo color("   Minify script not found at scripts/minify.js\n", "yellow");
      return;
    }

    // Cek ketersediaan Node.js
    exec('node -v', $out, $ret);
    if ($ret !== 0) {
      echo color("   Node.js not found. Skipping minification.\n", "yellow");
      return;
    }

    echo "   Running esbuild minification...\n";
    exec("node " . escapeshellarg($script), $output, $returnVar);

    if ($returnVar === 0) {
      echo "   Minification successful.\n";
    } else {
      echo color("   Minification failed. Ensure 'npm install' is run.\n", "red");
      // Optional: Tampilkan error output
      foreach ($output as $line) {
        echo "   > $line\n";
      }
    }
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
