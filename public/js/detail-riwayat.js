let DATA = [];
const tableBody = document.getElementById('tableBody'), emptyState = document.getElementById('emptyState'), searchInput = document.getElementById('searchDivisi'), footerCount = document.getElementById('footerCount'), entriesPerPage = document.getElementById('entriesPerPage'), pagination = document.getElementById('pagination');
let riwayatPage = 1;
const filterTrigger = document.getElementById('filterTrigger'), filterDropdown = document.getElementById('filterDropdown'), filterBulanSelect = document.getElementById('filterBulanSelect'), filterTahunSelect = document.getElementById('filterTahunSelect');
const filterApply = document.getElementById('filterApply'), filterReset = document.getElementById('filterReset'); const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const filterStatus = document.getElementById('filterStatus');
// Default filter = bulan berjalan (sebelumnya terkunci ke September 2026 sehingga data bulan lain tampak kosong).
let activeMonthFilter = (function () {
  const now = new Date(), y = String(now.getFullYear());
  if (!Array.from(filterTahunSelect.options).some(o => o.value === y)) { const o = document.createElement('option'); o.value = y; o.textContent = y; filterTahunSelect.appendChild(o); }
  filterBulanSelect.value = String(now.getMonth() + 1);
  filterTahunSelect.value = y;
  filterTrigger.title = `${NAMA_BULAN[now.getMonth()]} ${y}`;
  filterTrigger.classList.add('is-active');
  return `${y}-${String(now.getMonth() + 1).padStart(2, '0')}`;
})();
function esc(v) { return SlipUI.esc(v) } function monthKey(t) { const p = String(t || '').split('-'); return p.length === 3 ? `${p[2]}-${p[1]}` : '' } function initials(n) { return String(n || 'K').replace(/^(Pak|Mbak|Bu|Ibu)\s+/i, '').trim().split(/\s+/).map(x => x[0]).join('').slice(0, 2).toUpperCase() } function colorFor(n) { let x = 0; for (const c of String(n || '')) x += c.charCodeAt(0); return ['#078b4c', '#8B5E3C', '#3D5A80', '#7A5980', '#A0522D', '#08743e'][x % 6] }
function channelLabel(c) { return c === 'wa' ? 'WhatsApp' : 'Email' }
function getFilteredData() { const q = searchInput.value.trim().toLowerCase(), st = filterStatus.value; return DATA.filter(r => (!st || String(r.status || '').toLowerCase() === st) && (!q || String(r.divisi || '').toLowerCase().includes(q) || String(r.nama || '').toLowerCase().includes(q) || String(r.nik || '').includes(q)) && (!activeMonthFilter || monthKey(r.tanggal) === activeMonthFilter)) }
function openFilter() { filterDropdown.hidden = false; filterTrigger.setAttribute('aria-expanded', 'true') } function closeFilter() { filterDropdown.hidden = true; filterTrigger.setAttribute('aria-expanded', 'false') }
filterTrigger.addEventListener('click', e => { e.stopPropagation(); filterDropdown.hidden ? openFilter() : closeFilter() }); document.addEventListener('click', e => { if (!filterDropdown.hidden && !document.getElementById('filterField').contains(e.target)) closeFilter() }); document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFilter() }); filterApply.addEventListener('click', () => { activeMonthFilter = `${filterTahunSelect.value}-${String(filterBulanSelect.value).padStart(2, '0')}`; filterTrigger.title = `${NAMA_BULAN[Number(filterBulanSelect.value) - 1]} ${filterTahunSelect.value}`; filterTrigger.classList.add('is-active'); closeFilter(); riwayatPage = 1; render() }); filterReset.addEventListener('click', () => { activeMonthFilter = ''; filterTrigger.classList.remove('is-active'); filterTrigger.title = 'Semua bulan'; closeFilter(); riwayatPage = 1; render() });
function render() {
  const filtered = getFilteredData();
  const perPage = parseInt(entriesPerPage.value, 10) || 20;
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  riwayatPage = Math.min(riwayatPage, totalPages);
  const start = (riwayatPage - 1) * perPage;
  const pageRows = filtered.slice(start, start + perPage);

  tableBody.innerHTML = ''; emptyState.style.display = filtered.length ? 'none' : 'block';
  pageRows.forEach((r, i) => { const tr = document.createElement('tr'); const ok = String(r.status).toLowerCase() === 'berhasil'; const dest = r.channel === 'wa' ? r.wa : r.email; tr.innerHTML = `<td>${start + i + 1}</td><td><div class="name-cell"><span class="avatar" style="background:${colorFor(r.nama)}">${initials(r.nama)}</span>${esc(r.nama)}</div><small class="nik-sub">NIK ${esc(r.nik || '-')}</small></td><td class="channel-cell"><span class="channel-pill ${r.channel === 'wa' ? 'wa' : 'email'}"><i class="bi ${r.channel === 'wa' ? 'bi-whatsapp' : 'bi-envelope-fill'}"></i>${channelLabel(r.channel)}</span><small>${esc(dest || '-')}</small></td><td><span class="divisi-tag">${esc(r.divisi || '-')}</span></td><td><div class="file-cell"><i class="bi bi-file-earmark-pdf-fill"></i><span class="file-name" title="${esc(r.file || '')}">${esc(r.file || '-')}</span><button class="pdf-btn" data-pdf="${esc(r.id || r.nik || '')}" title="Lihat PDF"><i class="bi bi-eye-fill"></i></button></div></td><td><span class="status-pill ${ok ? 'status-ok' : 'status-bad'}"><span class="dot"></span>${esc(r.status || '-')}</span>${!ok && r.keterangan ? `<small class="fail-reason" title="${esc(r.keterangan)}"><i class="bi bi-exclamation-triangle-fill"></i> ${esc(r.keterangan)}</small>` : ''}${r.bisaKirimUlang ? `<button type="button" class="resend-btn" data-resend="${esc(r.payrollId)}" title="Kirim ulang slip ini"><i class="bi bi-arrow-repeat"></i> Kirim ulang</button>` : ''}</td><td class="tanggal-cell"><span class="date">${esc(r.tanggal || '-')}</span><br>${esc(r.jam || '-')}</td>`; tableBody.appendChild(tr) });

  const nGagal = filtered.filter(r => String(r.status).toLowerCase() === 'gagal').length; footerCount.textContent = (filtered.length === 0 ? 'Menampilkan 0 dari 0 data' : `Menampilkan ${start + 1}–${Math.min(start + perPage, filtered.length)} dari ${filtered.length} data`) + (nGagal ? ` · ${nGagal} gagal` : '');
  renderRiwayatPagination(totalPages);
}
function renderRiwayatPagination(totalPages) {
  let html = `<button class="page-btn" data-page="prev"${riwayatPage === 1 ? ' disabled' : ''}>Prev</button>`;
  for (let p = 1; p <= totalPages; p++) { html += `<button class="page-btn${p === riwayatPage ? ' active' : ''}" data-page="${p}">${p}</button>`; }
  html += `<button class="page-btn" data-page="next"${riwayatPage === totalPages ? ' disabled' : ''}>Next</button>`;
  pagination.innerHTML = html;
}
pagination.addEventListener('click', e => { const b = e.target.closest('.page-btn'); if (!b || b.disabled) return; const p = b.dataset.page; riwayatPage = p === 'prev' ? riwayatPage - 1 : p === 'next' ? riwayatPage + 1 : parseInt(p, 10); render(); });
entriesPerPage.addEventListener('change', () => { riwayatPage = 1; render(); });
filterStatus.addEventListener('change', () => { riwayatPage = 1; render(); });
tableBody.addEventListener('click', async e => {
  const b = e.target.closest('[data-resend]'); if (!b || b.disabled) return;
  b.disabled = true; b.innerHTML = '<i class="bi bi-arrow-repeat resend-spin"></i> Mengirim...';
  try {
    const res = await Api.post('/api/slip/' + encodeURIComponent(b.dataset.resend) + '/kirim');
    if (res && res.ok) UI.toast('Slip berhasil dikirim ulang.'); else UI.toast('Masih gagal: ' + ((res && res.reason) || 'lihat riwayat terbaru'), 'error');
  } catch (err) { UI.toast('Gagal mengirim ulang: ' + (err.message || 'coba lagi'), 'error'); }
  loadHistory();
});
function previewPdf(row) {
  // Tampilkan PDF slip asli dari server: file yang sama dengan lampiran email,
  // jadi angkanya pasti sama (bukan lagi dirakit ulang di browser dari row.slip).
  if (!row.pdfUrl) {
    if (window.SlipUI && SlipUI.toast) SlipUI.toast('Slip belum tersedia untuk riwayat ini.', 'error');
    return;
  }
  const modal = document.createElement('div');
  modal.className = 'pdf-modal';
  modal.innerHTML = `<div class="pdf-backdrop" data-close></div><div class="pdf-dialog"><div class="pdf-head"><div><span>DOKUMEN RAHASIA</span><strong>Preview PDF Slip Gaji</strong></div><button data-close><i class="bi bi-x-lg"></i></button></div><div style="padding:14px 18px 0;background:#fff"><iframe src="${esc(row.pdfUrl)}" title="Slip gaji ${esc(row.nama || '')}" style="width:100%;height:72vh;border:1px solid #dfe5e1;border-radius:8px;background:#fff"></iframe><div class="pdf-foot" style="padding:10px 0;font-size:12px;color:#5b6a61">${esc(row.nama || '-')} · File: ${esc(row.file || '-')} · Status: ${esc(row.status || '-')} · Dikirim melalui ${channelLabel(row.channel)}</div></div><div class="pdf-actions"><a class="print-btn" href="${esc(row.pdfUrl)}" target="_blank" rel="noopener" style="text-decoration:none"><i class="bi bi-box-arrow-up-right"></i> Buka di tab baru</a><button class="close-btn" data-close>Tutup</button></div></div>`;
  document.body.appendChild(modal);
  modal.addEventListener('click', e => { if (e.target.closest('[data-close]')) modal.remove() });
}
tableBody.addEventListener('click', e => { const b = e.target.closest('[data-pdf]'); if (!b) return; const row = DATA.find(x => String(x.id || x.nik) === String(b.dataset.pdf)); if (row) previewPdf(row) }); searchInput.addEventListener('input', () => { riwayatPage = 1; render(); });

function loadHistory() {
  if (window.SlipStore && SlipStore.getHistory) {
    SlipStore.getHistory().then(function (rows) { DATA = rows; render(); }).catch(function (err) {
      tableBody.innerHTML = '<tr><td colspan="6">Gagal memuat riwayat: ' + esc(err.message) + '</td></tr>';
    });
  } else { render(); }
}
loadHistory();

document.getElementById('exportBtn').addEventListener('click', async () => { const wb = new ExcelJS.Workbook(), sh = wb.addWorksheet('Riwayat Slip'); sh.columns = [{ header: 'No', key: 'no', width: 6 }, { header: 'Nama', key: 'nama', width: 24 }, { header: 'NIK', key: 'nik', width: 12 }, { header: 'Channel', key: 'channel', width: 15 }, { header: 'Tujuan', key: 'tujuan', width: 30 }, { header: 'Divisi', key: 'divisi', width: 15 }, { header: 'File PDF', key: 'file', width: 45 }, { header: 'Status', key: 'status', width: 14 }, { header: 'Tanggal', key: 'tanggal', width: 14 }, { header: 'Jam', key: 'jam', width: 10 }]; getFilteredData().forEach((r, i) => sh.addRow({ no: i + 1, nama: r.nama, nik: r.nik, channel: channelLabel(r.channel), tujuan: r.channel === 'wa' ? r.wa : r.email, divisi: r.divisi, file: r.file, status: r.status, tanggal: r.tanggal, jam: r.jam })); sh.getRow(1).font = { bold: true, color: { argb: 'FFFFFFFF' } }; sh.getRow(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF078B4C' } }; const buf = await wb.xlsx.writeBuffer(), a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([buf])); a.download = 'riwayat-slip.xlsx'; a.click(); setTimeout(() => URL.revokeObjectURL(a.href), 500); });
document.getElementById('hapusBtn').addEventListener('click', () => {
  UI.confirm('Hapus seluruh riwayat slip yang tersimpan?', { danger: true, title: 'Hapus Riwayat?' }).then(ok => {
    if (!ok) return;
    // Catatan: server TIDAK menyediakan endpoint hapus massal riwayat
    // (data histori pengiriman sengaja tidak dihapus otomatis demi jejak
    // audit). Sesuaikan/tambahkan endpoint DELETE khusus bila memang
    // dibutuhkan.
    if (window.SlipStore && SlipStore.addActivity) SlipStore.addActivity('Hapus riwayat', 'Permintaan hapus seluruh riwayat slip (tidak menghapus data di server).').then(() => window.location.reload());
    else window.location.reload();
  });
});
