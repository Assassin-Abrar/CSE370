// Fetch helpers + generic ajax-form handling + toasts
(function () {
  'use strict';

  // Catch-all: log ANY uncaught JS error or unhandled promise rejection on the
  // page, loudly, in case the real problem isn't in the fetch code at all.
  window.addEventListener('error', (e) => {
    console.error(
      '%c[BRACU CMS] Uncaught JS error — copy this and send it back',
      'color:#fff;background:#b45309;padding:2px 6px;border-radius:4px;font-weight:bold;',
      '\nMessage:', e.message, '\nFile:', e.filename, '\nLine:', e.lineno + ':' + e.colno,
      '\nError object:', e.error
    );
  });
  window.addEventListener('unhandledrejection', (e) => {
    console.error(
      '%c[BRACU CMS] Unhandled promise rejection — copy this and send it back',
      'color:#fff;background:#b45309;padding:2px 6px;border-radius:4px;font-weight:bold;',
      '\nReason:', e.reason
    );
  });

  function getCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function dumpFormData(fd) {
    if (!(fd instanceof FormData)) return fd;
    const out = {};
    for (const [k, v] of fd.entries()) out[k] = (v instanceof File) ? `[File: ${v.name}]` : v;
    return out;
  }

  async function postJSON(url, data) {
    // Always send as multipart FormData (never raw JSON) — every api/*.php endpoint
    // reads $_POST, which PHP only populates from form-encoded/multipart bodies.
    let body;
    if (data instanceof FormData) {
      body = data;
    } else {
      body = new FormData();
      Object.entries(data || {}).forEach(([k, v]) => body.append(k, v));
    }
    if (!body.has('csrf_token')) body.append('csrf_token', getCsrf());
    const opts = {
      method: 'POST',
      body,
      headers: { 'X-CSRF-Token': getCsrf() },
    };
    let res, raw;
    try {
      res = await fetch(url, opts);
      raw = await res.text();
    } catch (networkErr) {
      console.error(
        '%c[BRACU CMS] Network-level failure (request never got a response)',
        'color:#fff;background:#dc2626;padding:2px 6px;border-radius:4px;font-weight:bold;',
        '\nURL:', url, '\nFields sent:', dumpFormData(body), '\nError:', networkErr
      );
      return { ok: false, message: 'Network error — the request never reached the server: ' + networkErr.message };
    }
    let json;
    try {
      json = JSON.parse(raw);
    } catch (err) {
      // Surface exactly what the server sent back instead of a generic message —
      // this is what actually lets us diagnose a non-JSON response without DevTools.
      const headerDump = {};
      res.headers.forEach((v, k) => { headerDump[k] = v; });
      const snippet = raw.trim() === '' ? '(empty response)' : raw.trim().slice(0, 300);
      console.error(
        '%c[BRACU CMS] Non-JSON response — copy everything below and send it back',
        'color:#fff;background:#dc2626;padding:2px 6px;border-radius:4px;font-weight:bold;',
        '\nURL:', url,
        '\nFields sent:', dumpFormData(body),
        '\nHTTP status:', res.status, res.statusText,
        '\nResponse headers:', headerDump,
        '\nRaw body (full):', raw
      );
      json = { ok: false, message: 'Server returned an invalid response (HTTP ' + res.status + '): ' + snippet };
    }
    return json;
  }
  async function getJSON(url) {
    const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
    return res.json();
  }
  window.postJSON = postJSON;
  window.getJSON = getJSON;

  // Blocks every button/link/form on the page the instant a reload/redirect
  // is scheduled, so a second click during the delay can't fire a request
  // that gets cut off mid-flight by the navigation (which is what produces
  // a garbled/incomplete response on the client).
  window.blockPageForReload = function () {
    if (document.getElementById('reloadBlockOverlay')) return;
    const overlay = document.createElement('div');
    overlay.id = 'reloadBlockOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;cursor:wait;background:transparent;';
    overlay.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); });
    document.body.appendChild(overlay);
  };

  // ---------- Toasts ----------
  function ensureStack() {
    let stack = document.querySelector('.toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'toast-stack';
      document.body.appendChild(stack);
    }
    return stack;
  }
  window.showToast = function (type, message) {
    const stack = ensureStack();
    const t = document.createElement('div');
    t.className = 'toast ' + (type || '');
    const icon = { success: '✓', error: '✕', warning: '⚠' }[type] || 'ℹ';
    t.innerHTML = `<div>${icon}</div><div class="msg">${message}</div><button class="close" aria-label="Dismiss">✕</button>`;
    t.querySelector('.close').addEventListener('click', () => t.remove());
    stack.appendChild(t);
    setTimeout(() => t.remove(), 5000);
  };

  // ---------- Generic ajax form handler ----------
  async function handleAjaxForm(form) {
    if (form.dataset.confirm) {
      const ok = await window.confirmAction(form.dataset.confirm, form.dataset.confirmLabel);
      if (!ok) return;
    }
    const submitBtn = form.querySelector('[type="submit"]');
    const originalHtml = submitBtn ? submitBtn.innerHTML : null;
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner"></span> ' + (form.dataset.loadingLabel || 'Saving...');
    }
    const clearErrors = () => form.querySelectorAll('.field .error').forEach(e => e.remove());
    clearErrors();
    try {
      const fd = new FormData(form);
      // NOTE: form.action is unsafe here — if the form has a field named
      // "action" (several of ours do, e.g. approve/reject), the browser lets
      // that field SHADOW the form element's built-in .action property, so
      // form.action returns the <input> DOM node instead of the submit URL.
      // getAttribute() reads the literal HTML attribute and is immune to this.
      const submitUrl = form.getAttribute('action');
      const json = await postJSON(submitUrl, fd);
      if (json.ok) {
        showToast('success', json.message || 'Done.');
        form.dispatchEvent(new CustomEvent('ajaxsuccess', { detail: json }));
        if (json.reload) { blockPageForReload(); setTimeout(() => location.reload(), 600); }
        if (json.redirect) { blockPageForReload(); setTimeout(() => { location.href = json.redirect; }, 400); }
        if (json.reset) form.reset();
        if (json.closeModal) { const b = form.closest('.modal-backdrop'); if (b) b.classList.remove('open'); }
      } else {
        showToast('error', json.message || 'Something went wrong.');
        if (json.errors) {
          Object.entries(json.errors).forEach(([field, msg]) => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
              const wrap = input.closest('.field') || input.parentElement;
              const err = document.createElement('div');
              err.className = 'error';
              err.textContent = msg;
              wrap.appendChild(err);
              input.classList.add('invalid');
            }
          });
        }
        form.dispatchEvent(new CustomEvent('ajaxerror', { detail: json }));
      }
    } catch (err) {
      showToast('error', 'Network error. Please try again.');
    } finally {
      if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = originalHtml; }
    }
  }

  document.addEventListener('submit', (e) => {
    if (e.target.classList && e.target.classList.contains('ajax-form')) {
      e.preventDefault();
      handleAjaxForm(e.target);
    }
  });

  // ---------- Quick ajax action buttons: data-ajax-post="url" data-ajax-body='{"id":1}' ----------
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-ajax-post]');
    if (!btn) return;
    e.preventDefault();
    if (btn.dataset.confirm) {
      const ok = await window.confirmAction(btn.dataset.confirm, btn.dataset.confirmLabel, btn.dataset.confirmClass);
      if (!ok) return;
    }
    const url = btn.getAttribute('data-ajax-post');
    let body = {};
    try { body = btn.dataset.ajaxBody ? JSON.parse(btn.dataset.ajaxBody) : {}; } catch (e) {}
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner dark"></span>';
    const json = await postJSON(url, body);
    btn.disabled = false;
    btn.innerHTML = original;
    if (json.ok) {
      showToast('success', json.message || 'Done.');
      btn.dispatchEvent(new CustomEvent('ajaxsuccess', { detail: json, bubbles: true }));
      if (json.reload) { blockPageForReload(); setTimeout(() => location.reload(), 500); }
      if (json.redirect) { blockPageForReload(); setTimeout(() => { location.href = json.redirect; }, 400); }
      if (json.removeSelector) document.querySelectorAll(json.removeSelector).forEach(el => el.remove());
    } else {
      showToast('error', json.message || 'Something went wrong.');
    }
  });

  // Render flash messages queued server-side (in #flashData script tag)
  document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('flashData');
    if (el) {
      try {
        const flashes = JSON.parse(el.textContent);
        flashes.forEach(f => showToast(f.type, f.message));
      } catch (e) {}
    }
  });
})();
