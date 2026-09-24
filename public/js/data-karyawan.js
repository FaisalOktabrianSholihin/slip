/* =========================================================
   Halaman Data Karyawan
   Data di bawah adalah contoh (dummy). Cari komentar
   "BACKEND" untuk tahu di mana harus menyambungkan ke
   server (Laravel / Node / API lain).
   ========================================================= */
(function () {
  'use strict';

  /* ---------- Data ----------
     Data sesungguhnya ditarik dari /api/karyawan (database db_indukk)
     lewat loadData() di bagian "Mulai" paling bawah file ini. */
  var DIVISI = [];
  var JABATAN = [];
  var BANK_NAMES = ['BCA', 'BRI', 'BNI', 'Mandiri', 'CIMB Niaga', 'Bank Syariah Indonesia', 'Lainnya'];

  var STATUS_PEGAWAI_LABEL = {
    tetap: 'Karyawan Tetap',
    pkwt: 'PKWT',
    honorer: 'Honorer',
    penugasan: 'Karyawan Penugasan'
  };

  var data = [];
  var nextId = 1;

  var state = {
    q: '',
    divisi: '',
    status: '',
    sortKey: null,
    sortDir: 'asc',
    page: 1,
    perPage: 10
  };

  /* Data sementara untuk blok dinamis (Bank & Data Anak) di dalam form yang
     sedang dibuka. Direset setiap kali modal form dibuka. */
  var formBank = [];
  var formAnak = [];

  /* ---------- Helper ---------- */
  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function esc(str) {
    return String(str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function initials(name) {
    var parts = name.trim().split(/\s+/);
    var first = parts[0] ? parts[0].charAt(0) : '';
    var last = parts.length > 1 ? parts[parts.length - 1].charAt(0) : (parts[0] || '').charAt(1);
    return (first + (last || '')).toUpperCase();
  }

  function tone(name) {
    var h = 0;
    for (var i = 0; i < name.length; i++) h = (h * 31 + name.charCodeAt(i)) % 5;
    return 'kw-tone-' + h;
  }

  function findById(id) {
    for (var i = 0; i < data.length; i++) if (data[i].id === id) return data[i];
    return null;
  }

  function debounce(fn, ms) {
    var t;
    return function () {
      var args = arguments, ctx = this;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(ctx, args); }, ms);
    };
  }

  function jkLabel(v) {
    return v === 'L' ? 'Laki-laki' : (v === 'P' ? 'Perempuan' : '');
  }

  function fmtDate(iso) {
    if (!iso) return '';
    var p = String(iso).split('-');
    if (p.length !== 3) return iso;
    var d = new Date(Date.UTC(+p[0], +p[1] - 1, +p[2]));
    if (isNaN(d.getTime())) return iso;
    var bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return d.getUTCDate() + ' ' + bulan[d.getUTCMonth()] + ' ' + d.getUTCFullYear();
  }

  /* Kotak nilai read-only bergaya field form, dipakai di modal Detail */
  function vfield(label, value, span, multiline) {
    span = span || 4;
    var empty = (value === undefined || value === null || value === '');
    var cls = 'kw-vfield__value' + (multiline ? ' kw-vfield__value--multiline' : '') + (empty ? ' kw-vfield__value--empty' : '');
    return '<div class="kw-field kw-s' + span + ' kw-vfield"><label>' + esc(label) + '</label>' +
      '<p class="' + cls + '">' + esc(empty ? '-' : value) + '</p></div>';
  }

  function vfieldBadge(label, aktif, span) {
    span = span || 4;
    var badge = aktif
      ? '<span class="kw-badge kw-badge--on">Aktif</span>'
      : '<span class="kw-badge kw-badge--off">Nonaktif</span>';
    return '<div class="kw-field kw-s' + span + ' kw-vfield"><label>' + esc(label) + '</label>' +
      '<p class="kw-vfield__value kw-vfield__value--plain">' + badge + '</p></div>';
  }

  /* ---------- Elemen ---------- */
  var els = {
    tbody: $('#kwTbody'),
    tableWrap: $('#kwTableWrap'),
    empty: $('#kwEmpty'),
    info: $('#kwInfo'),
    pager: $('#kwPager'),
    search: $('#kwSearch'),
    filterDivisi: $('#kwFilterDivisi'),
    filterStatus: $('#kwFilterStatus'),
    filterBtn: $('#kwFilterBtn'),
    filterPanel: $('#kwFilterPanel'),
    filterBadge: $('#kwFilterBadge'),
    filterReset: $('#kwFilterReset'),
    perPage: $('#kwPerPage'),
    toasts: $('#kwToasts'),
    statTotal: $('#kwStatTotal'),
    statAktif: $('#kwStatAktif'),
    statNonaktif: $('#kwStatNonaktif'),
    statDivisi: $('#kwStatDivisi'),
    modalForm: $('#kwModalForm'),
    modalDetail: $('#kwModalDetail'),
    modalDelete: $('#kwModalDelete'),
    form: $('#kwForm'),
    formTitle: $('#kwFormTitle'),
    formSubmit: $('#kwFormSubmit'),
    detailTop: $('#kwDetailTop'),
    detailBody: $('#kwDetailBody'),
    detailTabs: $('#kwDetailTabs'),
    detailTabButtons: $$('#kwDetailTabs .kw-tab'),
    detailPanels: $$('#kwDetailBody .kw-tabpanel'),
    detailEdit: $('#kwDetailEdit'),
    deleteText: $('#kwDeleteText'),
    deleteConfirm: $('#kwDeleteConfirm'),
    importFile: $('#kwImportFile'),
    modalImport: $('#kwModalImport'),
    importSubmit: $('#kwImportSubmit'),
    importResult: $('#kwImportResult'),

    tabs: $('#kwTabs'),
    tabButtons: $$('.kw-tab'),
    tabPanels: $$('.kw-tabpanel'),

    bankList: $('#kwBankList'),
    btnTambahBank: $('#kwBtnTambahBank'),
    anakTbody: $('#kwAnakTbody'),
    btnTambahAnak: $('#kwBtnTambahAnak')
  };

  /* ---------- Filter, sort, render ---------- */
  function getFiltered() {
    var q = state.q.trim().toLowerCase();

    var rows = data.filter(function (r) {
      if (state.divisi && r.divisi !== state.divisi) return false;
      if (state.status && (state.status === 'aktif') !== r.aktif) return false;
      if (!q) return true;
      return [r.nama, r.email, r.nik, r.divisi].some(function (v) {
        return v.toLowerCase().indexOf(q) !== -1;
      });
    });

    if (state.sortKey) {
      var k = state.sortKey;
      var dir = state.sortDir === 'asc' ? 1 : -1;
      rows = rows.slice().sort(function (a, b) {
        var av = a[k], bv = b[k];
        if (typeof av === 'string') return av.localeCompare(bv, 'id') * dir;
        return (av === bv ? 0 : av ? -1 : 1) * dir; // boolean: aktif dulu
      });
    }
    return rows;
  }

  function rowHtml(r, no) {
    var badge = r.aktif
      ? '<span class="kw-badge kw-badge--on">Aktif</span>'
      : '<span class="kw-badge kw-badge--off">Nonaktif</span>';

    return '' +
      '<tr>' +
        '<td class="kw-col-no">' + no + '</td>' +
        '<td><div class="kw-person">' +
          '<span class="kw-avatar ' + tone(r.nama) + '" aria-hidden="true">' + esc(initials(r.nama)) + '</span>' +
          '<span class="kw-person__name">' + esc(r.nama) + '</span>' +
        '</div></td>' +
        '<td class="kw-email">' + esc(r.email) + '</td>' +
        '<td class="kw-nik">' + esc(r.nik) + '</td>' +
        '<td><span class="kw-chip">' + esc(r.divisi) + '</span></td>' +
        '<td>' + badge + '</td>' +
        '<td class="kw-col-actions"><div class="kw-row-actions">' +
          '<button type="button" class="kw-icon-btn kw-icon-btn--view" data-action="view" data-id="' + r.id + '" title="Lihat detail" aria-label="Lihat detail ' + esc(r.nama) + '"><i class="fa-solid fa-eye"></i></button>' +
          '<button type="button" class="kw-icon-btn kw-icon-btn--edit" data-action="edit" data-id="' + r.id + '" title="Ubah data" aria-label="Ubah data ' + esc(r.nama) + '"><i class="fa-solid fa-pen-to-square"></i></button>' +
          '<button type="button" class="kw-icon-btn kw-icon-btn--delete" data-action="delete" data-id="' + r.id + '" title="Hapus" aria-label="Hapus ' + esc(r.nama) + '"><i class="fa-solid fa-trash-can"></i></button>' +
        '</div></td>' +
      '</tr>';
  }

  function pageList(cur, total) {
    var i, list = [];
    if (total <= 7) {
      for (i = 1; i <= total; i++) list.push(i);
      return list;
    }
    var s = Math.max(2, cur - 1), e = Math.min(total - 1, cur + 1);
    list.push(1);
    if (s > 2) list.push('gap');
    for (i = s; i <= e; i++) list.push(i);
    if (e < total - 1) list.push('gap');
    list.push(total);
    return list;
  }

  function renderPager(pages) {
    var cur = state.page;
    var html = '<button type="button" class="kw-page" data-page="' + (cur - 1) + '"' + (cur === 1 ? ' disabled' : '') + ' aria-label="Halaman sebelumnya"><i class="fa-solid fa-chevron-left"></i></button>';

    pageList(cur, pages).forEach(function (p) {
      if (p === 'gap') {
        html += '<span class="kw-page kw-page--gap" aria-hidden="true">…</span>';
      } else {
        html += '<button type="button" class="kw-page' + (p === cur ? ' is-active' : '') + '" data-page="' + p + '"' + (p === cur ? ' aria-current="page"' : '') + '>' + p + '</button>';
      }
    });

    html += '<button type="button" class="kw-page" data-page="' + (cur + 1) + '"' + (cur === pages ? ' disabled' : '') + ' aria-label="Halaman berikutnya"><i class="fa-solid fa-chevron-right"></i></button>';
    els.pager.innerHTML = html;
  }

  function renderStats() {
    var aktif = data.filter(function (r) { return r.aktif; }).length;
    var divisi = {};
    data.forEach(function (r) { divisi[r.divisi] = true; });
    els.statTotal.textContent = data.length;
    els.statAktif.textContent = aktif;
    els.statNonaktif.textContent = data.length - aktif;
    els.statDivisi.textContent = Object.keys(divisi).length;
  }

  function renderSortUi() {
    $$('th[aria-sort]').forEach(function (th) {
      var btn = $('.kw-sort', th);
      var icon = $('i', btn);
      var key = btn.getAttribute('data-sort');
      if (state.sortKey === key) {
        th.setAttribute('aria-sort', state.sortDir === 'asc' ? 'ascending' : 'descending');
        icon.className = 'fa-solid ' + (state.sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
      } else {
        th.setAttribute('aria-sort', 'none');
        icon.className = 'fa-solid fa-sort';
      }
    });
  }

  /* Badge jumlah filter aktif (divisi + status digabung dalam satu icon filter) */
  function renderFilterBadge() {
    var count = (state.divisi ? 1 : 0) + (state.status ? 1 : 0);
    if (count > 0) {
      els.filterBadge.hidden = false;
      els.filterBadge.textContent = count;
      els.filterBtn.classList.add('is-active');
    } else {
      els.filterBadge.hidden = true;
      els.filterBtn.classList.remove('is-active');
    }
  }

  function render() {
    var rows = getFiltered();
    var total = rows.length;
    var pages = Math.max(1, Math.ceil(total / state.perPage));
    if (state.page > pages) state.page = pages;
    if (state.page < 1) state.page = 1;

    var start = (state.page - 1) * state.perPage;
    var slice = rows.slice(start, start + state.perPage);

    els.tbody.innerHTML = slice.map(function (r, i) { return rowHtml(r, start + i + 1); }).join('');
    els.tableWrap.hidden = total === 0;
    els.empty.hidden = total !== 0;
    els.info.textContent = total
      ? 'Menampilkan ' + (start + 1) + '–' + (start + slice.length) + ' dari ' + total + ' karyawan'
      : '0 karyawan';

    renderPager(pages);
    renderStats();
    renderSortUi();
    renderFilterBadge();
  }

  /* ---------- Toast ---------- */
  function toast(message, type) {
    var el = document.createElement('div');
    el.className = 'kw-toast' + (type === 'error' ? ' kw-toast--error' : '');
    el.innerHTML = '<i class="fa-solid ' + (type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check') + '"></i><span>' + esc(message) + '</span>';
    els.toasts.appendChild(el);
    setTimeout(function () {
      el.style.transition = 'opacity .2s';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 220);
    }, 3200);
  }

  /* ---------- Modal ---------- */
  var activeModal = null;
  var lastFocus = null;

  function openModal(modal) {
    lastFocus = document.activeElement;
    activeModal = modal;
    modal.hidden = false;
    document.body.classList.add('kw-lock');
    requestAnimationFrame(function () { modal.classList.add('is-open'); });
    var target = $('[data-autofocus]', modal) || $('button, input, select', modal);
    if (target) setTimeout(function () { target.focus(); }, 30);
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('is-open');
    setTimeout(function () { modal.hidden = true; }, 160);
    document.body.classList.remove('kw-lock');
    if (activeModal === modal) activeModal = null;
    if (lastFocus && document.body.contains(lastFocus)) lastFocus.focus();
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-close]')) {
      closeModal(e.target.closest('.kw-modal'));
    }
  });

  document.addEventListener('keydown', function (e) {
    if (!activeModal) return;
    if (e.key === 'Escape') {
      closeModal(activeModal);
      return;
    }
    if (e.key === 'Tab') { // jaga fokus tetap di dalam modal
      var f = $$('button:not([disabled]), input, select, textarea, [href]', activeModal)
        .filter(function (n) { return n.offsetParent !== null; });
      if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });

  /* ---------- Panel filter (gabungan divisi + status) ---------- */
  function openFilterPanel() {
    els.filterPanel.hidden = false;
    els.filterBtn.setAttribute('aria-expanded', 'true');
  }

  function closeFilterPanel() {
    els.filterPanel.hidden = true;
    els.filterBtn.setAttribute('aria-expanded', 'false');
  }

  els.filterBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    if (els.filterPanel.hidden) openFilterPanel(); else closeFilterPanel();
  });

  els.filterPanel.addEventListener('click', function (e) {
    e.stopPropagation(); // klik di dalam panel tidak menutup panel
  });

  document.addEventListener('click', function (e) {
    if (!els.filterPanel.hidden && !e.target.closest('.kw-filter')) closeFilterPanel();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !els.filterPanel.hidden) closeFilterPanel();
  });

  els.filterReset.addEventListener('click', function () {
    state.divisi = '';
    state.status = '';
    state.page = 1;
    els.filterDivisi.value = '';
    els.filterStatus.value = '';
    render();
    closeFilterPanel();
  });

  /* ---------- Tab navigasi form ---------- */
  function switchTab(key) {
    els.tabButtons.forEach(function (btn) {
      var active = btn.getAttribute('data-tab') === key;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    els.tabPanels.forEach(function (panel) {
      var active = panel.getAttribute('data-panel') === key;
      panel.classList.toggle('is-active', active);
      panel.hidden = !active;
    });
    // Scroll body form ke atas setiap ganti tab
    var body = $('.kw-formbody', els.modalForm);
    if (body) body.scrollTop = 0;
  }

  els.tabs.addEventListener('click', function (e) {
    var btn = e.target.closest('.kw-tab');
    if (!btn) return;
    switchTab(btn.getAttribute('data-tab'));
  });

  /* ---------- Tab navigasi detail (read-only) ---------- */
  function switchDetailTab(key) {
    els.detailTabButtons.forEach(function (btn) {
      var active = btn.getAttribute('data-dtab') === key;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    els.detailPanels.forEach(function (panel) {
      var active = panel.getAttribute('data-dpanel') === key;
      panel.classList.toggle('is-active', active);
      panel.hidden = !active;
    });
    if (els.detailBody) els.detailBody.scrollTop = 0;
  }

  els.detailTabs.addEventListener('click', function (e) {
    var btn = e.target.closest('.kw-tab');
    if (!btn) return;
    switchDetailTab(btn.getAttribute('data-dtab'));
  });

  /* ---------- Select generik ---------- */
  function fillSelect(select, withAll) {
    var html = withAll ? '<option value="">Semua divisi</option>' : '<option value="">Pilih divisi</option>';
    DIVISI.forEach(function (d) { html += '<option value="' + esc(d) + '">' + esc(d) + '</option>'; });
    select.innerHTML = html;
  }

  function fillOptions(select, list, placeholder) {
    var html = '<option value="">' + esc(placeholder) + '</option>';
    list.forEach(function (v) { html += '<option value="' + esc(v) + '">' + esc(v) + '</option>'; });
    select.innerHTML = html;
  }

  /* ---------- Blok Bank dinamis (form tambah/ubah) ---------- */
  function bankItemHtml(item, index) {
    var options = '<option value="">-- Pilih --</option>' +
      BANK_NAMES.map(function (b) {
        return '<option value="' + esc(b) + '"' + (item.namaBank === b ? ' selected' : '') + '>' + esc(b) + '</option>';
      }).join('');
    var id = 'kwBank' + index;

    return '' +
      '<div class="kw-bank-item" data-bank-index="' + index + '">' +
        '<div class="kw-bank-item__head">' +
          '<span class="kw-bank-badge">Bank ' + (index + 1) + '</span>' +
          (formBank.length > 1
            ? '<button type="button" class="kw-icon-btn kw-icon-btn--delete kw-bank-remove" data-bank-remove="' + index + '" title="Hapus bank" aria-label="Hapus Bank ' + (index + 1) + '"><i class="fa-solid fa-trash-can"></i></button>'
            : '') +
        '</div>' +
        '<div class="kw-grid">' +
          '<div class="kw-field kw-s4"><label for="' + id + 'Nama">Nama Bank</label>' +
            '<select class="kw-select" id="' + id + 'Nama" data-bank-field="namaBank">' + options + '</select>' +
          '</div>' +
          '<div class="kw-field kw-s4"><label for="' + id + 'Rek">No Rekening</label>' +
            '<input type="text" class="kw-input" id="' + id + 'Rek" data-bank-field="noRekening" inputmode="numeric" value="' + esc(item.noRekening || '') + '" autocomplete="off"></div>' +
          '<div class="kw-field kw-s4"><label for="' + id + 'Atas">Atas Nama</label>' +
            '<input type="text" class="kw-input" id="' + id + 'Atas" data-bank-field="atasNama" value="' + esc(item.atasNama || '') + '" autocomplete="off"></div>' +
        '</div>' +
      '</div>';
  }

  function renderBankList() {
    els.bankList.innerHTML = formBank.map(bankItemHtml).join('');
  }

  function addBankRow(item) {
    formBank.push(item || { namaBank: '', noRekening: '', atasNama: '' });
    renderBankList();
  }

  function removeBankRow(index) {
    formBank.splice(index, 1);
    renderBankList();
  }

  els.btnTambahBank.addEventListener('click', function () { addBankRow(); });

  els.bankList.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-bank-remove]');
    if (!btn) return;
    removeBankRow(parseInt(btn.getAttribute('data-bank-remove'), 10));
  });

  els.bankList.addEventListener('input', bankFieldUpdate);
  els.bankList.addEventListener('change', bankFieldUpdate);

  function bankFieldUpdate(e) {
    var field = e.target.getAttribute('data-bank-field');
    if (!field) return;
    var item = e.target.closest('[data-bank-index]');
    var idx = parseInt(item.getAttribute('data-bank-index'), 10);
    if (formBank[idx]) formBank[idx][field] = e.target.value;
  }

  /* ---------- Baris Data Anak dinamis (form tambah/ubah) ---------- */
  function anakRowHtml(item, index) {
    return '' +
      '<tr data-anak-index="' + index + '">' +
        '<td class="kw-col-no">' + (index + 1) + '</td>' +
        '<td><input type="text" class="kw-input" data-anak-field="nama" aria-label="Nama anak ke-' + (index + 1) + '" value="' + esc(item.nama || '') + '" autocomplete="off"></td>' +
        '<td><input type="text" class="kw-input" data-anak-field="tempatLahir" aria-label="Tempat lahir anak ke-' + (index + 1) + '" value="' + esc(item.tempatLahir || '') + '" autocomplete="off"></td>' +
        '<td><input type="date" class="kw-input" data-anak-field="tglLahir" aria-label="Tanggal lahir anak ke-' + (index + 1) + '" value="' + esc(item.tglLahir || '') + '"></td>' +
        '<td><input type="text" class="kw-input" data-anak-field="pekerjaan" aria-label="Pekerjaan anak ke-' + (index + 1) + '" value="' + esc(item.pekerjaan || '') + '" autocomplete="off"></td>' +
        '<td><select class="kw-select" data-anak-field="jenisKelamin" aria-label="Jenis kelamin anak ke-' + (index + 1) + '">' +
          '<option value="">-</option>' +
          '<option value="L"' + (item.jenisKelamin === 'L' ? ' selected' : '') + '>L</option>' +
          '<option value="P"' + (item.jenisKelamin === 'P' ? ' selected' : '') + '>P</option>' +
        '</select></td>' +
        '<td><input type="text" class="kw-input" data-anak-field="pendidikan" aria-label="Pendidikan anak ke-' + (index + 1) + '" value="' + esc(item.pendidikan || '') + '" autocomplete="off"></td>' +
        '<td><button type="button" class="kw-icon-btn kw-icon-btn--delete kw-anak-remove" data-anak-remove="' + index + '" title="Hapus" aria-label="Hapus anak ke-' + (index + 1) + '"><i class="fa-solid fa-trash-can"></i></button></td>' +
      '</tr>';
  }

  function renderAnakList() {
    els.anakTbody.innerHTML = formAnak.length
      ? formAnak.map(anakRowHtml).join('')
      : '<tr class="kw-anak-empty"><td colspan="8">Belum ada data anak. Klik "Tambah Anak" untuk menambahkan.</td></tr>';
  }

  function addAnakRow(item) {
    formAnak.push(item || { nama: '', tempatLahir: '', tglLahir: '', pekerjaan: '', jenisKelamin: '', pendidikan: '' });
    renderAnakList();
  }

  function removeAnakRow(index) {
    formAnak.splice(index, 1);
    renderAnakList();
  }

  els.btnTambahAnak.addEventListener('click', function () { addAnakRow(); });

  els.anakTbody.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-anak-remove]');
    if (!btn) return;
    removeAnakRow(parseInt(btn.getAttribute('data-anak-remove'), 10));
  });

  els.anakTbody.addEventListener('input', anakFieldUpdate);
  els.anakTbody.addEventListener('change', anakFieldUpdate);

  function anakFieldUpdate(e) {
    var field = e.target.getAttribute('data-anak-field');
    if (!field) return;
    var tr = e.target.closest('[data-anak-index]');
    var idx = parseInt(tr.getAttribute('data-anak-index'), 10);
    if (formAnak[idx]) formAnak[idx][field] = e.target.value;
  }

  /* ---------- Tanggal pensiun otomatis (dari tanggal lahir, usia 56 th) ---------- */
  var RETIREMENT_AGE = 56;

  function calcPensiun(iso) {
    if (!iso) return '';
    var d = new Date(iso);
    if (isNaN(d.getTime())) return '';
    d.setFullYear(d.getFullYear() + RETIREMENT_AGE);
    return d.toISOString().slice(0, 10);
  }

  function updatePensiunDate() {
    els.form.elements.tglPensiun.value = calcPensiun(els.form.elements.tglLahir.value);
  }

  $('#kwFTglLahir').addEventListener('change', updatePensiunDate);

  /* ---------- Form tambah / ubah ---------- */
  var editId = null;

  function clearErrors() {
    $$('.kw-field__error', els.form).forEach(function (p) { p.textContent = ''; });
    $$('.kw-input, .kw-select', els.form).forEach(function (i) { i.removeAttribute('aria-invalid'); });
    els.tabButtons.forEach(function (b) { b.classList.remove('kw-tab--error'); });
  }

  function setError(name, msg) {
    var input = els.form.elements[name];
    var p = $('[data-error-for="' + name + '"]', els.form);
    if (p) p.textContent = msg;
    if (input) input.setAttribute('aria-invalid', 'true');
  }

  /* Tab mana yang memuat field bernama `name` -- dipakai untuk lompat
     otomatis ke tab yang errornya perlu dibenahi. */
  var FIELD_TAB = {
    nik: 'utama', nama: 'utama', jabatan: 'utama', divisi: 'utama',
    email: 'utama', ktp: 'utama', hp: 'utama', alamat: 'utama',
    jenisKelamin: 'pribadi', tglLahir: 'pribadi'
  };

  function openForm(id) {
    editId = id || null;
    clearErrors();
    els.form.reset();
    formBank = [];
    formAnak = [];

    if (editId) {
      var r = findById(editId);
      els.formTitle.textContent = 'Ubah data karyawan';
      els.formSubmit.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan perubahan';

      var f = els.form.elements;
      f.nik.value = r.nik || '';
      f.noAbsen.value = r.noAbsen || '';
      f.golongan.value = r.golongan || '';
      f.nama.value = r.nama || '';
      f.namaPanggilan.value = r.namaPanggilan || '';
      f.jabatan.value = r.jabatan || '';
      f.divisi.value = r.divisi || '';
      f.statusPegawai.value = r.statusPegawai || '';
      f.status.value = r.aktif ? 'aktif' : 'nonaktif';
      f.masaKerja.value = r.masaKerja || '';
      f.tglMasuk.value = r.tglMasuk || '';
      f.tglAngkat.value = r.tglAngkat || '';
      f.tglPensiun.value = r.tglPensiun || '';
      f.jatahCuti.value = (r.jatahCuti != null) ? r.jatahCuti : 12;
      f.sisaCuti.value = (r.sisaCuti != null) ? r.sisaCuti : 12;
      f.email.value = r.email || '';
      f.ktp.value = r.ktp || '';
      f.hp.value = r.hp || '';
      f.alamat.value = r.alamat || '';

      f.jenisKelamin.value = r.jenisKelamin || '';
      f.agama.value = r.agama || '';
      f.suku.value = r.suku || '';
      f.tempatLahir.value = r.tempatLahir || '';
      f.tglLahir.value = r.tglLahir || '';
      f.statusNikah.value = r.statusNikah || '';
      f.hobby.value = r.hobby || '';

      f.sd.value = r.sd || '';
      f.sltp.value = r.sltp || '';
      f.slta.value = r.slta || '';
      f.pt.value = r.pt || '';
      f.pendidikanTerakhir.value = r.pendidikanTerakhir || '';
      f.jurusan.value = r.jurusan || '';
      f.tahunMasuk.value = r.tahunMasuk || '';
      f.tahunKeluar.value = r.tahunKeluar || '';

      f.namaAyah.value = r.namaAyah || '';
      f.namaIbu.value = r.namaIbu || '';
      f.namaPasangan.value = r.namaPasangan || '';
      f.tempatLahirPasangan.value = r.tempatLahirPasangan || '';
      f.tglLahirPasangan.value = r.tglLahirPasangan || '';
      f.pekerjaanPasangan.value = r.pekerjaanPasangan || '';
      f.jkPasangan.value = r.jkPasangan || '';
      f.pendidikanPasangan.value = r.pendidikanPasangan || '';
      f.jaminanKesehatan.value = r.jaminanKesehatan || '';

      formBank = (r.bank && r.bank.length) ? r.bank.map(function (b) { return { namaBank: b.namaBank, noRekening: b.noRekening, atasNama: b.atasNama }; }) : [{ namaBank: '', noRekening: '', atasNama: '' }];
      formAnak = (r.anak && r.anak.length) ? r.anak.map(function (a) { return Object.assign({}, a); }) : [];
    } else {
      els.formTitle.textContent = 'Tambah karyawan';
      els.formSubmit.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan';
      els.form.elements.jatahCuti.value = 12;
      els.form.elements.sisaCuti.value = 12;
      formBank = [{ namaBank: '', noRekening: '', atasNama: '' }];
      formAnak = [];
    }

    renderBankList();
    renderAnakList();
    switchTab('utama');
    openModal(els.modalForm);
  }

  function validate(values) {
    var ok = true;
    var firstBadTab = null;
    var badTabs = {};
    clearErrors();

    function fail(field, msg) {
      setError(field, msg);
      ok = false;
      var tab = FIELD_TAB[field] || 'utama';
      badTabs[tab] = true;
      if (!firstBadTab) firstBadTab = tab;
    }

    if (!values.nik || values.nik.trim().length !== 8) fail('nik', 'NIK wajib 8 digit.');
    else if (data.some(function (r) { return r.id !== editId && r.nik === values.nik; })) fail('nik', 'NIK ini sudah terdaftar.');

    if (values.nama.length < 2) fail('nama', 'Nama wajib diisi (minimal 2 karakter).');

    if (!values.jabatan) fail('jabatan', 'Pilih jabatan karyawan.');
    if (!values.divisi) fail('divisi', 'Pilih divisi karyawan.');

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email)) {
      fail('email', 'Masukkan alamat email yang valid.');
    } else if (data.some(function (r) { return r.id !== editId && r.email.toLowerCase() === values.email.toLowerCase(); })) {
      fail('email', 'Email ini sudah dipakai karyawan lain.');
    }

    if (!/^\d{16}$/.test(values.ktp)) {
      fail('ktp', 'No KTP harus 16 digit angka.');
    } else if (data.some(function (r) { return r.id !== editId && r.ktp === values.ktp; })) {
      fail('ktp', 'No KTP ini sudah terdaftar.');
    }

    if (!values.hp || values.hp.trim().length < 8) fail('hp', 'No HP wajib diisi dengan benar.');
    if (!values.alamat || !values.alamat.trim()) fail('alamat', 'Alamat wajib diisi.');

    if (!values.jenisKelamin) fail('jenisKelamin', 'Pilih jenis kelamin.');
    if (!values.tglLahir) fail('tglLahir', 'Tanggal lahir wajib diisi.');

    if (!ok && firstBadTab) {
      els.tabButtons.forEach(function (b) {
        b.classList.toggle('kw-tab--error', !!badTabs[b.getAttribute('data-tab')]);
      });
      switchTab(firstBadTab);
      var firstBad = $('[aria-invalid="true"]', els.form);
      if (firstBad) firstBad.focus();
    }
    return ok;
  }

  els.form.elements.ktp.addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 8);
  });

  els.form.addEventListener('submit', function (e) {
    e.preventDefault();
    var f = els.form.elements;
    var values = {
      nik: f.nik.value.trim(),
      noAbsen: f.noAbsen.value.trim(),
      golongan: f.golongan.value,
      nama: f.nama.value.trim(),
      namaPanggilan: f.namaPanggilan.value.trim(),
      jabatan: f.jabatan.value,
      divisi: f.divisi.value,
      statusPegawai: f.statusPegawai.value,
      aktif: f.status.value === 'aktif',
      masaKerja: f.masaKerja.value,
      tglMasuk: f.tglMasuk.value,
      tglAngkat: f.tglAngkat.value,
      tglPensiun: f.tglPensiun.value,
      jatahCuti: f.jatahCuti.value,
      sisaCuti: f.sisaCuti.value,
      email: f.email.value.trim(),
      ktp: f.ktp.value.trim(),
      hp: f.hp.value.trim(),
      alamat: f.alamat.value.trim(),

      jenisKelamin: f.jenisKelamin.value,
      agama: f.agama.value,
      suku: f.suku.value.trim(),
      tempatLahir: f.tempatLahir.value.trim(),
      tglLahir: f.tglLahir.value,
      statusNikah: f.statusNikah.value,
      hobby: f.hobby.value.trim(),

      sd: f.sd.value.trim(),
      sltp: f.sltp.value.trim(),
      slta: f.slta.value.trim(),
      pt: f.pt.value.trim(),
      pendidikanTerakhir: f.pendidikanTerakhir.value,
      jurusan: f.jurusan.value.trim(),
      tahunMasuk: f.tahunMasuk.value.trim(),
      tahunKeluar: f.tahunKeluar.value.trim(),

      namaAyah: f.namaAyah.value.trim(),
      namaIbu: f.namaIbu.value.trim(),
      namaPasangan: f.namaPasangan.value.trim(),
      tempatLahirPasangan: f.tempatLahirPasangan.value.trim(),
      tglLahirPasangan: f.tglLahirPasangan.value,
      pekerjaanPasangan: f.pekerjaanPasangan.value.trim(),
      jkPasangan: f.jkPasangan.value,
      pendidikanPasangan: f.pendidikanPasangan.value.trim(),
      jaminanKesehatan: f.jaminanKesehatan.value,

      bank: formBank.slice(),
      anak: formAnak.slice()
    };
    if (!validate(values)) return;

    var submitBtn = els.formSubmit;
    submitBtn.disabled = true;

    var request = editId
      ? Api.put('/api/karyawan/' + editId, values)
      : Api.post('/api/karyawan', values);

    request.then(function (saved) {
      if (editId) {
        // BACKEND: sudah tersambung - PUT ke /api/karyawan/{id}.
        var r = findById(editId);
        Object.assign(r, saved);
        toast('Perubahan data karyawan disimpan.');
      } else {
        // BACKEND: sudah tersambung - POST ke /api/karyawan, id dari server.
        data.push(saved);
        state.q = ''; state.divisi = ''; state.status = '';
        els.search.value = ''; els.filterDivisi.value = ''; els.filterStatus.value = '';
        state.page = Math.ceil(data.length / state.perPage); // lompat ke halaman terakhir
        toast('Karyawan baru ditambahkan.');
      }

      closeModal(els.modalForm);
      render();
    }).catch(function (err) {
      toast(err.message || 'Gagal menyimpan data karyawan.', 'error');
    }).finally(function () {
      submitBtn.disabled = false;
    });
  });

  /* ---------- Detail (tampil seperti form, tapi read-only + bertab) ---------- */
  var detailId = null;

  function detailUtamaHtml(r) {
    return '' +
      '<h3 class="kw-formsec"><i class="fa-solid fa-briefcase"></i> Informasi Kepegawaian</h3>' +
      '<div class="kw-grid">' +
        vfield('NIK', r.nik, 4) +
        vfield('No Absen', r.noAbsen, 4) +
        vfield('Golongan', r.golongan, 4) +
        vfield('Nama', r.nama, 8) +
        vfield('Nama Panggilan', r.namaPanggilan, 4) +
        vfield('Jabatan', r.jabatan, 3) +
        vfield('Divisi', r.divisi, 3) +
        vfield('Status Pegawai', STATUS_PEGAWAI_LABEL[r.statusPegawai] || r.statusPegawai, 3) +
        vfieldBadge('Status Aktif', r.aktif, 3) +
      '</div>' +
      '<h3 class="kw-formsec kw-formsec--spaced"><i class="fa-solid fa-calendar-days"></i> Masa Kerja &amp; Cuti</h3>' +
      '<div class="kw-grid">' +
        vfield('Tanggal Masuk', fmtDate(r.tglMasuk), 4) +
        vfield('Tanggal Pengangkatan', fmtDate(r.tglAngkat), 4) +
        vfield('Tanggal Pensiun', fmtDate(r.tglPensiun), 4) +
        vfield('Masa Kerja (tahun)', r.masaKerja, 4) +
        vfield('Jatah Cuti (hari)', r.jatahCuti, 4) +
        vfield('Sisa Cuti (hari)', r.sisaCuti, 4) +
      '</div>' +
      '<h3 class="kw-formsec kw-formsec--spaced"><i class="fa-solid fa-address-card"></i> Kontak &amp; Identitas</h3>' +
      '<div class="kw-grid">' +
        vfield('Email', r.email, 4) +
        vfield('No HP', r.hp, 4) +
        vfield('No KTP', r.ktp, 4) +
        vfield('Alamat', r.alamat, 12, true) +
      '</div>';
  }

  function detailPribadiHtml(r) {
    return '' +
      '<h3 class="kw-formsec"><i class="fa-solid fa-user"></i> Identitas Pribadi</h3>' +
      '<div class="kw-grid">' +
        vfield('Jenis Kelamin', jkLabel(r.jenisKelamin), 4) +
        vfield('Agama', r.agama, 4) +
        vfield('Suku', r.suku, 4) +
        vfield('Tempat Lahir', r.tempatLahir, 4) +
        vfield('Tanggal Lahir', fmtDate(r.tglLahir), 4) +
        vfield('Status Nikah', r.statusNikah, 4) +
        vfield('Hobby', r.hobby, 12) +
      '</div>';
  }

  function detailBankHtml(r) {
    var bank = (r.bank && r.bank.length) ? r.bank : [];
    var bankHtml = bank.length
      ? bank.map(function (b, i) {
          return '<div class="kw-bank-item">' +
            '<div class="kw-bank-item__head"><span class="kw-bank-badge">Bank ' + (i + 1) + '</span></div>' +
            '<div class="kw-grid">' +
              vfield('Nama Bank', b.namaBank, 4) +
              vfield('No Rekening', b.noRekening, 4) +
              vfield('Atas Nama', b.atasNama, 4) +
            '</div>' +
          '</div>';
        }).join('')
      : '<p class="kw-vfield__empty-note">Belum ada data bank.</p>';

    return '' +
      '<h3 class="kw-formsec"><i class="fa-solid fa-building-columns"></i> Informasi Bank</h3>' +
      '<div class="kw-bank-list">' + bankHtml + '</div>' +
      '<h3 class="kw-formsec kw-formsec--spaced"><i class="fa-solid fa-graduation-cap"></i> Riwayat Pendidikan</h3>' +
      '<div class="kw-grid">' +
        vfield('SD', r.sd, 6) +
        vfield('SLTP', r.sltp, 6) +
        vfield('SLTA', r.slta, 6) +
        vfield('Perguruan Tinggi (PT)', r.pt, 6) +
      '</div>' +
      '<div class="kw-grid">' +
        vfield('Pendidikan Terakhir', r.pendidikanTerakhir, 3) +
        vfield('Jurusan', r.jurusan, 3) +
        vfield('Tahun Masuk', r.tahunMasuk, 3) +
        vfield('Tahun Keluar', r.tahunKeluar, 3) +
      '</div>';
  }

  function detailKeluargaHtml(r) {
    var anak = (r.anak && r.anak.length) ? r.anak : [];
    var anakHtml = anak.length
      ? '<div class="kw-anak-wrap"><table class="kw-table">' +
          '<thead><tr><th class="kw-col-no">No</th><th>Nama</th><th>Tempat Lahir</th><th>Tanggal Lahir</th><th>Pekerjaan</th><th>Jenis Kelamin</th><th>Pendidikan</th></tr></thead>' +
          '<tbody>' + anak.map(function (a, i) {
            return '<tr>' +
              '<td class="kw-col-no">' + (i + 1) + '</td>' +
              '<td>' + esc(a.nama || '-') + '</td>' +
              '<td>' + esc(a.tempatLahir || '-') + '</td>' +
              '<td>' + esc(fmtDate(a.tglLahir) || '-') + '</td>' +
              '<td>' + esc(a.pekerjaan || '-') + '</td>' +
              '<td>' + esc(jkLabel(a.jenisKelamin) || '-') + '</td>' +
              '<td>' + esc(a.pendidikan || '-') + '</td>' +
            '</tr>';
          }).join('') + '</tbody>' +
        '</table></div>'
      : '<p class="kw-vfield__empty-note">Belum ada data anak.</p>';

    return '' +
      '<h3 class="kw-formsec"><i class="fa-solid fa-people-roof"></i> Orang Tua</h3>' +
      '<div class="kw-grid">' +
        vfield('Nama Ayah', r.namaAyah, 6) +
        vfield('Nama Ibu', r.namaIbu, 6) +
      '</div>' +
      '<h3 class="kw-formsec kw-formsec--spaced"><i class="fa-solid fa-heart"></i> Suami / Istri</h3>' +
      '<div class="kw-grid">' +
        vfield('Nama Pasangan', r.namaPasangan, 4) +
        vfield('Tempat Lahir Pasangan', r.tempatLahirPasangan, 4) +
        vfield('Tanggal Lahir Pasangan', fmtDate(r.tglLahirPasangan), 4) +
        vfield('Pekerjaan Pasangan', r.pekerjaanPasangan, 4) +
        vfield('Jenis Kelamin Pasangan', jkLabel(r.jkPasangan), 4) +
        vfield('Pendidikan Pasangan', r.pendidikanPasangan, 4) +
      '</div>' +
      '<h3 class="kw-formsec kw-formsec--spaced"><i class="fa-solid fa-child-reaching"></i> Data Anak</h3>' +
      anakHtml +
      '<h3 class="kw-formsec kw-formsec--spaced"><i class="fa-solid fa-shield-heart"></i> Jaminan Kesehatan</h3>' +
      '<div class="kw-grid">' +
        vfield('Jenis Jaminan', r.jaminanKesehatan, 4) +
      '</div>';
  }

  function openDetail(id) {
    var r = findById(id);
    if (!r) return;
    detailId = id;

    els.detailTop.innerHTML =
      '<span class="kw-avatar kw-avatar--lg ' + tone(r.nama) + '" aria-hidden="true">' + esc(initials(r.nama)) + '</span>' +
      '<div><h3 class="kw-detail__name">' + esc(r.nama) + '</h3><p class="kw-detail__email">' + esc(r.email) + '</p></div>';

    $('#kwDetailPanelUtama').innerHTML = detailUtamaHtml(r);
    $('#kwDetailPanelPribadi').innerHTML = detailPribadiHtml(r);
    $('#kwDetailPanelBank').innerHTML = detailBankHtml(r);
    $('#kwDetailPanelKeluarga').innerHTML = detailKeluargaHtml(r);

    switchDetailTab('utama');
    openModal(els.modalDetail);
  }

  els.detailEdit.addEventListener('click', function () {
    var id = detailId;
    closeModal(els.modalDetail);
    setTimeout(function () { openForm(id); }, 180);
  });

  /* ---------- Hapus ---------- */
  var deleteId = null;

  function openDelete(id) {
    var r = findById(id);
    if (!r) return;
    deleteId = id;
    els.deleteText.textContent = 'Data ' + r.nama + ' akan dihapus dan tidak bisa dikembalikan.';
    openModal(els.modalDelete);
  }

  els.deleteConfirm.addEventListener('click', function () {
    // BACKEND: sudah tersambung - DELETE ke /api/karyawan/{id}.
    els.deleteConfirm.disabled = true;
    Api.delete('/api/karyawan/' + deleteId).then(function () {
      data = data.filter(function (r) { return r.id !== deleteId; });
      closeModal(els.modalDelete);
      render();
      toast('Data karyawan dihapus.');
    }).catch(function (err) {
      toast(err.message || 'Gagal menghapus data karyawan.', 'error');
    }).finally(function () {
      els.deleteConfirm.disabled = false;
    });
  });

  /* ---------- Export, Template & Import Excel (.xlsx) ----------
     Kolom template = kolom hasil export, jadi file hasil export bisa
     langsung di-import lagi. Memakai library ExcelJS (dimuat di HTML). */
  var XL_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
  var XL_HEADER_FILL = 'FF2F5597';
  var XL_MAX_ROWS = 1000;            // baris yang sudah diberi format/dropdown di file Excel
  var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  /* ---------- Export, Template & Import Excel (.xlsx) ----------
     Template dibuat mengikuti seluruh field pada form Data Karyawan.
     Import akan mengisi kembali seluruh data tersebut berdasarkan NIK. */
  var XL_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
  var XL_HEADER_FILL = 'FF2F5597';
  var XL_MAX_ROWS = 1000;

  var MASTER_COLUMNS = [
    ['NIK','nik','text'],['No Absen','noAbsen'],['Golongan','golongan'],['Nama','nama'],['Nama Panggilan','namaPanggilan'],
    ['Jabatan','jabatan'],['Divisi','divisi'],['Status Pegawai','statusPegawai'],['Status','aktif'],['Masa Kerja','masaKerja'],
    ['Tanggal Masuk','tglMasuk','date'],['Tanggal Pengangkatan','tglAngkat','date'],['Tanggal Pensiun','tglPensiun','date'],
    ['Jatah Cuti','jatahCuti'],['Sisa Cuti','sisaCuti'],['Email','email'],['KTP','ktp','text'],['No HP / WhatsApp','hp','text'],['Alamat','alamat'],
    ['Jenis Kelamin','jenisKelamin'],['Agama','agama'],['Suku','suku'],['Tempat Lahir','tempatLahir'],['Tanggal Lahir','tglLahir','date'],
    ['Status Nikah','statusNikah'],['Hobby','hobby'],['SD','sd'],['SLTP','sltp'],['SLTA','slta'],['Perguruan Tinggi','pt'],
    ['Pendidikan Terakhir','pendidikanTerakhir'],['Jurusan','jurusan'],['Tahun Masuk','tahunMasuk'],['Tahun Keluar','tahunKeluar'],
    ['Nama Ayah','namaAyah'],['Nama Ibu','namaIbu'],['Nama Pasangan','namaPasangan'],['Tempat Lahir Pasangan','tempatLahirPasangan'],
    ['Tanggal Lahir Pasangan','tglLahirPasangan','date'],['Pekerjaan Pasangan','pekerjaanPasangan'],['Jenis Kelamin Pasangan','jkPasangan'],
    ['Pendidikan Pasangan','pendidikanPasangan'],['Jaminan Kesehatan','jaminanKesehatan']
  ];
  for (var bi=1; bi<=3; bi++) {
    MASTER_COLUMNS.push(['Bank '+bi+' - Nama','bank'+bi+'Nama'],['Bank '+bi+' - No Rekening','bank'+bi+'NoRekening','text'],['Bank '+bi+' - Atas Nama','bank'+bi+'AtasNama']);
  }
  for (var ai=1; ai<=10; ai++) {
    MASTER_COLUMNS.push(['Anak '+ai+' - Nama','anak'+ai+'Nama'],['Anak '+ai+' - Tempat Lahir','anak'+ai+'TempatLahir'],['Anak '+ai+' - Tanggal Lahir','anak'+ai+'TglLahir','date'],['Anak '+ai+' - Pekerjaan','anak'+ai+'Pekerjaan'],['Anak '+ai+' - Jenis Kelamin','anak'+ai+'Jk'],['Anak '+ai+' - Pendidikan','anak'+ai+'Pendidikan']);
  }

  var IMPORT_HEADERS = {};
  MASTER_COLUMNS.forEach(function(c){ IMPORT_HEADERS[normHeader(c[0])] = c[1]; });

  // Alias header agar import tidak bergantung pada satu penulisan Excel.
  // Nilai yang sudah ada di Excel tetap dipakai apa adanya (setelah trim),
  // terutama DIVISI, EMAIL dan NO HP/WHATSAPP karena ketiganya dipakai
  // sebagai data kontak/pengiriman slip.
  [
    ['nikkaryawan','nik'], ['nomornik','nik'], ['employeeid','nik'],
    ['nama karyawan','nama'], ['namakaryawan','nama'], ['namapegawai','nama'],
    ['departemen','divisi'], ['department','divisi'], ['unitkerja','divisi'], ['bagian','divisi'],
    ['posisi','jabatan'], ['position','jabatan'],
    ['alamatemail','email'], ['emailkaryawan','email'], ['emailpegawai','email'],
    ['nomorhp','hp'], ['nohandphone','hp'], ['nomorhandphone','hp'],
    ['nomorwa','hp'], ['nowa','hp'], ['nomorwhatsapp','hp'], ['nohpwa','hp'],
    ['telepon','hp'], ['phone','hp'], ['mobile','hp']
  ].forEach(function(pair){ IMPORT_HEADERS[normHeader(pair[0])] = pair[1]; });
  IMPORT_HEADERS.nohp='hp'; IMPORT_HEADERS.whatsapp='hp'; IMPORT_HEADERS.hpwa='hp';
  var IMPORT_BTN_HTML = '<i class="fa-solid fa-upload"></i> Import Excel';

  function hasExcelLib() {
    if (typeof ExcelJS !== 'undefined') return true;
    toast('Library Excel gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.', 'error');
    return false;
  }
  function pad2(n) { return (n < 10 ? '0' : '') + n; }
  function isDate(v) { return Object.prototype.toString.call(v) === '[object Date]'; }
  function isoFromParts(y,m,d){ var t=new Date(Date.UTC(y,m-1,d)); if(y<1900||y>2100)return null; if(t.getUTCFullYear()!==y||t.getUTCMonth()!==m-1||t.getUTCDate()!==d)return null; return y+'-'+pad2(m)+'-'+pad2(d); }
  function cellText(v) {
    if(v==null)return '';
    if(isDate(v))return v.toISOString().slice(0,10);
    if(typeof v==='object'){if(v.richText)return v.richText.map(function(t){return t.text||'';}).join('').trim();if(v.text!=null)return cellText(v.text);if(v.result!=null)return cellText(v.result);return '';}
    return String(v).trim();
  }
  function normHeader(s){return String(s||'').toLowerCase().replace(/[^a-z0-9]/g,'');}
  function parseDateCell(v){
    if(v==null||v==='')return '';
    if(isDate(v)) return isNaN(v.getTime())?'':isoFromParts(v.getUTCFullYear(),v.getUTCMonth()+1,v.getUTCDate())||'';
    if(typeof v==='number'){var d=new Date(Math.round((v-25569)*86400000));return isoFromParts(d.getUTCFullYear(),d.getUTCMonth()+1,d.getUTCDate())||'';}
    var s=cellText(v),m=s.match(/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/); if(m)return isoFromParts(+m[3],+m[2],+m[1])||'';
    m=s.match(/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/); return m?(isoFromParts(+m[1],+m[2],+m[3])||''):s;
  }
  function masterRowValues(r){
    var vals=[];
    MASTER_COLUMNS.forEach(function(c){
      var v=r[c[1]];
      if(c[1]==='aktif') v=r.aktif?'AKTIF':'NONAKTIF';
      vals.push(v==null?'':v);
    });
    return vals;
  }
  function flattenNested(r){
    var banks=Array.isArray(r.bank)?r.bank:[], anak=Array.isArray(r.anak)?r.anak:[];
    for(var i=1;i<=3;i++){var b=banks[i-1]||{};r['bank'+i+'Nama']=b.namaBank||'';r['bank'+i+'NoRekening']=b.noRekening||'';r['bank'+i+'AtasNama']=b.atasNama||'';}
    for(var j=1;j<=10;j++){var a=anak[j-1]||{};r['anak'+j+'Nama']=a.nama||'';r['anak'+j+'TempatLahir']=a.tempatLahir||'';r['anak'+j+'TglLahir']=a.tglLahir||'';r['anak'+j+'Pekerjaan']=a.pekerjaan||'';r['anak'+j+'Jk']=a.jenisKelamin||'';r['anak'+j+'Pendidikan']=a.pendidikan||'';}
    return r;
  }
  function unflattenNested(r){
    r.bank=[]; r.anak=[];
    for(var i=1;i<=3;i++){var b={namaBank:r['bank'+i+'Nama']||'',noRekening:r['bank'+i+'NoRekening']||'',atasNama:r['bank'+i+'AtasNama']||''};if(b.namaBank||b.noRekening||b.atasNama)r.bank.push(b);}
    for(var j=1;j<=10;j++){var a={nama:r['anak'+j+'Nama']||'',tempatLahir:r['anak'+j+'TempatLahir']||'',tglLahir:r['anak'+j+'TglLahir']||'',pekerjaan:r['anak'+j+'Pekerjaan']||'',jenisKelamin:r['anak'+j+'Jk']||'',pendidikan:r['anak'+j+'Pendidikan']||''};if(Object.values(a).some(function(v){return v!=='';}))r.anak.push(a);}
    return r;
  }
  function buildWorkbook(rows, withGuide){
    var wb=new ExcelJS.Workbook(), ws=wb.addWorksheet('Data Karyawan',{views:[{state:'frozen',ySplit:1}]});
    ws.columns=MASTER_COLUMNS.map(function(c){return {header:c[0],key:c[1],width:Math.max(16,Math.min(34,c[0].length+8))};});
    var head=ws.getRow(1);head.height=22;head.eachCell(function(cell){cell.font={bold:true,color:{argb:'FFFFFFFF'}};cell.fill={type:'pattern',pattern:'solid',fgColor:{argb:XL_HEADER_FILL}};cell.alignment={vertical:'middle'};});
    rows.forEach(function(raw,i){var r=flattenNested(Object.assign({},raw));var vals=masterRowValues(r);vals.forEach(function(v,ci){if(v!==''&&v!=null)ws.getCell(i+2,ci+1).value=v;});});
    for(var rr=2;rr<=Math.max(XL_MAX_ROWS,rows.length+1);rr++){MASTER_COLUMNS.forEach(function(c,ci){if(c[2]==='text')ws.getCell(rr,ci+1).numFmt='@';if(c[2]==='date')ws.getCell(rr,ci+1).numFmt='dd-mm-yyyy';});}
    if(withGuide){var g=wb.addWorksheet('Petunjuk');g.columns=[{width:30},{width:95}];g.addRow(['Kolom','Keterangan']);MASTER_COLUMNS.forEach(function(c){g.addRow([c[0],'Data yang tersedia pada menu Master Data Karyawan. NIK wajib 8 digit dan menjadi kunci pencocokan.']);});}
    return wb;
  }
  function downloadWorkbook(wb,filename){return wb.xlsx.writeBuffer().then(function(buf){var a=document.createElement('a');a.href=URL.createObjectURL(new Blob([buf],{type:XL_MIME}));a.download=filename;document.body.appendChild(a);a.click();a.remove();setTimeout(function(){URL.revokeObjectURL(a.href);},500);});}
  function exportExcel(){var rows=getFiltered();if(!rows.length){toast('Tidak ada data untuk diexport.','error');return;}if(!hasExcelLib())return;downloadWorkbook(buildWorkbook(rows,false),'data-karyawan-lengkap.xlsx').then(function(){toast(rows.length+' data karyawan diexport lengkap.');}).catch(function(e){console.error(e);toast('Export gagal dibuat.','error');});}
  function downloadTemplate(){if(!hasExcelLib())return;downloadWorkbook(buildWorkbook([],true),'template-import-karyawan-lengkap.xlsx').then(function(){toast('Template lengkap berhasil diunduh.');}).catch(function(e){console.error(e);toast('Template gagal dibuat.','error');});}
  function openImport(){els.importFile.value='';els.importSubmit.disabled=true;els.importResult.hidden=true;openModal(els.modalImport);}
  function readFileBuffer(file){return new Promise(function(resolve,reject){var fr=new FileReader();fr.onload=function(){resolve(fr.result);};fr.onerror=function(){reject(fr.error);};fr.readAsArrayBuffer(file);});}
  function importFromSheet(ws){
    var col={};
    ws.getRow(1).eachCell({includeEmpty:false},function(cell,n){
      var header=normHeader(cellText(cell.value));
      var key=IMPORT_HEADERS[header];
      if(key && !col[key]) col[key]=n;
    });

    if(!col.nik || !col.nama) return {fatal:'Header tidak sesuai. Kolom wajib: NIK dan Nama.'};

    var errors=[], importedRows=[], seen={};
    for(var rn=2;rn<=ws.rowCount;rn++){
      var row=ws.getRow(rn);
      var nikCell=col.nik ? row.getCell(col.nik) : null;
      var nik=String(nikCell ? (nikCell.text || cellText(nikCell.value)) : '')
        .trim().replace(/\.0+$/,'').replace(/\D/g,'');
      if(/^\d{1,7}$/.test(nik)) nik=nik.padStart(8,'0');

      var nama=cellText(col.nama ? row.getCell(col.nama).value : '');
      if(!nik && !nama) continue;

      var msgs=[];
      if(!/^\d{8}$/.test(nik)) msgs.push('NIK harus tepat 8 digit');
      else if(seen[nik]) msgs.push('NIK duplikat di file Excel');
      if(!nama) msgs.push('Nama wajib diisi');

      // Field kontak WA/email tidak boleh dibuat-buat oleh sistem.
      // Nilai harus berasal dari Excel (atau dari data lama saat update).
      var email=cellText(col.email ? row.getCell(col.email).value : '');
      var hp=cellText(col.hp ? row.getCell(col.hp).value : '');
      if(col.email && email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) msgs.push('Email tidak valid');
      if(!col.email) msgs.push('Kolom Email tidak ditemukan');
      if(!col.hp) msgs.push('Kolom No HP / WhatsApp tidak ditemukan');
      if(col.email && !email) msgs.push('Email wajib diisi');
      if(col.hp && !hp) msgs.push('No HP / WhatsApp wajib diisi');

      if(msgs.length){
        errors.push({row:rn,msg:msgs.join('; ')});
        continue;
      }

      var existing=data.find(function(x){return String(x.nik)===nik;});
      var r={id:existing?existing.id:nextId++,nik:nik};
      MASTER_COLUMNS.forEach(function(c){
        if(!col[c[1]]) return;
        var v=row.getCell(col[c[1]]).value;
        if(c[2]==='date') v=parseDateCell(v); else v=cellText(v);
        if(c[1]==='aktif') v=!/^(non|tidak)/i.test(String(v));
        r[c[1]]=v;
      });

      // Jangan pernah mengganti nilai Excel dengan email/WA dummy.
      r.nama=nama;
      r.email=email;
      r.hp=hp;
      r.aktif=typeof r.aktif==='boolean'?r.aktif:true;
      unflattenNested(r);
      importedRows.push(r);
      seen[nik]=true;
    }

    return {added:importedRows.length,errors:errors,importedRows:importedRows};
  }
  function persistImportedRows(res){
    if(!res.added||!res.importedRows.length) return Promise.resolve(res);
    return Api.post('/api/karyawan/import',{rows:res.importedRows}).then(function(saved){
      // Hanya data yang benar-benar berhasil disimpan yang dimasukkan ke state.
      (saved.imported||[]).forEach(function(r){
        var idx=data.findIndex(function(x){return String(x.nik)===String(r.nik);});
        if(idx>=0) data[idx]=r; else data.push(r);
      });

      // Backend memproses setiap baris secara terpisah. Jadi satu data bermasalah
      // tidak menghentikan data lainnya. Tampilkan kembali baris yang gagal.
      (saved.failed||[]).forEach(function(f){
        res.errors.push({
          row:f.row || ((f.index||0)+2),
          msg:'NIK '+(f.nik||'-')+(f.nama?' ('+f.nama+')':'')+': '+(f.message||'Gagal disimpan ke database')
        });
      });
      res.added=(saved.summary&&Number.isFinite(saved.summary.success)) ? saved.summary.success : (saved.imported||[]).length;

      fillSelect(els.filterDivisi,true);fillSelect(els.form.elements.divisi,false);
      state.page=1;render();
      return res;
    }).catch(function(err){
      res.fatal='Data Excel gagal dikirim ke server: '+(err.message||'');
      return res;
    });
  }
  function showImportResult(res){var box=els.importResult,html,cls;if(res.fatal){cls='kw-import__result--error';html='<p class="kw-import__summary">'+esc(res.fatal)+'</p>';toast('Import gagal.','error');}else if(res.added&&!res.errors.length){closeModal(els.modalImport);toast(res.added+' data karyawan berhasil diimport lengkap.');return;}else{var LIMIT=50;cls=res.added?'kw-import__result--warn':'kw-import__result--error';html='<p class="kw-import__summary">'+(res.added?res.added+' data berhasil diimport, ':'Tidak ada data yang diimport, ')+res.errors.length+' baris dilewati:</p><ul class="kw-import__errors">'+res.errors.slice(0,LIMIT).map(function(e){return '<li><strong>Baris '+e.row+'</strong> '+esc(e.msg)+'</li>';}).join('')+(res.errors.length>LIMIT?'<li>…dan '+(res.errors.length-LIMIT)+' baris lainnya.</li>':'')+'</ul>';toast(res.added?res.added+' data diimport, '+res.errors.length+' dilewati.':'Tidak ada data yang diimport.','error');}box.className='kw-import__result '+cls;box.innerHTML=html;box.hidden=false;box.scrollIntoView({block:'nearest'});}
  function runImport(){var file=els.importFile.files[0];if(!file||!hasExcelLib())return;if(!/\.xlsx$/i.test(file.name)){showImportResult({fatal:'File harus berformat .xlsx.'});return;}els.importSubmit.disabled=true;els.importSubmit.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';readFileBuffer(file).then(function(buf){return new ExcelJS.Workbook().xlsx.load(buf);}).then(function(wb){var ws=wb.worksheets[0];if(!ws)throw new Error('Sheet kosong');return importFromSheet(ws);}).then(function(res){return persistImportedRows(res);}).then(function(res){showImportResult(res);}).catch(function(err){console.error(err);showImportResult({fatal:'File tidak bisa dibaca. Pastikan file .xlsx valid.'});}).then(function(){els.importSubmit.innerHTML=IMPORT_BTN_HTML;els.importFile.value='';els.importSubmit.disabled=true;});}
  $('#kwBtnAdd').addEventListener('click',function(){openForm();});
  $('#kwBtnExport').addEventListener('click',exportExcel);
  $('#kwBtnImport').addEventListener('click',openImport);
  $('#kwBtnTemplate').addEventListener('click',downloadTemplate);
  els.importFile.addEventListener('change',function(){els.importSubmit.disabled=!this.files.length;els.importResult.hidden=true;});
  els.importSubmit.addEventListener('click',runImport);

  /* ---------- Event ---------- */
  $('#kwBtnAdd').addEventListener('click', function () { openForm(); });
  $('#kwBtnExport').addEventListener('click', exportExcel);
  $('#kwBtnImport').addEventListener('click', openImport);
  $('#kwBtnTemplate').addEventListener('click', downloadTemplate);
  els.importFile.addEventListener('change', function () {
    els.importSubmit.disabled = !this.files.length;
    els.importResult.hidden = true;
  });
  els.importSubmit.addEventListener('click', runImport);

  els.search.addEventListener('input', debounce(function () {
    state.q = els.search.value; state.page = 1; render();
  }, 150));

  els.filterDivisi.addEventListener('change', function () { state.divisi = this.value; state.page = 1; render(); });
  els.filterStatus.addEventListener('change', function () { state.status = this.value; state.page = 1; render(); });
  els.perPage.addEventListener('change', function () { state.perPage = parseInt(this.value, 10); state.page = 1; render(); });

  els.pager.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-page]');
    if (!btn || btn.disabled) return;
    state.page = parseInt(btn.getAttribute('data-page'), 10);
    render();
  });

  $$('.kw-sort').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var key = btn.getAttribute('data-sort');
      if (state.sortKey === key) {
        if (state.sortDir === 'asc') state.sortDir = 'desc';
        else { state.sortKey = null; state.sortDir = 'asc'; } // klik ke-3: kembali ke urutan awal
      } else { state.sortKey = key; state.sortDir = 'asc'; }
      state.page = 1;
      render();
    });
  });

  els.tbody.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-action]');
    if (!btn) return;
    var id = parseInt(btn.getAttribute('data-id'), 10);
    var action = btn.getAttribute('data-action');
    if (action === 'view') openDetail(id);
    else if (action === 'edit') openForm(id);
    else if (action === 'delete') openDelete(id);
  });

  /* ---------- Mulai ---------- */
  function loadData() {
    els.tbody.innerHTML = '<tr><td colspan="7" class="kw-empty-note">Memuat data karyawan...</td></tr>';

    Promise.all([
      Api.get('/api/karyawan'),
      Api.get('/api/divisi'),
      Api.get('/api/jabatan'),
    ]).then(function (results) {
      data = results[0];
      nextId = data.reduce(function (m, r) { return Math.max(m, Number(r.id) || 0); }, 0) + 1;
      DIVISI = results[1].map(function (d) { return d.nama; });
      JABATAN = results[2].map(function (j) { return j.nama; });

      fillSelect(els.filterDivisi, true);
      fillSelect(els.form.elements.divisi, false);
      fillOptions(els.form.elements.jabatan, JABATAN, 'Pilih Jabatan');
      render();
    }).catch(function (err) {
      els.tbody.innerHTML = '<tr><td colspan="7" class="kw-empty-note">Gagal memuat data: ' + esc(err.message) + '</td></tr>';
      toast('Gagal memuat data karyawan dari server.', 'error');
    });
  }

  loadData();
})();
