/* =========================================================
   js/import-runner.js  (dimuat di SEMUA halaman lewat layouts/app)

   Menjalankan import gaji di latar belakang.
   - Data import disimpan di server (tabel import_jobs), bukan di halaman.
   - Skrip ini memanggil POST /api/slip/import-jobs/{id}/proses berulang
     kali (10 baris per panggilan) sampai selesai.
   - Saat pengguna pindah menu, halaman baru memuat skrip ini lagi, membaca
     job yang masih berjalan, lalu MELANJUTKAN dari baris terakhir.
   - Di halaman selain Kirim Slip tampil kartu progres di pojok kanan bawah.
   - Halaman Kirim Slip memakai ImportRunner.subscribe() untuk tampilannya.
   ========================================================= */
(function () {
  'use strict';
  if (window.ImportRunner || !window.Api) return;

  var API = '/api/slip/import-jobs';
  var listeners = [];
  var job = null;
  var running = false;
  var stopped = false;
  var loaded = false;
  var onKirimSlip = document.body && document.body.getAttribute('data-nav') === 'kirim-slip';

  function emit() { listeners.forEach(function (fn) { try { fn(job); } catch (e) { console.error(e); } }); renderBanner(); }
  function sleep(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }

  /* ---------- Loop pemrosesan ---------- */
  async function loop() {
    if (running) return;
    running = true;
    var gagalBeruntun = 0;
    try {
      while (job && job.status === 'berjalan' && !stopped) {
        try {
          var res = await Api.post(API + '/' + job.id + '/proses');
          gagalBeruntun = 0;
          job = res.job;
          emit();
          if (res.sibuk) await sleep(2500);       // tab/halaman lain sedang memproses batch
          else await sleep(50);
        } catch (err) {
          if (err && err.status === 403 || err && err.status === 404) { job = null; emit(); break; }
          gagalBeruntun++;
          if (gagalBeruntun >= 8) { console.error('Import terhenti:', err); break; }
          await sleep(Math.min(1500 * gagalBeruntun, 8000)); // coba lagi (jaringan/server sibuk)
        }
      }
    } finally {
      running = false;
    }
    if (job && job.status === 'berjalan' && gagalBeruntun >= 8) { job.terputus = true; emit(); }
  }

  function refresh() {
    return Api.get(API + '/aktif').then(function (res) {
      job = res && res.job ? res.job : null;
      loaded = true;
      emit();
      if (job && job.status === 'berjalan') loop();
      return job;
    }).catch(function () { loaded = true; return null; }); // user tanpa izin: abaikan
  }

  /* ---------- API publik ---------- */
  window.ImportRunner = {
    get current() { return job; },
    get loaded() { return loaded; },
    subscribe: function (fn) { listeners.push(fn); if (loaded) fn(job); },
    start: function (payload) {
      return Api.post(API, payload).then(function (res) { job = res.job; emit(); loop(); return job; });
    },
    resume: function () { if (job) { delete job.terputus; emit(); loop(); } },
    cancel: function () {
      if (!job) return Promise.resolve();
      return Api.post(API + '/' + job.id + '/batal').then(function (res) { job = res.job; emit(); });
    },
    dismiss: function () {
      if (!job || job.status === 'berjalan') return Promise.resolve();
      var id = job.id;
      job = null; emit();
      return Api.post(API + '/' + id + '/dilihat').catch(function () { });
    }
  };

  /* ---------- Kartu progres (halaman selain Kirim Slip) ---------- */
  var box = null;
  function injectCss() {
    if (document.getElementById('irCss')) return;
    var st = document.createElement('style'); st.id = 'irCss';
    st.textContent =
      '.ir-box{position:fixed;right:20px;bottom:20px;z-index:99990;width:min(340px,calc(100vw - 32px));background:#fff;border:1px solid #cfe5d8;border-radius:14px;box-shadow:0 14px 40px rgba(0,0,0,.18);padding:14px 16px;font-family:"Plus Jakarta Sans","Segoe UI",system-ui,sans-serif;color:#1f3a2c}' +
      '.ir-head{display:flex;align-items:center;gap:8px;font-weight:800;font-size:.9rem}' +
      '.ir-head i{color:#078b4c}.ir-spin{animation:ir-spin .9s linear infinite;display:inline-block}' +
      '@keyframes ir-spin{to{transform:rotate(360deg)}}' +
      '.ir-sub{margin:6px 0 8px;font-size:.8rem;color:#5b6f63;line-height:1.45}' +
      '.ir-bar{height:8px;border-radius:99px;background:#e3efe8;overflow:hidden}.ir-bar>span{display:block;height:100%;background:#078b4c;transition:width .25s ease}' +
      '.ir-actions{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}' +
      '.ir-btn{border:1px solid #b6dcc3;background:#fff;color:#0c5a38;border-radius:9px;padding:6px 12px;font-size:.8rem;font-weight:700;cursor:pointer;text-decoration:none}' +
      '.ir-btn--p{background:#078b4c;border-color:#078b4c;color:#fff}' +
      '.ir-box.ir-warn{border-color:#f0d19a}.ir-box.ir-warn .ir-head i{color:#d98c1f}';
    document.head.appendChild(st);
  }
  function renderBanner() {
    if (onKirimSlip) { if (box) { box.remove(); box = null; } return; }
    if (!job) { if (box) { box.remove(); box = null; } return; }
    injectCss();
    if (!box) { box = document.createElement('div'); box.className = 'ir-box'; document.body.appendChild(box); }
    var pct = job.total ? Math.round(job.processed / job.total * 100) : 0;
    var html;
    if (job.status === 'berjalan') {
      box.className = 'ir-box' + (job.terputus ? ' ir-warn' : '');
      html = '<div class="ir-head"><i class="bi ' + (job.terputus ? 'bi-exclamation-triangle-fill' : 'bi-arrow-repeat ir-spin') + '"></i> Import gaji ' + (job.terputus ? 'terhenti' : 'sedang berjalan') + '</div>' +
        '<div class="ir-sub">' + job.processed + ' dari ' + job.total + ' data diproses (' + pct + '%). ' + (job.terputus ? 'Koneksi bermasalah.' : 'Anda boleh berpindah menu, proses tetap berlanjut.') + '</div>' +
        '<div class="ir-bar"><span style="width:' + pct + '%"></span></div>' +
        '<div class="ir-actions">' + (job.terputus ? '<button class="ir-btn ir-btn--p" data-ir="resume">Lanjutkan</button>' : '') + '<a class="ir-btn" href="/kirim-slip">Lihat</a></div>';
    } else {
      box.className = 'ir-box';
      var label = job.status === 'dibatalkan' ? 'Import dibatalkan' : 'Import gaji selesai';
      html = '<div class="ir-head"><i class="bi bi-check-circle-fill"></i> ' + label + '</div>' +
        '<div class="ir-sub">' + job.berhasil + ' data berhasil' + (job.gagalCount ? ', ' + job.gagalCount + ' ditolak' : '') + ' dari ' + job.total + ' baris.</div>' +
        '<div class="ir-actions"><a class="ir-btn ir-btn--p" href="/preview-slip">Lihat Preview</a>' + (job.gagalCount ? '<a class="ir-btn" href="/kirim-slip">Lihat detail</a>' : '') + '<button class="ir-btn" data-ir="close">Tutup</button></div>';
    }
    box.innerHTML = html;
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-ir]');
    if (!b) return;
    if (b.getAttribute('data-ir') === 'close') window.ImportRunner.dismiss();
    if (b.getAttribute('data-ir') === 'resume') window.ImportRunner.resume();
  });

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', refresh);
  else refresh();
})();
