<div class="tasks-container">
  <div class="tasks-header">
    <div>
      <h1 class="tasks-title">Tasks</h1>
      <p class="tasks-subtitle">Manage your daily tasks efficiently.</p>
    </div>
    <a href="#" onclick="openModal('create'); return false;" class="btn-new-task">
      <i class="bi bi-plus-lg"></i>
      New Task
    </a>
  </div>

  <!-- Search Controls -->
  <div class="search-controls">
    <div class="search-wrapper">
      <i class="bi bi-search search-icon"></i>
      <input type="text" id="task-search" placeholder="Search tasks..." class="search-input" oninput="debounceSearch()">
    </div>
  </div>

  <div class="table-card">
    <table class="tasks-table">
      <thead>
        <tr class="table-header-row">
          <th onclick="handleSort('title')" class="sortable-th">
            <div class="th-content">
              Title <i id="icon-title" class="bi bi-arrow-down-up sort-icon"></i>
            </div>
          </th>
          <th onclick="handleSort('status')" class="sortable-th">
            <div class="th-content">
              Status <i id="icon-status" class="bi bi-arrow-down-up sort-icon"></i>
            </div>
          </th>
          <th onclick="handleSort('created_at')" class="sortable-th">
            <div class="th-content">
              Created <i id="icon-created_at" class="bi bi-arrow-down sort-icon active"></i>
            </div>
          </th>
          <th class="actions-th">Actions</th>
        </tr>
      </thead>
      <tbody id="tasks-list">
        <?php if (empty($items)): ?>
          <tr id="no-tasks-placeholder">
            <td colspan="4" class="empty-table-cell">
              <i class="bi bi-clipboard-x empty-icon-large"></i>
              No tasks found. Start by creating one!
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($items as $task): ?>
            <tr class="task-row">
              <td class="task-cell">
                <div class="task-row-title"><?= htmlspecialchars($task['title']) ?></div>
                <div class="task-row-desc"><?= htmlspecialchars(substr($task['description'] ?? '', 0, 50)) ?>...</div>
              </td>
              <td class="task-cell">
                <?php
                $statusClass = $task['status'] === 'done' ? 'status-done' : 'status-pending';
                ?>
                <span class="status-badge-modern <?= $statusClass ?>">
                  <span class="status-dot"></span>
                  <?= ucfirst($task['status']) ?>
                </span>
              </td>
              <td class="task-cell task-date-cell">
                <?= date('M j, Y', strtotime($task['created_at'])) ?>
              </td>
              <td class="task-cell actions-cell">
                <div class="actions-wrapper">
                  <button onclick="openModal('edit', <?= $task['id'] ?>)" class="btn-action btn-edit" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button onclick="if(confirm('Are you sure?')) { fetch('/tasks/<?= $task['id'] ?>', { method: 'DELETE', headers: {'X-SPA-REQUEST': 'true', 'Authorization': `Bearer ${localStorage.getItem('token')}`} }).then(r => r.json()).then(d => { if(window.spa) window.spa.push(d.redirect); else window.location.reload(); }); }" class="btn-action btn-delete" title="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Controls -->
  <div id="pagination-container" class="pagination-container">
    <!-- Rendered by JS -->
  </div>

  <!-- Task Modal -->
  <div id="task-modal" class="modal-overlay">
    <div class="modal-content">
      <h2 id="modal-title" class="modal-title">New Task</h2>

      <form id="task-form" onsubmit="saveTask(event)">
        <input type="hidden" id="task-id">

        <div class="form-group">
          <label for="task-title" class="form-label">Title</label>
          <input id="task-title" required class="form-input">
        </div>

        <div class="form-group">
          <label for="task-desc" class="form-label">Description</label>
          <textarea id="task-desc" rows="3" class="form-input"></textarea>
        </div>

        <div id="status-group" class="form-group" style="display: none;">
          <label for="task-status" class="form-label">Status</label>
          <select id="task-status" class="form-input">
            <option value="pending">Pending</option>
            <option value="done">Done</option>
          </select>
        </div>

        <div class="modal-footer">
          <button type="button" onclick="closeModal()" class="btn-modal btn-cancel">
            Cancel
          </button>
          <button type="submit" id="btn-save" class="btn-modal btn-save">
            Save Task
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  let currentPage = 1;
  const limit = <?= $initialLimit ?? 5 ?>;
  let totalItems = <?= $total ?? 0 ?>;
  // Initialize with PHP data
  let currentTasks = <?= json_encode($items) ?>;
  let currentItemsCount = currentTasks.length;

  let sortBy = 'created_at';
  let sortOrder = 'DESC';

  function renderPagination() {
    const totalPages = Math.ceil(totalItems / limit);
    const container = document.getElementById('pagination-container');

    // If no items or just 1 page, hide pagination but keep container
    if (totalPages <= 1) {
      container.innerHTML = '';
      return;
    }

    let html = '';

    // Previous Button
    const prevDisabled = currentPage === 1;
    html += `<button onclick="goToPage(${currentPage - 1})" ${prevDisabled ? 'disabled' : ''} class="btn-pagination-nav">
        <i class="bi bi-chevron-left"></i> Prev
    </button>`;

    // Page Numbers
    let startPage = Math.max(1, currentPage - 1);
    let endPage = Math.min(totalPages, currentPage + 1);

    if (startPage > 1) {
      html += `<button onclick="goToPage(1)" class="btn-pagination-number">1</button>`;
      if (startPage > 2) html += `<span class="pagination-dots">...</span>`;
    }

    for (let i = startPage; i <= endPage; i++) {
      const isActive = i === currentPage;
      html += `<button onclick="goToPage(${i})" class="btn-pagination-number ${isActive ? 'active' : ''}">${i}</button>`;
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) html += `<span class="pagination-dots">...</span>`;
      html += `<button onclick="goToPage(${totalPages})" class="btn-pagination-number">${totalPages}</button>`;
    }

    // Next Button
    const nextDisabled = currentPage >= totalPages;
    html += `<button onclick="goToPage(${currentPage + 1})" ${nextDisabled ? 'disabled' : ''} class="btn-pagination-nav">
        Next <i class="bi bi-chevron-right"></i>
    </button>`;

    container.innerHTML = html;
  }

  // Initial render for SSR state
  renderPagination();

  let searchTimeout;

  function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      resetAndFetch();
    }, 500);
  }

  function handleSort(column) {
    if (sortBy === column) {
      sortOrder = sortOrder === 'ASC' ? 'DESC' : 'ASC';
    } else {
      sortBy = column;
      sortOrder = 'ASC';
    }
    updateSortIcons();
    resetAndFetch();
  }

  function updateSortIcons() {
    ['title', 'status', 'created_at'].forEach(col => {
      const icon = document.getElementById(`icon-${col}`);
      if (icon) {
        icon.className = 'bi bi-arrow-down-up sort-icon';
        icon.classList.remove('active');
      }
    });

    const activeIcon = document.getElementById(`icon-${sortBy}`);
    if (activeIcon) {
      activeIcon.className = sortOrder === 'ASC' ? 'bi bi-arrow-up sort-icon' : 'bi bi-arrow-down sort-icon';
      activeIcon.classList.add('active');
    }
  }

  function resetAndFetch() {
    currentPage = 1;
    fetchTasks();
  }

  function goToPage(page) {
    const totalPages = Math.ceil(totalItems / limit);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    fetchTasks();
  }

  async function fetchTasks() {
    const search = document.getElementById('task-search').value;

    // Show loading state if needed (optional overlay)
    const container = document.getElementById('tasks-list');
    container.style.opacity = '0.5';

    try {
      const response = await fetch(`/api/tasks?page=${currentPage}&limit=${limit}&search=${search}&sort=${sortBy}&order=${sortOrder}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });

      if (response.status === 401) {
        window.location.href = '/login';
        return;
      }

      const data = await response.json();

      if (data.items) {
        totalItems = data.total;
        currentTasks = data.items; // Update global state
        container.innerHTML = ''; // Clear existing

        if (data.items.length === 0) {
          container.innerHTML = `
                  <tr id="no-tasks-placeholder">
                    <td colspan="4" style="padding: 4rem 1.5rem; text-align: center; color: var(--text-secondary);">
                      <i class="bi bi-clipboard-x" style="font-size: 3rem; color: var(--neutral-300); margin-bottom: 1rem; display: block;"></i>
                      No tasks found.
                    </td>
                  </tr>`;
        } else {
          data.items.forEach(task => {
            appendTaskRow(task);
          });
        }

        renderPagination();
      }
    } catch (e) {
      console.error('Error fetching tasks:', e);
    } finally {
      container.style.opacity = '1';
    }
  }

  function appendTaskRow(task) {
    const tbody = document.getElementById('tasks-list');
    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid var(--neutral-200)';
    tr.style.transition = 'background-color 0.15s ease';

    const statusColor = task.status === 'done' ? '#10b981' : '#f59e0b';
    const statusBg = task.status === 'done' ? '#ecfdf5' : '#fffbeb';
    const date = new Date(task.created_at).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });

    tr.innerHTML = `
        <td style="padding: 1rem 1.5rem;">
            <div style="font-weight: 600; color: var(--text-primary);">${escapeHtml(task.title)}</div>
            <div style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.25rem;">${escapeHtml(task.description ? task.description.substring(0, 50) + (task.description.length > 50 ? '...' : '') : '')}</div>
        </td>
        <td style="padding: 1rem 1.5rem;">
            <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background-color: ${statusBg}; color: ${statusColor};">
                <span style="width: 6px; height: 6px; border-radius: 50%; background-color: ${statusColor}; margin-right: 0.5rem;"></span>
                ${task.status.charAt(0).toUpperCase() + task.status.slice(1)}
            </span>
        </td>
        <td style="padding: 1rem 1.5rem; color: var(--text-secondary); font-size: 0.875rem;">
            ${date}
        </td>
        <td style="padding: 1rem 1.5rem; text-align: right;">
             <div style="display: inline-flex; gap: 0.5rem;">
                  <button onclick="openModal('edit', ${task.id})" style="padding: 0.5rem; border-radius: 0.5rem; color: var(--primary-600); background: none; border: none; cursor: pointer; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='var(--primary-50)'" onmouseout="this.style.backgroundColor='transparent'" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button onclick="if(confirm('Are you sure?')) { fetch('/tasks/${task.id}', { method: 'DELETE', headers: {'X-SPA-REQUEST': 'true', 'Authorization': 'Bearer ' + localStorage.getItem('token')} }).then(r => r.json()).then(d => { if(window.spa) window.spa.push(d.redirect); else window.location.reload(); }); }" style="padding: 0.5rem; border-radius: 0.5rem; color: #ef4444; background: none; border: none; cursor: pointer; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#fef2f2'" onmouseout="this.style.backgroundColor='transparent'" title="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
            </div>
        </td>
    `;
    tr.onmouseover = function() {
      this.style.backgroundColor = 'var(--neutral-50)';
    };
    tr.onmouseout = function() {
      this.style.backgroundColor = 'transparent';
    };

    tbody.appendChild(tr);
  }

  function escapeHtml(text) {
    if (!text) return '';
    return text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  // --- Modal Logic ---

  function openModal(mode, taskId = null) {
    const modal = document.getElementById('task-modal');
    const modalTitle = document.getElementById('modal-title');
    const form = document.getElementById('task-form');
    const statusGroup = document.getElementById('status-group');
    const btnSave = document.getElementById('btn-save');

    // Reset Form
    form.reset();
    document.getElementById('task-id').value = '';

    if (mode === 'create') {
      modalTitle.textContent = 'New Task';
      statusGroup.style.display = 'none';
      btnSave.textContent = 'Create Task';
    } else {
      modalTitle.textContent = 'Edit Task';
      statusGroup.style.display = 'block';
      btnSave.textContent = 'Update Task';

      // Find task data
      const task = currentTasks.find(t => t.id == taskId);
      if (task) {
        document.getElementById('task-id').value = task.id;
        document.getElementById('task-title').value = task.title;
        document.getElementById('task-desc').value = task.description || '';
        document.getElementById('task-status').value = task.status;
      }
    }

    // Show Modal
    modal.style.display = 'flex';
    // Small delay to allow display:flex to apply before opacity transition
    setTimeout(() => {
      modal.style.opacity = '1';
      modal.querySelector('div').style.transform = 'scale(1)';
    }, 10);
  }

  function closeModal() {
    const modal = document.getElementById('task-modal');
    modal.style.opacity = '0';
    modal.querySelector('div').style.transform = 'scale(0.95)';

    setTimeout(() => {
      modal.style.display = 'none';
    }, 300);
  }

  // Close on outside click
  document.getElementById('task-modal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('task-modal')) {
      closeModal();
    }
  });

  async function saveTask(e) {
    e.preventDefault();

    const btnSave = document.getElementById('btn-save');
    const originalText = btnSave.textContent;
    btnSave.disabled = true;
    btnSave.textContent = 'Saving...';

    const id = document.getElementById('task-id').value;
    const title = document.getElementById('task-title').value;
    const description = document.getElementById('task-desc').value;
    const status = document.getElementById('task-status').value;

    const isEdit = !!id;
    const url = isEdit ? `/tasks/${id}` : '/tasks';
    const method = isEdit ? 'PUT' : 'POST';

    const payload = {
      title,
      description,
      status: isEdit ? status : 'pending' // Default to pending for new tasks
    };

    try {
      const response = await fetch(url, {
        method: method,
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'X-SPA-REQUEST': 'true'
        },
        body: JSON.stringify(payload)
      });

      if (response.ok) {
        closeModal();
        fetchTasks(); // Refresh list

        // Optional: Show toast
        // alert(isEdit ? 'Task updated!' : 'Task created!');
      } else {
        const error = await response.json();
        alert(error.message || 'Failed to save task');
      }
    } catch (err) {
      console.error(err);
      alert('An error occurred');
    } finally {
      btnSave.disabled = false;
      btnSave.textContent = originalText;
    }
  }
</script>