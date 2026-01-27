<!DOCTYPE html>
<html lang="en">

<head>
  <?= App\Core\View::renderMeta($meta) ?>

  <!-- Link ke file CSS yang sudah di-generate oleh Tailwind CLI -->
  <!-- Google Fonts - Pindahkan ke sini -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

  <!-- Auto-Injected Styles -->
  <?= App\Core\View::renderStyles() ?>

  <!-- Bootstrap Icons (opsional) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
  <!-- Global Loading Progress Bar -->
  <div id="global-progress-bar" class="progress-bar-container">
    <div id="global-progress-bar-inner" class="progress-bar-fill"></div>
  </div>

  <!-- Variabel ini akan berisi konten dari layout anak atau halaman  -->
  <div id="app-content" data-layout="layout.php">
    <?= $children; ?>
  </div>

  <!-- SPA Script -->
  <?= App\Core\View::renderScripts() ?>

  <!-- Global Application Config & Interceptors -->
  <script>
    if (window.spa && window.spa.onRequest) {
      window.spa.onRequest((headers) => {
        const token = localStorage.getItem('token');
        if (token) headers['Authorization'] = 'Bearer ' + token;
      });
    }
  </script>
</body>

</html>