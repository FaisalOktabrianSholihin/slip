/* =========================================================
   js/slip-common.js
   Data & komponen bersama untuk alur Kirim Slip Gaji.
   Dipakai oleh: kirim-slip.html, preview-slip.html, detail-riwayat.html,
   pengaturan.html, log-aktivitas.html.

   SlipStore sekarang tersambung ke backend Laravel sungguhan lewat
   window.Api (public/js/api-client.js):
     - Import Excel  -> POST /api/slip/import       (SlipController@importPayroll)
     - Preview       -> GET  /api/slip/preview      (SlipController@preview)
     - Kirim         -> POST /api/slip/kirim-semua | /api/slip/kirim-divisi/{d}
     - Riwayat       -> GET  /api/riwayat           (RiwayatController)
     - Pengaturan    -> GET/PUT /api/pengaturan     (SettingController)
     - Log aktivitas -> GET/POST /api/log-aktivitas (ActivityLogController)

   Karena semua fungsi ini sekarang memanggil server, SEMUANYA
   mengembalikan Promise. Pemanggil (kirim-slip.js, preview-slip.js,
   dst) memakai async/await atau .then().
   ========================================================= */
(function (global) {
  'use strict';

  // 2026-09-19 -> 19-09-2026
  function formatIso(iso) {
    const p = String(iso || '').split('-');
    return p.length === 3 ? p[2] + '-' + p[1] + '-' + p[0] : (iso || '-');
  }

  const esc = (str) => String(str).replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  /* =========================================================
     Pengaturan
     ========================================================= */
  function getSettings() {
    return Api.get('/api/pengaturan');
  }

  function saveSettings(s) {
    return Api.put('/api/pengaturan', s);
  }

  /* Tidak ada job retensi otomatis di server pada paket ini -
     endpoint pengaturan sudah menyimpan kebijakannya, tapi
     pembersihan data lampau perlu dijadwalkan terpisah (mis. lewat
     Laravel Scheduler) bila dibutuhkan. Dibiarkan no-op supaya
     tombol "Terapkan retensi sekarang" di UI tidak error. */
  function cleanupStoredData() {
    return Promise.resolve();
  }

  /* =========================================================
     Log Aktivitas
     ========================================================= */
  function getActivityLogs() {
    return Api.get('/api/log-aktivitas');
  }

  function addActivity(action, detail) {
    return Api.post('/api/log-aktivitas', { action, detail }).catch(() => null);
  }

  /* =========================================================
     Kirim Slip Gaji: import, preview, riwayat, kirim
     ========================================================= */

  /* rows = [{ nik, gajiPokok, tambahan:[{nama,jumlah}], potongan:[{nama,jumlah}] }] */
  function importPayroll(periode, rows) {
    return Api.post('/api/slip/import', { periode, rows });
  }

  function getPreviewRows() {
    return Api.get('/api/slip/preview');
  }

  function getHistory() {
    return Api.get('/api/riwayat');
  }

  /* Kirim seluruh slip siap_kirim pada satu channel (email/wa),
     opsional dibatasi ke satu divisi saja. */
  function sendChannel(opts) {
    const channel = opts.channel;
    const qs = '?channel=' + encodeURIComponent(channel);
    const url = opts.all
      ? '/api/slip/kirim-semua' + qs
      : '/api/slip/kirim-divisi/' + encodeURIComponent(opts.divisi) + qs;
    return Api.post(url);
  }

  global.SlipStore = {
    getSettings: getSettings,
    saveSettings: saveSettings,
    cleanupStoredData: cleanupStoredData,
    getActivityLogs: getActivityLogs,
    addActivity: addActivity,
    importPayroll: importPayroll,
    getPreviewRows: getPreviewRows,
    getHistory: getHistory,
    sendChannel: sendChannel,
    formatIso: formatIso
  };

  /* =========================================================
     SlipUI: toast & dialog (memakai class ks-* dari kirim-slip.css)
     ========================================================= */
  let toastsEl = null;
  let dlgSeq = 0;

  function getToasts() {
    if (toastsEl && document.body.contains(toastsEl)) return toastsEl;
    toastsEl = document.getElementById('ksToasts');
    if (!toastsEl) {
      toastsEl = document.createElement('div');
      toastsEl.id = 'ksToasts';
      toastsEl.className = 'ks-toasts';
      toastsEl.setAttribute('role', 'status');
      toastsEl.setAttribute('aria-live', 'polite');
      document.body.appendChild(toastsEl);
    }
    return toastsEl;
  }

  // type: 'success' (default) | 'error' | 'info'
  function toast(message, type) {
    const icon = type === 'error' ? 'bi-exclamation-circle-fill'
      : type === 'info' ? 'bi-info-circle-fill' : 'bi-check-circle-fill';
    const el = document.createElement('div');
    el.className = 'ks-toast' + (type === 'error' ? ' ks-toast--error' : '');
    el.innerHTML = '<i class="bi ' + icon + '"></i><span>' + esc(message) + '</span>';
    getToasts().appendChild(el);
    setTimeout(() => {
      el.style.transition = 'opacity .2s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 220);
    }, 3400);
  }

  /* Dialog modal. opts = { title, html, buttons: [{ label, kind, value }] }
     kind: 'primary' | 'outline'.  html HARUS sudah di-escape oleh pemanggil.
     Mengembalikan Promise berisi value tombol yang ditekan,
     atau null bila ditutup lewat X / klik latar / Esc. */
  function dialog(opts) {
    return new Promise((resolve) => {
      const uid = 'ksDlgTitle' + (++dlgSeq);
      const buttons = opts.buttons && opts.buttons.length
        ? opts.buttons
        : [{ label: 'Tutup', kind: 'outline', value: 'close' }];

      const el = document.createElement('div');
      el.className = 'ks-modal';
      el.hidden = true;
      el.innerHTML =
        '<div class="ks-modal__backdrop" data-close></div>' +
        '<div class="ks-modal__dialog ks-modal__dialog--sm" role="dialog" aria-modal="true" aria-labelledby="' + uid + '">' +
          '<div class="ks-modal__head">' +
            '<h2 id="' + uid + '">' + esc(opts.title || '') + '</h2>' +
            '<button type="button" class="ks-modal__close" data-close aria-label="Tutup"><i class="bi bi-x-lg"></i></button>' +
          '</div>' +
          '<div class="ks-modal__body">' + (opts.html || '') + '</div>' +
          '<div class="ks-modal__foot">' +
            buttons.map((b) =>
              '<button type="button" class="ks-btn ks-btn--' + (b.kind === 'primary' ? 'primary' : 'outline') +
              '" data-value="' + esc(b.value) + '">' + esc(b.label) + '</button>'
            ).join('') +
          '</div>' +
        '</div>';
      document.body.appendChild(el);

      const lastFocus = document.activeElement;
      let done = false;

      function finish(value) {
        if (done) return;
        done = true;
        document.removeEventListener('keydown', onKey, true);
        el.classList.remove('is-open');
        document.body.classList.remove('ks-lock');
        setTimeout(() => el.remove(), 160);
        if (lastFocus && document.body.contains(lastFocus)) lastFocus.focus();
        resolve(value);
      }

      function onKey(e) {
        if (e.key === 'Escape') {
          e.preventDefault();
          e.stopPropagation();
          finish(null);
          return;
        }
        if (e.key === 'Tab') {   // jaga fokus tetap di dalam dialog
          const f = Array.from(el.querySelectorAll('button:not([disabled])'));
          if (!f.length) return;
          const first = f[0];
          const last = f[f.length - 1];
          if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
          else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
      }

      el.addEventListener('click', (e) => {
        const b = e.target.closest('[data-value]');
        if (b) { finish(b.getAttribute('data-value')); return; }
        if (e.target.closest('[data-close]')) finish(null);
      });

      document.addEventListener('keydown', onKey, true);

      el.hidden = false;
      document.body.classList.add('ks-lock');
      requestAnimationFrame(() => el.classList.add('is-open'));

      const focusTarget = el.querySelector('.ks-btn--primary') || el.querySelector('[data-value]') || el.querySelector('[data-close]');
      if (focusTarget) setTimeout(() => focusTarget.focus(), 30);
    });
  }

  /* Isi dialog ringkasan (jumlah + daftar yang gagal).
     o = { lead, total, ok, fail, labels: [total, ok, gagal],
           itemsTitle, items: [{ title, sub }], note } */
  function summaryHtml(o) {
    const labels = o.labels || ['Total', 'Berhasil', 'Gagal'];
    const items = o.items || [];
    let h = '<p class="ks-result__lead">' + esc(o.lead) + '</p>';

    h += '<div class="ks-stats">' +
      '<div class="ks-stat"><span class="ks-stat__num">' + o.total + '</span><span class="ks-stat__label">' + esc(labels[0]) + '</span></div>' +
      '<div class="ks-stat ks-stat--ok"><span class="ks-stat__num">' + o.ok + '</span><span class="ks-stat__label">' + esc(labels[1]) + '</span></div>' +
      '<div class="ks-stat ks-stat--fail' + (o.fail > 0 ? ' has-fail' : '') + '"><span class="ks-stat__num">' + o.fail + '</span><span class="ks-stat__label">' + esc(labels[2]) + '</span></div>' +
      '</div>';

    if (items.length) {
      h += '<div><h3 class="ks-result__sub">' + esc(o.itemsTitle || 'Yang gagal') + '</h3><ul class="ks-fails">' +
        items.map((it) =>
          '<li><i class="bi bi-x-circle-fill" aria-hidden="true"></i><div><strong>' + esc(it.title) + '</strong><span>' + esc(it.sub) + '</span></div></li>'
        ).join('') + '</ul></div>';
    }

    if (o.note) h += '<p class="ks-hint">' + esc(o.note) + '</p>';
    return h;
  }

  global.SlipUI = {
    esc: esc,
    toast: toast,
    dialog: dialog,
    summaryHtml: summaryHtml
  };
})(window);
