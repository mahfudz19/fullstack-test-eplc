<div class="error-container">
  <div class="error-box">
    <h1 class="error-code animate__animated animate__bounceIn">
      <?= htmlspecialchars($code ?? 'Error'); ?>
    </h1>
    <p class="error-message animate__animated animate__fadeInUp">
      <?= htmlspecialchars($message ?? 'Terjadi kesalahan.'); ?>
    </p>
    <p class="error-details animate__animated animate__fadeInUp animate__delay-1s">
      Silakan coba lagi nanti atau kembali ke halaman utama.
    </p>
    <a data-spa href="<?= getBaseUrl('/'); ?>" class="btn btn-primary mt-3 animate__animated animate__fadeInUp animate__delay-2s">
      <i class="bi bi-house-door-fill me-2"></i>Kembali ke Dashboard
    </a>
  </div>
</div>