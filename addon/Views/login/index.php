<div class="login-container">
  <div class="login-card">
    <a data-spa href="<?= getBaseUrl('/') ?>" class="btn btn-text">kembali</a>
    <h1 class="login-title">EPLC Login</h1>

    <form id="loginForm" class="login-form">
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" class="form-control" value="admin@example.com" required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" class="form-control" value="admin123" required>
      </div>

      <div id="errorMessage" class="error-message hidden"></div>

      <button type="submit" class="btn-login">
        Sign In
      </button>
    </form>
  </div>
</div>

<script>
  // Check if user is already logged in
  if (localStorage.getItem('token')) {
    window.location.href = '<?= getBaseUrl('/dashboard') ?>';
  }

  document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('errorMessage');
    const submitBtn = document.querySelector('.btn-login');

    // Reset UI
    errorDiv.textContent = '';
    errorDiv.classList.add('hidden');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing in...';

    try {
      const response = await fetch('<?= getBaseUrl('/api/login') ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          email,
          password
        })
      });

      const data = await response.json();

      if (response.ok) {
        // Simpan token
        localStorage.setItem('token', data.token);
        // Simpan info user jika perlu
        localStorage.setItem('user', JSON.stringify(data.user));

        // Redirect ke dashboard
        if (window.spa && window.spa.push) {
          window.spa.clearCache();
          window.spa.push('<?= getBaseUrl('/dashboard') ?>');
        } else {
          window.location.href = '<?= getBaseUrl('/dashboard') ?>';
        }
      } else {
        errorDiv.textContent = data.message || 'Login failed. Please check your credentials.';
        errorDiv.classList.remove('hidden');
      }
    } catch (err) {
      console.error(err);
      errorDiv.textContent = 'Network error occurred. Please try again.';
      errorDiv.classList.remove('hidden');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Sign In';
    }
  });
</script>