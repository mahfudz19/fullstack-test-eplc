const fs = require("fs");
const path = require("path");
const { execSync } = require("child_process");

const BUILD_DIR = path.resolve(__dirname, "../public/build");

// Fungsi rekursif untuk mencari file
function getAllFiles(dir, exts, fileList = []) {
  if (!fs.existsSync(dir)) return [];
  const files = fs.readdirSync(dir);
  files.forEach((file) => {
    const filePath = path.join(dir, file);
    if (fs.statSync(filePath).isDirectory()) {
      getAllFiles(filePath, exts, fileList);
    } else {
      if (exts.includes(path.extname(file))) {
        fileList.push(filePath);
      }
    }
  });
  return fileList;
}

console.log("\x1b[36m%s\x1b[0m", "🚀 Starting Asset Minification...");

const files = getAllFiles(BUILD_DIR, [".js", ".css"]);

if (files.length === 0) {
  console.log("\x1b[33m%s\x1b[0m", "⚠️ No files to minify in public/build.");
  process.exit(0);
}

console.log(`Found ${files.length} files to minify.`);

try {
  // Construct command: esbuild file1 file2 ... --minify --allow-overwrite --outdir=... --outbase=...
  // Menggunakan npx esbuild atau esbuild langsung jika ada di path
  // --outbase penting untuk menjaga struktur folder

  // Batasi panjang command line (untuk Windows/Shell limits), batching jika perlu
  // Tapi untuk project ini, sekali jalan cukup.

  const fileArgs = files.map((f) => `"${f}"`).join(" ");
  const cmd = `npx esbuild ${fileArgs} --minify --allow-overwrite --outdir="${BUILD_DIR}" --outbase="${BUILD_DIR}" --log-level=info`;

  console.log("\x1b[90m%s\x1b[0m", "> Executing esbuild...");
  execSync(cmd, { stdio: "inherit" });

  console.log("\x1b[32m%s\x1b[0m", "✅ Minification Complete!");
} catch (e) {
  console.error("\x1b[31m%s\x1b[0m", "❌ Minification Failed");
  process.exit(1);
}
