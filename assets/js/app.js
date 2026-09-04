// Core UI interactions: mobile nav, modals, tabs, dropdowns, confirm dialog
(function () {
  'use strict';

  // ---------- Theme toggle ----------
  function currentTheme() {
    return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
  }
  function syncThemeIcons() {
    const isDark = currentTheme() === 'dark';
    document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
      btn.innerHTML = isDark
        ? '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>'
        : '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
      btn.setAttribute('aria-label', isDark ? 'Switch to light theme' : 'Switch to dark theme');
      btn.setAttribute('title', isDark ? 'Switch to light theme' : 'Switch to dark theme');
    });
  }
  syncThemeIcons();
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-theme-toggle]');
    if (!btn) return;
    const next = currentTheme() === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem('bracu_theme', next); } catch (err) {}
    syncThemeIcons();
  });

  // ---------- Mobile sidebar ----------
  const sidebar = document.querySelector('.sidebar');
  const scrim = document.querySelector('.sidebar-scrim');
  document.querySelectorAll('[data-menu-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
      sidebar && sidebar.classList.toggle('open');
      scrim && scrim.classList.toggle('open');
    });
  });
  scrim && scrim.addEventListener('click', () => {
    sidebar.classList.remove('open');
    scrim.classList.remove('open');
  });

  // ---------- Modals ----------
  function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
  }
  function closeModal(el) {
    const backdrop = el.closest('.modal-backdrop');
    if (backdrop) backdrop.classList.remove('open');
  }
  window.openModal = openModal;
  window.closeModal = closeModal;

  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-open-modal]');
    if (opener) { openModal(opener.getAttribute('data-open-modal')); }
    const closer = e.target.closest('[data-close-modal]');
    if (closer) { closeModal(closer); }
    if (e.target.classList && e.target.classList.contains('modal-backdrop')) {
      e.target.classList.remove('open');
    }
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-backdrop.open').forEach(m => m.classList.remove('open'));
    }
  });

  // ---------- Tabs ----------
  document.querySelectorAll('[data-tabs]').forEach(tabGroup => {
    const targetSel = tabGroup.getAttribute('data-tabs');
    const panels = document.querySelectorAll(targetSel + ' [data-tab-panel]');
    tabGroup.querySelectorAll('[data-tab]').forEach(tabBtn => {
      tabBtn.addEventListener('click', (e) => {
        e.preventDefault();
        tabGroup.querySelectorAll('[data-tab]').forEach(b => b.classList.remove('active'));
        tabBtn.classList.add('active');
        const key = tabBtn.getAttribute('data-tab');
        panels.forEach(p => {
          p.style.display = (p.getAttribute('data-tab-panel') === key) ? '' : 'none';
        });
      });
    });
  });

  // ---------- Notification dropdown ----------
  document.querySelectorAll('[data-dropdown-toggle]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const panel = document.getElementById(btn.getAttribute('data-dropdown-toggle'));
      const isOpen = panel.classList.contains('open');
      document.querySelectorAll('.dropdown-panel.open').forEach(p => p.classList.remove('open'));
      if (!isOpen) panel.classList.add('open');
    });
  });
  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-panel.open').forEach(p => p.classList.remove('open'));
  });

  // ---------- Confirm dialog (Promise-based) ----------
  function ensureConfirmModal() {
    if (document.getElementById('globalConfirmModal')) return;
    const div = document.createElement('div');
    div.className = 'modal-backdrop';
    div.id = 'globalConfirmModal';
    div.innerHTML = `
      <div class="modal" style="max-width:400px">
        <div class="modal-body" style="padding-top:24px">
          <h3 id="confirmTitle" style="margin-bottom:8px">Are you sure?</h3>
          <p class="text-muted text-sm" id="confirmMessage"></p>
        </div>
        <div class="modal-foot">
          <button class="btn" id="confirmCancelBtn">Cancel</button>
          <button class="btn btn-danger" id="confirmOkBtn">Confirm</button>
        </div>
      </div>`;
    document.body.appendChild(div);
  }
  window.confirmAction = function (message, okLabel, okClass) {
    ensureConfirmModal();
    return new Promise((resolve) => {
      const modal = document.getElementById('globalConfirmModal');
      modal.querySelector('#confirmMessage').textContent = message || 'This action cannot be undone.';
      const okBtn = modal.querySelector('#confirmOkBtn');
      okBtn.textContent = okLabel || 'Confirm';
      okBtn.className = 'btn ' + (okClass || 'btn-danger');
      modal.classList.add('open');
      const cleanup = (result) => {
        modal.classList.remove('open');
        okBtn.removeEventListener('click', onOk);
        cancelBtn.removeEventListener('click', onCancel);
        resolve(result);
      };
      const onOk = () => cleanup(true);
      const onCancel = () => cleanup(false);
      const cancelBtn = modal.querySelector('#confirmCancelBtn');
      okBtn.addEventListener('click', onOk);
      cancelBtn.addEventListener('click', onCancel);
    });
  };
})();
