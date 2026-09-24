/* =========================================================
   Preview Slip Gaji
   Struktur: Channel -> Divisi -> Karyawan -> Status
   Channel ditentukan otomatis saat import:
     - Email terisi -> Email (meskipun WA juga terisi)
     - Email kosong + WA terisi -> WhatsApp
   ========================================================= */
(function () {
  'use strict';

  const $ = (sel, root) => (root || document).querySelector(sel);
  const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));
  const esc = SlipUI.esc;

  const contentEl = $('#psContent');
  const emptyEl = $('#psEmpty');
  const emptyTitle = $('#psEmptyTitle');
  const emptyText = $('#psEmptyText');
  const summaryEl = $('#psSummary');
  const listEl = $('#psList');
  const searchEl = $('#psSearch');
  const suggestEl = $('#psSuggest');
  const btnAll = $('#psSendAll');
  const btnAllWa = $('#psSendAllWa');

  let rows = [];
  let historyCache = [];
  let settingsCache = { emailEnabled: true, waEnabled: true };
  let query = '';
  let busy = false;
  let didSend = false;
  const openGroups = new Set();
  let activeSuggest = -1;

  const money = (v) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0
  }).format(Number(v || 0));

  function load() {
    return Promise.all([
      SlipStore.getPreviewRows(),
      SlipStore.getHistory(),
      SlipStore.getSettings(),
    ]).then((results) => {
      rows = results[0];
      historyCache = results[1];
      settingsCache = results[2];
    }).catch((err) => {
      SlipUI.toast('Gagal memuat data preview: ' + err.message, 'error');
    });
  }

  function groupRows(source) {
    const map = new Map();
    source.forEach((r) => {
      const divisi = r.divisi || '-';
      if (!map.has(divisi)) map.set(divisi, []);
      map.get(divisi).push(r);
    });
    return Array.from(map.entries()).sort((a, b) => a[0].localeCompare(b[0], 'id'));
  }

  function highlight(text, kw) {
    const i = kw ? text.toLowerCase().indexOf(kw.toLowerCase()) : -1;
    if (i < 0) return esc(text);
    return esc(text.slice(0, i)) + '<mark>' + esc(text.slice(i, i + kw.length)) + '</mark>' + esc(text.slice(i + kw.length));
  }

  function statusFor(row, channel) {
    const item = historyCache.find((h) =>
      h.nik === row.nik &&
      (h.channel || 'email') === channel
    );
    if (!item) return { text: 'Menunggu', type: 'pending' };
    if (String(item.status).toLowerCase() === 'berhasil') return { text: 'Berhasil', type: 'success' };
    if (String(item.status).toLowerCase() === 'gagal') return { text: 'Gagal', type: 'failed' };
    return { text: item.status || 'Menunggu', type: 'pending' };
  }

  function statusBadge(status) {
    const icon = status.type === 'success' ? 'bi-check-circle-fill'
      : status.type === 'failed' ? 'bi-x-circle-fill' : 'bi-hourglass-split';
    return '<span class="ps-status ps-status--' + esc(status.type) + '">' +
      '<i class="bi ' + icon + '" aria-hidden="true"></i><span>' + esc(status.text) + '</span></span>';
  }

  function employeeRows(items, channel) {
    return items.map((r, i) => {
      const status = statusFor(r, channel);
      const destination = channel === 'email' ? (r.email || '-') : (r.wa || '-');
      return '<tr>' +
        '<td class="ps-num">' + (i + 1) + '</td>' +
        '<td class="ps-nik">' + esc(r.nik) + '</td>' +
        '<td><div class="ps-employee"><strong>' + esc(r.nama) + '</strong><small>' + esc(destination) + '</small></div></td>' +
        '<td>' + statusBadge(status) + '</td>' +
        '<td>' + esc(SlipStore.formatIso(r.date)) + '</td>' +
        '<td class="ps-file">' + esc(r.file) + '</td>' +
        '<td class="ps-preview-cell"><button type="button" class="ps-eye" data-preview="' + esc(r.nik) + '" title="Lihat desain slip gaji" aria-label="Lihat desain slip gaji ' + esc(r.nama) + '"><i class="bi bi-eye-fill"></i></button></td>' +
      '</tr>';
    }).join('');
  }

  function groupHtml(divisi, items, idx, channel) {
    const key = channel + '::' + divisi;
    const open = openGroups.has(key);
    const bodyId = 'psBody' + channel + idx;
    const isEmail = channel === 'email';
    const icon = isEmail ? 'bi-envelope-fill' : 'bi-whatsapp';
    const label = isEmail ? 'Email' : 'WhatsApp';

    return '<section class="ps-group' + (open ? ' is-open' : '') + '" data-divisi="' + esc(divisi) + '" data-channel="' + channel + '">' +
      '<div class="ps-group__head">' +
        '<button type="button" class="ps-group__toggle" data-toggle aria-expanded="' + open + '" aria-controls="' + bodyId + '">' +
          '<i class="bi bi-chevron-down ps-chev" aria-hidden="true"></i>' +
          '<span class="ps-group__name">' + esc(divisi) + '</span>' +
          '<span class="ps-count">' + items.length + ' karyawan</span>' +
        '</button>' +
        '<div class="ps-group__actions">' +
          '<button type="button" class="ps-mini ' + (isEmail ? 'ps-mini--email' : 'ps-mini--wa') + '" data-send data-channel="' + channel + '" data-divisi="' + esc(divisi) + '" title="Kirim slip divisi ' + esc(divisi) + ' via ' + label + '">' +
            '<i class="bi ' + icon + '" aria-hidden="true"></i><span>' + label + '</span>' +
          '</button>' +
        '</div>' +
      '</div>' +
      '<div class="ps-group__body" id="' + bodyId + '"' + (open ? '' : ' hidden') + '>' +
        '<table class="ps-table"><thead><tr>' +
          '<th class="ps-num">No</th><th>NIK</th><th>Nama Karyawan</th><th>Status</th><th>Tanggal</th><th>File Slip</th><th>Preview</th>' +
        '</tr></thead><tbody>' + employeeRows(items, channel) + '</tbody></table>' +
      '</div></section>';
  }

  function channelHtml(channel, groups) {
    const isEmail = channel === 'email';
    const title = isEmail ? 'Email' : 'WhatsApp';
    const subtitle = isEmail
      ? 'Slip dengan Email terisi diarahkan ke channel Email.'
      : 'Slip dengan Email kosong dan WhatsApp terisi diarahkan ke channel WhatsApp.';
    const icon = isEmail ? 'bi-envelope-fill' : 'bi-whatsapp';
    const cls = isEmail ? 'ps-channel--email' : 'ps-channel--wa';
    const total = groups.reduce((n, g) => n + g[1].length, 0);

    return '<section class="ps-channel ' + cls + '" data-channel-section="' + channel + '">' +
      '<div class="ps-channel__head"><div class="ps-channel__identity">' +
        '<span class="ps-channel__icon"><i class="bi ' + icon + '" aria-hidden="true"></i></span>' +
        '<div><h2>' + title + '</h2><p>' + subtitle + '</p></div>' +
      '</div><span class="ps-channel__total">' + total + ' karyawan</span></div>' +
      '<div class="ps-channel__groups">' +
        (groups.length ? groups.map((g, i) => groupHtml(g[0], g[1], i, channel)).join('') : '<p class="ps-none">Tidak ada data untuk channel ini.</p>') +
      '</div></section>';
  }

  function render() {
    const allGroups = groupRows(rows);
    if (!rows.length) {
      contentEl.hidden = true;
      emptyEl.hidden = false;
      summaryEl.textContent = '';
      emptyTitle.textContent = didSend ? 'Semua slip sudah diproses' : 'Belum ada slip untuk dipreview';
      emptyText.textContent = didSend
        ? 'Tidak ada slip yang menunggu dikirim. Hasil pengiriman bisa dilihat di Riwayat.'
        : 'Import Excel gaji terlebih dahulu pada halaman Data Karyawan.';
      return;
    }

    contentEl.hidden = false;
    emptyEl.hidden = true;
    const q = query.trim().toLowerCase();
    const visible = allGroups.filter((g) => g[0].toLowerCase().indexOf(q) >= 0);
    const emailGroups = groupRows(visible.flatMap((g) => g[1]).filter((r) => r.channel === 'email'));
    const waGroups = groupRows(visible.flatMap((g) => g[1]).filter((r) => r.channel === 'wa'));

    summaryEl.innerHTML = '<strong>' + rows.length + '</strong> karyawan &middot; <strong>' + allGroups.length + '</strong> divisi &middot; Email <strong>' + rows.filter(r => r.channel === 'email').length + '</strong> &middot; WA <strong>' + rows.filter(r => r.channel === 'wa').length + '</strong>';
    listEl.innerHTML = channelHtml('email', emailGroups) + channelHtml('wa', waGroups);

    const settings = settingsCache;
    const hasEmail = rows.some((r) => r.channel === 'email');
    const hasWa = rows.some((r) => r.channel === 'wa');
    btnAll.disabled = busy || !hasEmail || !settings.emailEnabled;
    btnAllWa.disabled = busy || !hasWa || !settings.waEnabled;
    btnAll.title = settings.emailEnabled ? 'Kirim semua melalui Email' : 'Pengiriman Email dinonaktifkan di Pengaturan';
    btnAllWa.title = settings.waEnabled ? 'Kirim semua melalui WhatsApp' : 'Pengiriman WhatsApp dinonaktifkan di Pengaturan';
    listEl.querySelectorAll('[data-send]').forEach((b) => {
      const ch = b.getAttribute('data-channel');
      const enabled = ch === 'email' ? settings.emailEnabled : settings.waEnabled;
      b.disabled = !enabled;
      b.title = enabled ? b.title : 'Pengiriman ' + (ch === 'email' ? 'Email' : 'WhatsApp') + ' dinonaktifkan di Pengaturan';
    });
  }

  function getDivisions() { return groupRows(rows).map((g) => g[0]); }

  function renderSuggestions() {
    const q = searchEl.value.trim().toLowerCase();
    const divisions = getDivisions().filter((d) => !q || d.toLowerCase().indexOf(q) >= 0);
    suggestEl.innerHTML = divisions.length
      ? divisions.map((d, i) => '<li role="option" tabindex="-1" data-value="' + esc(d) + '" aria-selected="' + (i === activeSuggest) + '">' + highlight(d, q) + '</li>').join('')
      : '<li class="ps-suggest__empty">Divisi tidak ditemukan</li>';
    suggestEl.hidden = false;
  }
  function closeSuggestions() { suggestEl.hidden = true; searchEl.setAttribute('aria-expanded', 'false'); activeSuggest = -1; }

  searchEl.addEventListener('focus', () => { searchEl.setAttribute('aria-expanded', 'true'); renderSuggestions(); });
  searchEl.addEventListener('input', () => { query = searchEl.value; render(); activeSuggest = -1; searchEl.setAttribute('aria-expanded', 'true'); renderSuggestions(); });
  searchEl.addEventListener('keydown', (e) => {
    const options = $$('.ps-suggest li[data-value]', suggestEl);
    if (e.key === 'ArrowDown') { e.preventDefault(); activeSuggest = Math.min(activeSuggest + 1, options.length - 1); renderSuggestions(); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); activeSuggest = Math.max(activeSuggest - 1, 0); renderSuggestions(); }
    else if (e.key === 'Enter' && activeSuggest >= 0 && options[activeSuggest]) { e.preventDefault(); searchEl.value = options[activeSuggest].getAttribute('data-value'); query = searchEl.value; closeSuggestions(); render(); }
    else if (e.key === 'Escape') closeSuggestions();
  });
  suggestEl.addEventListener('click', (e) => { const item = e.target.closest('[data-value]'); if (!item) return; searchEl.value = item.getAttribute('data-value'); query = searchEl.value; closeSuggestions(); render(); });
  document.addEventListener('click', (e) => { if (!$('#psCombo').contains(e.target)) closeSuggestions(); });

  function setBusy(on) {
    busy = on;
    const settings = settingsCache;
    btnAll.disabled = on || !settings.emailEnabled;
    btnAllWa.disabled = on || !settings.waEnabled;
    listEl.querySelectorAll('[data-send]').forEach((b) => { b.disabled = on || (b.getAttribute('data-channel') === 'email' ? !settings.emailEnabled : !settings.waEnabled); });
  }

  function progressButton(scope) {
    if (scope.all) return scope.channel === 'wa' ? btnAllWa : btnAll;
    return listEl.querySelector('[data-channel="' + scope.channel + '"] [data-send][data-divisi="' + CSS.escape(scope.divisi) + '"]');
  }

  async function sendFlow(scope) {
    if (busy) return;
    const channel = scope.channel || 'email';
    const settings = settingsCache;
    if ((channel === 'email' && !settings.emailEnabled) || (channel === 'wa' && !settings.waEnabled)) {
      SlipUI.toast('Pengiriman ' + (channel === 'email' ? 'Email' : 'WhatsApp') + ' sedang dinonaktifkan di Pengaturan.', 'error');
      return;
    }
    const target = scope.all
      ? rows.filter((r) => r.channel === channel)
      : rows.filter((r) => r.channel === channel && r.divisi === scope.divisi);
    if (!target.length) { SlipUI.toast('Tidak ada slip pada channel ini.', 'info'); return; }

    const label = channel === 'email' ? 'email' : 'WhatsApp';
    const nDivisi = new Set(target.map((r) => r.divisi)).size;
    const what = scope.all ? target.length + ' slip dari ' + nDivisi + ' divisi' : target.length + ' slip divisi ' + scope.divisi;
    const answer = await SlipUI.dialog({
      title: 'Kirim slip via ' + label + '?',
      html: '<p class="ks-result__lead">' + esc(what) + ' akan dikirim melalui ' + label + ' sesuai kontak pada data import.</p><p class="ks-hint">Status pengiriman akan diperbarui pada daftar karyawan dan dicatat di Riwayat.</p>',
      buttons: [{ label: 'Batal', kind: 'outline', value: 'cancel' }, { label: 'Kirim', kind: 'primary', value: 'send' }]
    });
    if (answer !== 'send') return;

    setBusy(true);
    const btn = progressButton(scope);
    let restore = null;
    if (btn) {
      const icon = $('i', btn), labelEl = $('span', btn);
      restore = { iconClass: icon.className, text: labelEl.textContent };
      icon.className = 'bi bi-arrow-repeat is-spin';
    }

    let res;
    try {
      if (btn) { const labelEl = $('span', btn); if (labelEl) labelEl.textContent = 'Mengirim…'; }
      res = await SlipStore.sendChannel({ all: scope.all, divisi: scope.divisi, channel });
    } catch (err) {
      if (btn && restore) { $('i', btn).className = restore.iconClass; $('span', btn).textContent = restore.text; }
      setBusy(false); render(); SlipUI.toast('Terjadi kesalahan saat mengirim. Coba lagi.', 'error'); return;
    }

    didSend = true;
    if (btn && restore) { $('i', btn).className = restore.iconClass; $('span', btn).textContent = restore.text; }
    setBusy(false); await load(); render();
    const choice = await showSendResult(res, label);
    if (choice === 'riwayat') window.location.href = '/detail-riwayat';
  }

  async function showSendResult(res, label) {
    const nOk = res.terkirim.length, nFail = res.gagal.length, total = nOk + nFail;
    const lead = nFail === 0 ? (total === 1 ? 'Slip berhasil dikirim.' : 'Semua slip berhasil dikirim.') : nOk === 0 ? 'Tidak ada slip yang terkirim.' : nOk + ' dari ' + total + ' slip terkirim, ' + nFail + ' gagal.';
    return SlipUI.dialog({
      title: 'Hasil pengiriman ' + label,
      html: SlipUI.summaryHtml({
        lead, total, ok: nOk, fail: nFail,
        labels: ['Total slip', 'Terkirim', 'Gagal'],
        itemsTitle: 'Slip yang gagal dikirim',
        items: res.gagal.map((g) => ({ title: g.row.nama, sub: (label === 'email' ? g.row.email : g.row.wa) + ' — ' + g.reason })),
        note: nFail > 0 ? 'Slip yang gagal tetap ada di daftar dan bisa dikirim ulang.' : 'Semua pengiriman tercatat di Riwayat.'
      }),
      buttons: [{ label: 'Tutup', kind: 'outline', value: 'close' }, { label: 'Lihat Riwayat', kind: 'primary', value: 'riwayat' }]
    });
  }

  function showSlipPreview(row) {
    const cfg = row.componentConfig || { additions: [], deductions: [] };
    const additions = Array.isArray(cfg.additions) && cfg.additions.length > 0 ? cfg.additions : ['Gaji Pokok','Tunjangan Jabatan','Tunjangan Bansos','Tunjangan Managerial','Tambahan 5','Tambahan 6','Tambahan 7','Tambahan 8','Tambahan 9','Tambahan 10'];
    const deductions = Array.isArray(cfg.deductions) && cfg.deductions.length > 0 ? cfg.deductions : ['BP JAMSOSTEK (JHT: 2%); (JP: 1%)','BPJS-Kshtn (JK: 1%)','BNI (DPLK Simponi)','KSU Ke. Mitratani','Iuran Anggota SPA','BTN (KPR)','BRI Cab Jember','Potongan 8','Potongan 9','Potongan 10'];
    const addValues = [];
    for (let i = 1; i <= 20; i++) if (Number(row['tambahan'+i] || 0) > 0) addValues.push([additions[i-1] || ('Tambahan '+i), row['tambahan'+i]]);
    // Gaji Pokok selalu tampil sebagai komponen utama, bukan tambahan 1 dari Excel.
    const deductionsWithValues = [];
    for (let i = 1; i <= 20; i++) if (Number(row['potongan'+i] || 0) > 0) deductionsWithValues.push([deductions[i-1] || ('Potongan '+i), row['potongan'+i]]);
    const totalTambahan = addValues.reduce((n,x)=>n+Number(x[1]||0),0);
    const totalPotongan = deductionsWithValues.reduce((n,x)=>n+Number(x[1]||0),0);
    const gross = Number(row.gajiPokok || 0) + totalTambahan;
    const bersih = gross - totalPotongan;
    const initials = String(row.nama || 'K').trim().split(/\s+/).slice(0,2).map(x=>x[0]).join('').toUpperCase();
    const modal = document.createElement('div');
    modal.className = 'ps-slip-modal';
    modal.innerHTML = '<div class="ps-slip-modal__backdrop" data-close></div>' +
      '<div class="ps-slip-modal__dialog ps-slip-modal__dialog--wide" role="dialog" aria-modal="true" aria-label="Preview slip gaji">' +
        '<div class="ps-slip-modal__head"><div><span class="ps-slip-modal__eyebrow">DOKUMEN RAHASIA • SLIP GAJI</span><h2>PT Mitratani Dua Tujuh</h2></div><div class="ps-slip-modal__head-actions">'+(row.pdfUrl?'<button type="button" class="ps-slip-pdf" data-pdf title="Generate / buka PDF"><i class="bi bi-file-earmark-pdf-fill"></i><span>PDF</span></button>':'')+'<button type="button" class="ps-slip-modal__close" data-close><i class="bi bi-x-lg"></i></button></div></div>' +
        '<div class="ps-slip">' +
          '<div class="ps-slip__brand"><div class="ps-slip__logo"><img src="'+(window.ESLIP_LOGO_URL || 'assets/logo.png')+'" alt="Logo Mitratani" onerror="this.style.display=\'none\'"><span>'+esc(initials)+'</span></div><div><strong>PT MITRATANI DUA TUJUH</strong><span>SLIP GAJI KARYAWAN</span></div><div class="ps-slip__secret"><i class="bi bi-shield-lock-fill"></i> RAHASIA</div></div>' +
          '<div class="ps-slip__number"><span>NOMOR URUT</span><strong>' + esc(row.noUrut || '-') + '</strong><small>' + esc(SlipStore.formatIso(row.date)) + '</small></div>' +
          '<div class="ps-slip__employee-grid">' +
            '<div><span>Nama</span><strong>' + esc(row.nama || '-') + '</strong></div><div><span>NIK</span><strong>' + esc(row.nik || '-') + '</strong></div>' +
            '<div><span>Pangkat / Golongan</span><strong>' + esc(row.pangkat || '-') + '</strong></div><div><span>Jabatan</span><strong>' + esc(row.jabatan || '-') + '</strong></div>' +
            '<div><span>Rekening</span><strong>' + esc(row.rekening || '-') + '</strong></div><div><span>Divisi</span><strong>' + esc(row.divisi || '-') + '</strong></div>' +
          '</div>' +
          '<div class="ps-slip__columns">' +
            '<section><div class="ps-slip__section-title">PENERIMAAN</div><div class="ps-slip__line"><span>Gaji Pokok</span><strong>' + money(row.gajiPokok) + '</strong></div>' +
              addValues.map(x=>'<div class="ps-slip__line"><span>'+esc(x[0])+'</span><strong>'+money(x[1])+'</strong></div>').join('') +
              '<div class="ps-slip__line ps-slip__subtotal"><span>Total Penerimaan</span><strong>'+money(gross)+'</strong></div></section>' +
            '<section><div class="ps-slip__section-title">POTONGAN</div>' +
              (deductionsWithValues.length ? deductionsWithValues.map(x=>'<div class="ps-slip__line"><span>'+esc(x[0])+'</span><strong class="ps-slip__minus">- '+money(x[1])+'</strong></div>').join('') : '<div class="ps-slip__no-deduction">Tidak ada potongan</div>') +
              '<div class="ps-slip__line ps-slip__subtotal"><span>Total Potongan</span><strong class="ps-slip__minus">- '+money(totalPotongan)+'</strong></div></section>' +
          '</div>' +
          '<div class="ps-slip__net"><div><span>TAKE HOME PAY</span><small>Jumlah yang diterima setelah seluruh potongan</small></div><strong>' + money(bersih) + '</strong></div>' +
          '<div class="ps-slip__footer"><span>Dokumen ini dibuat secara otomatis dari data payroll yang telah divalidasi terhadap master Karyawan.</span><span><i class="bi bi-shield-lock-fill"></i> CONFIDENTIAL</span></div>' +
        '</div>' +
      '</div>';
    document.body.appendChild(modal);
    requestAnimationFrame(()=>modal.classList.add('is-open'));
    const close=()=>{modal.classList.remove('is-open');setTimeout(()=>modal.remove(),180);};
    modal.addEventListener('click',e=>{
      if(e.target.closest('[data-close]')) { close(); return; }
      const pdfBtn=e.target.closest('[data-pdf]');
      if(pdfBtn && row.pdfUrl) {
        pdfBtn.disabled=true;
        window.open(row.pdfUrl, '_blank', 'noopener');
        setTimeout(()=>{if(document.body.contains(pdfBtn))pdfBtn.disabled=false;},800);
      }
    });
    const escKey=e=>{if(e.key==='Escape'){document.removeEventListener('keydown',escKey);close();}};document.addEventListener('keydown',escKey);
  }

  /* Accordion + tombol preview + kirim per divisi. */
  listEl.addEventListener('click', (e) => {
    const eye = e.target.closest('[data-preview]');
    if (eye) {
      const row = rows.find((r) => r.nik === eye.getAttribute('data-preview'));
      if (row) showSlipPreview(row);
      return;
    }
    const toggle = e.target.closest('[data-toggle]');
    if (toggle) {
      const group = toggle.closest('.ps-group');
      if (!group) return;
      const key = group.getAttribute('data-channel') + '::' + group.getAttribute('data-divisi');
      const body = document.getElementById(toggle.getAttribute('aria-controls'));
      const willOpen = body.hidden;
      if (willOpen) openGroups.add(key); else openGroups.delete(key);
      body.hidden = !willOpen;
      group.classList.toggle('is-open', willOpen);
      toggle.setAttribute('aria-expanded', String(willOpen));
      return;
    }
    const send = e.target.closest('[data-send]');
    if (send) sendFlow({ all: false, channel: send.getAttribute('data-channel'), divisi: send.getAttribute('data-divisi') });
  });

  btnAll.addEventListener('click', () => sendFlow({ all: true, channel: 'email' }));
  btnAllWa.addEventListener('click', () => sendFlow({ all: true, channel: 'wa' }));

  load().then(render);
})();
