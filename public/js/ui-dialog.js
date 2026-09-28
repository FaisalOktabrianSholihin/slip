/* =========================================================
   js/ui-dialog.js
   Pengganti alert() / confirm() bawaan browser (yang tampil sebagai
   "127.0.0.1:8000 says ...") dengan pop up front-end.

   Dipakai di seluruh halaman (layouts/app + layouts/guest):

     UI.alert('Pesan', { type: 'error', title: 'Gagal' })  -> Promise<void>
     UI.confirm('Yakin?', { danger: true })                -> Promise<boolean>
     UI.toast('Tersimpan.')  /  UI.toast('Gagal', 'error')

   Tambahan:
     window.showToast(message, isError)  -> dipakai data-user/divisi/jabatan
     window.alert = ...                  -> alert() lama otomatis jadi pop up
                                            front-end (tidak memblokir halaman).
   NB: confirm() tidak bisa diganti otomatis karena harus menunggu jawaban,
   jadi pemakaiannya diubah menjadi: UI.confirm(...).then(function (ok) {...})
   ========================================================= */
(function () {
  'use strict';
  if (window.UI && window.UI.__ready) return;

  var TYPES = {
    info: { icon: 'bi-info-circle-fill', title: 'Informasi' },
    success: { icon: 'bi-check-circle-fill', title: 'Berhasil' },
    error: { icon: 'bi-x-circle-fill', title: 'Terjadi Kesalahan' },
    warning: { icon: 'bi-exclamation-triangle-fill', title: 'Perhatian' }
  };

  var CSS = '' +
    '.uid-overlay{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(16,32,24,.45);backdrop-filter:blur(2px);opacity:0;transition:opacity .16s ease;font-family:"Plus Jakarta Sans","Inter","Segoe UI",system-ui,sans-serif}' +
    '.uid-overlay.uid-show{opacity:1}' +
    '.uid-box{width:min(420px,100%);background:#fff;border-radius:16px;box-shadow:0 30px 70px rgba(0,0,0,.25);padding:26px 24px 20px;text-align:center;transform:translateY(8px) scale(.98);transition:transform .16s ease;color:#293b4e}' +
    '.uid-overlay.uid-show .uid-box{transform:none}' +
    '.uid-icon{width:54px;height:54px;border-radius:50%;margin:0 auto 12px;display:grid;place-items:center;font-size:26px}' +
    '.uid-info .uid-icon{background:#e3f1fb;color:#1b7bbf}' +
    '.uid-success .uid-icon{background:#dff1e4;color:#078b4c}' +
    '.uid-error .uid-icon{background:#fbe6e6;color:#e24545}' +
    '.uid-warning .uid-icon{background:#fbeeda;color:#d98c1f}' +
    '.uid-title{margin:0 0 6px;font-size:17px;font-weight:800;color:#1b2c3d}' +
    '.uid-msg{margin:0;font-size:14px;line-height:1.55;color:#536477;white-space:pre-line;word-break:break-word}' +
    '.uid-actions{display:flex;gap:10px;justify-content:center;margin-top:20px}' +
    '.uid-btn{flex:1;max-width:170px;padding:10px 16px;border-radius:10px;border:1px solid #dfe6ed;background:#fff;color:#293b4e;font:inherit;font-size:14px;font-weight:700;cursor:pointer;transition:filter .12s,background .12s}' +
    '.uid-btn:hover{background:#f4f7f5}' +
    '.uid-btn:focus-visible{outline:3px solid rgba(7,139,76,.35);outline-offset:1px}' +
    '.uid-btn.uid-primary{background:#078b4c;border-color:#078b4c;color:#fff}' +
    '.uid-btn.uid-primary:hover{background:#06793f}' +
    '.uid-btn.uid-danger{background:#e24545;border-color:#e24545;color:#fff}' +
    '.uid-btn.uid-danger:hover{background:#cc3a3a}' +
    '.uid-toasts{position:fixed;top:18px;right:18px;z-index:100001;display:flex;flex-direction:column;gap:10px;max-width:min(380px,calc(100vw - 36px));font-family:"Plus Jakarta Sans","Inter","Segoe UI",system-ui,sans-serif}' +
    '.uid-toast{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#fff;border-left:4px solid #078b4c;border-radius:10px;box-shadow:0 12px 30px rgba(0,0,0,.18);font-size:13.5px;line-height:1.45;color:#293b4e;opacity:0;transform:translateX(14px);transition:opacity .18s,transform .18s}' +
    '.uid-toast.uid-show{opacity:1;transform:none}' +
    '.uid-toast i{font-size:17px;color:#078b4c;margin-top:1px}' +
    '.uid-toast.uid-error{border-left-color:#e24545}.uid-toast.uid-error i{color:#e24545}' +
    '.uid-toast span{white-space:pre-line;word-break:break-word}';

  function injectCss() {
    if (document.getElementById('uid-style')) return;
    var st = document.createElement('style');
    st.id = 'uid-style';
    st.textContent = CSS;
    (document.head || document.documentElement).appendChild(st);
  }

  function esc(v) {
    return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /* Dialog dasar: mengembalikan Promise<boolean> (true = tombol utama). */
  function open(o) {
    injectCss();
    return new Promise(function (resolve) {
      var t = TYPES[o.type] || TYPES.info;
      var prevFocus = document.activeElement;
      var ov = document.createElement('div');
      ov.className = 'uid-overlay uid-' + (TYPES[o.type] ? o.type : 'info');

      var buttons = '';
      if (o.cancelText) buttons += '<button type="button" class="uid-btn" data-uid="0">' + esc(o.cancelText) + '</button>';
      buttons += '<button type="button" class="uid-btn ' + (o.danger ? 'uid-danger' : 'uid-primary') + '" data-uid="1">' + esc(o.okText || 'OK') + '</button>';

      ov.innerHTML =
        '<div class="uid-box" role="alertdialog" aria-modal="true">' +
        '<div class="uid-icon"><i class="bi ' + t.icon + '"></i></div>' +
        '<h3 class="uid-title">' + esc(o.title || t.title) + '</h3>' +
        '<p class="uid-msg">' + esc(o.message) + '</p>' +
        '<div class="uid-actions">' + buttons + '</div>' +
        '</div>';

      function done(val) {
        document.removeEventListener('keydown', onKey, true);
        ov.classList.remove('uid-show');
        setTimeout(function () { ov.remove(); }, 160);
        if (prevFocus && prevFocus.focus) { try { prevFocus.focus(); } catch (_) { } }
        resolve(val);
      }
      function onKey(e) {
        if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); done(false); }
        else if (e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); done(true); }
      }

      ov.addEventListener('click', function (e) {
        var b = e.target.closest('[data-uid]');
        if (b) done(b.getAttribute('data-uid') === '1');
        else if (e.target === ov && o.cancelText) done(false);
      });
      document.addEventListener('keydown', onKey, true);
      document.body.appendChild(ov);
      requestAnimationFrame(function () { ov.classList.add('uid-show'); });
      var okBtn = ov.querySelector('[data-uid="1"]');
      // Untuk konfirmasi hapus, fokus awal ke Batal supaya tidak terhapus tak sengaja.
      var first = (o.danger && o.cancelText) ? ov.querySelector('[data-uid="0"]') : okBtn;
      if (first) first.focus();
    });
  }

  function uiAlert(message, opts) {
    opts = opts || {};
    return open({
      message: message, type: opts.type || 'info', title: opts.title,
      okText: opts.okText || 'OK'
    }).then(function () { });
  }

  function uiConfirm(message, opts) {
    opts = opts || {};
    return open({
      message: message, type: opts.type || (opts.danger ? 'warning' : 'info'),
      title: opts.title || 'Konfirmasi', danger: !!opts.danger,
      okText: opts.okText || (opts.danger ? 'Ya, Hapus' : 'Ya'),
      cancelText: opts.cancelText || 'Batal'
    });
  }

  var toastBox = null;
  function toast(message, type) {
    injectCss();
    if (!toastBox || !document.body.contains(toastBox)) {
      toastBox = document.createElement('div');
      toastBox.className = 'uid-toasts';
      toastBox.setAttribute('role', 'status');
      toastBox.setAttribute('aria-live', 'polite');
      document.body.appendChild(toastBox);
    }
    var isErr = type === 'error' || type === true;
    var el = document.createElement('div');
    el.className = 'uid-toast' + (isErr ? ' uid-error' : '');
    el.innerHTML = '<i class="bi ' + (isErr ? 'bi-x-circle-fill' : 'bi-check-circle-fill') + '"></i><span>' + esc(message) + '</span>';
    toastBox.appendChild(el);
    requestAnimationFrame(function () { el.classList.add('uid-show'); });
    setTimeout(function () {
      el.classList.remove('uid-show');
      setTimeout(function () { el.remove(); }, 200);
    }, isErr ? 5000 : 3200);
  }

  window.UI = { alert: uiAlert, confirm: uiConfirm, toast: toast, __ready: true };

  // Dipakai data-user.js, data-divisi.js, data-jabatan.js (sebelumnya tidak pernah terdefinisi).
  window.showToast = function (message, isError) { toast(message, isError ? 'error' : 'success'); };

  // Jaring pengaman: alert() lama di mana pun otomatis jadi pop up front-end.
  var ERR = /gagal|tidak dapat|tidak bisa|salah|error|wajib|tidak boleh|tidak valid/i;
  window.alert = function (message) {
    var text = String(message == null ? '' : message);
    uiAlert(text, { type: ERR.test(text) ? 'error' : 'info' });
  };
})();
