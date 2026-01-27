<!-- Navbar -->
<nav class="home-navbar">
  <div class="home-nav-container">
    <div class="flex">
      <div class="home-logo">
        <span>TaskApp</span>
      </div>
    </div>
    <div class="home-nav-right">
      <a data-spa href="<?= getBaseUrl('/login') ?>" class="btn btn-text">
        Log in
      </a>
      <a data-spa href="<?= getBaseUrl('/login') ?>" class="btn btn-primary">
        Get Started
      </a>
    </div>
  </div>
</nav>

<!-- Main Content -->
<main class="home-main">
  <?= $children ?>
</main>

<!-- Footer -->
<footer class="home-footer">
  <div class="home-footer-container">
    <p class="footer-text">
      &copy; <?= date('Y') ?> TaskApp. All rights reserved.
    </p>
  </div>
</footer>