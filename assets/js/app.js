/**
 * Global UI & Utility JavaScript
 * Construction Materials Shop Management & Accounting System
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Theme Management (Light / Dark / System)
  initTheme();

  // 2. Sidebar Toggle (Mobile / Drawer)
  initSidebar();

  // 3. Global Search Setup
  initGlobalSearch();
});

/**
 * Initialize Theme
 */
function initTheme() {
  const savedTheme = localStorage.getItem('nirman_erp_theme') || 'system';
  applyTheme(savedTheme);

  const themeToggleBtn = document.getElementById('theme-toggle-btn');
  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
      const current = document.documentElement.getAttribute('data-theme') || 'light';
      const nextTheme = current === 'dark' ? 'light' : 'dark';
      localStorage.setItem('nirman_erp_theme', nextTheme);
      applyTheme(nextTheme);
      showToast(`Switched to ${nextTheme} mode`, 'info');
    });
  }
}

function applyTheme(theme) {
  if (theme === 'system') {
    const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
  } else {
    document.documentElement.setAttribute('data-theme', theme);
  }
}

/**
 * Mobile Sidebar Drawer Controller
 */
function initSidebar() {
  const toggleBtn = document.getElementById('sidebar-toggle-btn');
  const sidebar = document.getElementById('app-sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');

  if (toggleBtn && sidebar && backdrop) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('show');
      backdrop.classList.toggle('show');
    });

    backdrop.addEventListener('click', () => {
      sidebar.classList.remove('show');
      backdrop.classList.remove('show');
    });
  }
}

/**
 * Toast Notification Dispatcher
 */
function showToast(message, type = 'info') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `
    <span>${message}</span>
    <button onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1.1rem;margin-left:10px;">&times;</button>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 300);
  }, 4000);
}

/**
 * Modal Manager
 */
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('show');
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('show');
  }
}

/**
 * Standard AJAX Request Wrapper with CSRF header
 */
async function apiRequest(url, method = 'GET', body = null) {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const options = {
    method,
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrfToken
    }
  };

  if (body) {
    if (body instanceof FormData) {
      options.body = body;
    } else {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(body);
    }
  }

  try {
    const response = await fetch(url, options);
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('API Request Error:', error);
    showToast('Network error or unexpected server response.', 'error');
    throw error;
  }
}

/**
 * Global Search
 */
function initGlobalSearch() {
  const input = document.getElementById('global-search-input');
  if (!input) return;

  let debounceTimer;
  input.addEventListener('input', (e) => {
    clearTimeout(debounceTimer);
    const query = e.target.value.trim();
    if (query.length < 2) return;

    debounceTimer = setTimeout(async () => {
      // Endpoint handles search
      console.log('Searching for:', query);
    }, 300);
  });
}
