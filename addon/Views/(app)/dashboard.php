<!-- Content -->
<div class="px-4 py-6 sm:px-0">
  <div style="border: 4px dashed #e5e7eb; border-radius: 0.5rem; height: 24rem; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #9ca3af; text-align: center;">
    <p style="font-size: 1.125rem; margin-bottom: 0.5rem;">Welcome to your Dashboard!</p>
    <p>Task Management Features coming soon here.</p>
    <a data-spa href="<?= getBaseUrl('/tasks') ?>" style="margin-top: 1rem; padding: 0.5rem 1rem; background-color: var(--color-primary-600); color: white; border-radius: 0.25rem; text-decoration: none; transition: background-color 0.2s;">Go to Tasks</a>
  </div>
</div>

<script>
  // Simple Client-side Auth Check
  (function() {
    const token = localStorage.getItem('token');
    if (!token) {
      if (window.spa) {
        window.spa.push('<?= getBaseUrl('/login') ?>');
      } else {
        window.location.href = '<?= getBaseUrl('/login') ?>';
      }
    }
  })();
</script>