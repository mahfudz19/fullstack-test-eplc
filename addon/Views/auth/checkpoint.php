<style>
  body {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
    background: #f8fafc;
    font-family: system-ui, -apple-system, sans-serif;
    color: #64748b;
  }

  .loader {
    text-align: center;
  }

  .spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 1rem;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }
</style>
<div class="loader">
  <div class="spinner"></div>
  <p>Verifying authentication...</p>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('token');

    if (!token) {
      window.location.replace('/login');
      return;
    }

    if (window.spa) {
      window.spa.refresh();
    } else {
      console.error("SPA Engine not loaded in checkpoint.");
      window.location.replace('/login');
    }
  });
</script>