/* =========================================================
   js/import-runner.js  (dimuat di SEMUA halaman lewat layouts/app)

   Menjalankan pekerjaan panjang di latar belakang:
     - ImportRunner : import gaji Excel   (/api/slip/import-jobs)
     - SendRunner   : pengiriman slip     (/api/slip/kirim-jobs)

   Cara kerja (sama untuk keduanya):
   - Data pekerjaan disimpan di server (tabel import_jobs / send_jobs).
   - Skrip ini memanggil ".../{id}/proses" berulang kali (beberapa baris per
     panggilan) sampai selesai.
   - Saat pengguna pindah menu, halaman baru memuat skrip ini lagi, membaca
     pekerjaan yang masih berjalan, lalu MELANJUTKAN dari posisi terakhir.
   - Di halaman lain tampil kartu progres di pojok kanan bawah. Di halaman
     asalnya (Kirim Slip / Preview Slip) tampilan dikelola halaman itu sendiri
     lewat runner.subscribe().
   ========================================================= */
(function () {
  'use strict';
  if (window.ImportRunner || !window.Api) return;

  var stack = null;
  function getStack() {
    if (stack && document.body.contains(stack)) return stack;
    injectCss();
    stack = document.createElement('div');
    stack.className = 'ir-stack';
    document.body.appendChild(stack);
    return stack;
  }

  function injectCss() {
    if (document.getElementById('irCss')) return;
    var st = document.createElement('style'); st.id = 'irCss';
    st.textContent =
      '.ir-stack{position:fixed;right:20px;bottom:20px;z-index:99990;display:flex;flex-direction:column;gap:12px;width:min(340px,calc(100vw - 32px))}' +
      '.ir-box{background:#fff;border:1px solid #cfe5d8;border-radius:14px;box-shadow:0 14px 40px rgba(0,0,0,.18);padding:14px 16px;font-family:"Plus Jakarta Sans","Segoe UI",system-ui,sans-serif;color:#1f3a2c}' +
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

  /* opts: base, nav (halaman yang mengelola tampilannya sendiri), text{} */
  function makeRunner(opts) {
    var listeners = [], job = null, running = false, loaded = false, box = null;
    var suppress = !!(document.body && document.body.getAttribute('data-nav') === opts.nav);

    function emit() { listeners.forEach(function (fn) { try { fn(job); } catch (e) { console.error(e); } }); renderBanner(); }
    function sleep(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }

    async function loop() {
      if (running) return;
      running = true;
      var gagal = 0;
      try {
        while (job && job.status === 'berjalan') {
          try {
            var res = await Api.post(opts.base + '/' + job.id + '/proses');
            gagal = 0;
            job = res.job;
            emit();
            await sleep(res.sibuk ? 2500 : 50); // sibuk = tab/halaman lain sedang memproses batch
          } catch (err) {
            if (err && (err.status === 403 || err.status === 404)) { job = null; emit(); break; }
            gagal++;
            if (gagal >= 8) { console.error('Proses terhenti:', err); break; }
            await sleep(Math.min(1500 * gagal, 8000));
          }
        }
      } finally { running = false; }
      if (job && job.status === 'berjalan' && gagal >= 8) { job.terputus = true; emit(); }
    }

    function refresh() {
      return Api.get(opts.base + '/aktif').then(function (res) {
        job = res && res.job ? res.job : null;
        loaded = true;
        emit();
        if (job && job.status === 'berjalan') loop();
        return job;
      }).catch(function () { loaded = true; return null; }); // user tanpa izin: abaikan
    }

    function renderBanner() {
      if (suppress || !job) { if (box) { box.remove(); box = null; } return; }
      var holder = getStack();
      if (!box) { box = document.createElement('div'); holder.appendChild(box); }
      var T = opts.text, pct = job.total ? Math.round(job.processed / job.total * 100) : 0, html;
      if (job.status === 'berjalan') {
        box.className = 'ir-box' + (job.terputus ? ' ir-warn' : '');
        html = '<div class="ir-head"><i class="bi ' + (job.terputus ? 'bi-exclamation-triangle-fill' : 'bi-arrow-repeat ir-spin') + '"></i> ' + (job.terputus ? T.stopped : T.running) + '</div>' +
          '<div class="ir-sub">' + job.processed + ' dari ' + job.total + ' ' + T.unit + ' diproses (' + pct + '%). ' + (job.terputus ? 'Koneksi bermasalah.' : 'Anda boleh berpindah menu, proses tetap berlanjut.') + '</div>' +
          '<div class="ir-bar"><span style="width:' + pct + '%"></span></div>' +
          '<div class="ir-actions">' + (job.terputus ? '<button class="ir-btn ir-btn--p" data-ir="resume" data-runner="' + opts.key + '">Lanjutkan</button>' : '') + '<a class="ir-btn" href="' + T.pageHref + '">Lihat</a></div>';
      } else {
        box.className = 'ir-box';
        html = '<div class="ir-head"><i class="bi bi-check-circle-fill"></i> ' + (job.status === 'dibatalkan' ? T.cancelled : T.done) + '</div>' +
          '<div class="ir-sub">' + job.berhasil + ' ' + T.okWord + (job.gagalCount ? ', ' + job.gagalCount + ' ' + T.failWord : '') + ' dari ' + job.total + ' ' + T.unit + '.</div>' +
          '<div class="ir-actions"><a class="ir-btn ir-btn--p" href="' + T.doneHref + '">' + T.doneLabel + '</a><button class="ir-btn" data-ir="close" data-runner="' + opts.key + '">Tutup</button></div>';
      }
      box.innerHTML = html;
    }

    var api = {
      get current() { return job; },
      get loaded() { return loaded; },
      subscribe: function (fn) { listeners.push(fn); if (loaded) fn(job); },
      start: function (payload) {
        return Api.post(opts.base, payload).then(function (res) { job = res.job; emit(); loop(); return job; });
      },
      resume: function () { if (job) { delete job.terputus; emit(); loop(); } },
      cancel: function () {
        if (!job) return Promise.resolve();
        return Api.post(opts.base + '/' + job.id + '/batal').then(function (res) { job = res.job; emit(); });
      },
      dismiss: function () {
        if (!job || job.status === 'berjalan') return Promise.resolve();
        var id = job.id; job = null; emit();
        return Api.post(opts.base + '/' + id + '/dilihat').catch(function () { });
      }
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', refresh);
    else refresh();
    return api;
  }

  window.ImportRunner = makeRunner({
    key: 'import', base: '/api/slip/import-jobs', nav: 'kirim-slip',
    text: {
      running: 'Import gaji sedang berjalan', stopped: 'Import gaji terhenti', done: 'Import gaji selesai', cancelled: 'Import dibatalkan',
      unit: 'data', okWord: 'data berhasil', failWord: 'ditolak', pageHref: '/kirim-slip', doneHref: '/preview-slip', doneLabel: 'Lihat Preview'
    }
  });

  window.SendRunner = makeRunner({
    key: 'send', base: '/api/slip/kirim-jobs', nav: 'preview-slip',
    text: {
      running: 'Pengiriman slip sedang berjalan', stopped: 'Pengiriman slip terhenti', done: 'Pengiriman slip selesai', cancelled: 'Pengiriman dibatalkan',
      unit: 'slip', okWord: 'slip terkirim', failWord: 'gagal', pageHref: '/preview-slip', doneHref: '/detail-riwayat', doneLabel: 'Lihat Riwayat'
    }
  });

  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-ir]');
    if (!b) return;
    var r = b.getAttribute('data-runner') === 'send' ? window.SendRunner : window.ImportRunner;
    if (b.getAttribute('data-ir') === 'close') r.dismiss();
    if (b.getAttribute('data-ir') === 'resume') r.resume();
  });
})();
