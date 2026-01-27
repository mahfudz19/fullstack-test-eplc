<div class="home-content">
  <div class="home-header">
    <h1 class="home-title">
      Explore Tasks
    </h1>
    <p class="home-subtitle">
      Discover public tasks and get inspired.
    </p>
  </div>

  <div class="task-grid">
    <?php foreach ($items as $item): ?>
      <div class="task-card">
        <div class="card-body">
          <div class="card-header">
            <span class="status-badge <?= ($item['status'] ?? 'pending') === 'done' ? 'status-done' : 'status-pending' ?>">
              <?= ucfirst($item['status'] ?? 'pending') ?>
            </span>
            <span class="task-date">
              <?= isset($item['created_at']) ? date('M d, Y', strtotime($item['created_at'])) : 'Recently' ?>
            </span>
          </div>
          <h3 class="task-title" title="<?= htmlspecialchars($item['title'] ?? 'Untitled') ?>">
            <?= htmlspecialchars($item['title'] ?? 'Untitled') ?>
          </h3>
          <p class="task-desc">
            <?= htmlspecialchars($item['description'] ?? 'No description provided.') ?>
          </p>
        </div>
        <div class="card-footer">
          <a data-spa href="<?= getBaseUrl('/login') ?>" class="btn-link">
            View Details
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
            </svg>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <svg class="empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
      </svg>
      <h3 class="empty-title">No tasks found</h3>
      <p class="empty-desc">Be the first to create a task!</p>
      <div class="empty-action">
        <a data-spa href="<?= getBaseUrl('/login') ?>" class="btn btn-primary">
          Get Started
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>