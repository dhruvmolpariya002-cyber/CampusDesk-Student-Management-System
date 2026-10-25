/**
 * CampusDesk — Frontend JavaScript Helpers
 * File: assets/js/app.js
 */

document.addEventListener('DOMContentLoaded', function () {
  // Sidebar toggle handler
  const sidebarToggle = document.getElementById('sidebarToggle');
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function (e) {
      e.preventDefault();
      document.body.classList.toggle('sb-sidenav-toggled');
      const wrapper = document.getElementById('wrapper');
      if (wrapper) {
        wrapper.classList.toggle('toggled');
      }
    });
  }

  // Delete confirmation modals / prompts
  const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
  deleteButtons.forEach(btn => {
    btn.addEventListener('click', function (e) {
      const message = this.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this record? This action cannot be undone.';
      if (!confirm(message)) {
        e.preventDefault();
      }
    });
  });

  // Client-side quick filter for data tables
  const searchInput = document.getElementById('tableSearchInput');
  if (searchInput) {
    searchInput.addEventListener('keyup', function () {
      const filter = this.value.toLowerCase().trim();
      const rows = document.querySelectorAll('.table-cd tbody tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
      });
    });
  }

  // Auto-dismiss alerts after 5 seconds
  const autoAlerts = document.querySelectorAll('.alert-dismissible');
  autoAlerts.forEach(alert => {
    setTimeout(() => {
      const closeBtn = alert.querySelector('.btn-close');
      if (closeBtn) {
        closeBtn.click();
      }
    }, 5000);
  });
});
