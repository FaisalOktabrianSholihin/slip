document.addEventListener('DOMContentLoaded', function () {
  const retention = document.getElementById('retentionMonths'), email = document.getElementById('emailEnabled'), wa = document.getElementById('waEnabled'), notice = document.getElementById('saveNotice'), senderName = document.getElementById('senderName'), senderEmail = document.getElementById('senderEmail'), kpiStatus = document.getElementById('kpiStatus'), kpiMasaKerja = document.getElementById('kpiMasaKerja');

  const senderPassword = document.getElementById('senderPassword');
  const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  const DEFAULT_KPIS = ['total', 'status', 'masaKerja5'];

  function applySettings(s) {
    const months = String(s.retentionMonths || 1);
    if (!Array.from(retention.options).some(o => o.value === months)) {
      const o = document.createElement('option'); o.value = months; o.textContent = months + ' bulan'; retention.appendChild(o);
    }
    retention.value = months;
    email.checked = !!s.emailEnabled;
    wa.checked = !!s.waEnabled;
    if (senderName) senderName.value = s.senderName || 'E-Slip Gaji Mitratani';
    if (senderEmail) senderEmail.value = s.senderEmail || '';
    if (senderPassword) { senderPassword.value = ''; senderPassword.placeholder = s.senderPasswordSet ? '•••••••• (tersimpan di .env, kosongkan jika tidak diubah)' : 'Belum ada password di .env'; }
    const kpis = Array.isArray(s.dashboardKpis) ? s.dashboardKpis : DEFAULT_KPIS;
    if (kpiStatus) kpiStatus.checked = kpis.includes('status');
    if (kpiMasaKerja) kpiMasaKerja.checked = kpis.includes('masaKerja5');
  }

  // Tiap angka dimuat sendiri: user tanpa izin riwayat/log (403) tidak membuat seluruh halaman gagal.
  function refreshStats() {
    [['historyCount', () => SlipStore.getHistory()], ['payrollCount', () => SlipStore.getPreviewRows()], ['activityCount', () => SlipStore.getActivityLogs()]]
      .forEach(function (x) { x[1]().then(r => setText(x[0], Array.isArray(r) ? r.length : 0)).catch(() => setText(x[0], '-')); });
  }

  function load() {
    SlipStore.getSettings().then(applySettings).catch(function (err) { show('Gagal memuat pengaturan: ' + err.message, true); });
    refreshStats();
  }

  function show(msg, error) {
    notice.hidden = false;
    notice.className = 'save-notice' + (error ? ' error' : '');
    notice.innerHTML = '<i class="bi ' + (error ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill') + '"></i> ' + SlipUI.esc(msg);
    clearTimeout(show._t);
    show._t = setTimeout(() => notice.hidden = true, error ? 7000 : 4000);
    notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  document.getElementById('saveBtn').addEventListener('click', function () {
    const btn = this;
    const payload = { retentionMonths: Number(retention.value), emailEnabled: email.checked, waEnabled: wa.checked, senderName: senderName?.value.trim(), senderEmail: senderEmail?.value.trim(), senderPassword: senderPassword?.value || '', dashboardKpis: ['total', ...(kpiStatus?.checked ? ['status'] : []), ...(kpiMasaKerja?.checked ? ['masaKerja5'] : [])] };
    if (!payload.senderName || !payload.senderEmail) { show('Nama dan alamat email pengirim wajib diisi.', true); return; }
    btn.disabled = true;
    SlipStore.saveSettings(payload).then(function (saved) {
      applySettings(saved);
      refreshStats();
      show('Pengaturan berhasil disimpan.');
    }).catch(function (err) {
      show('Gagal menyimpan pengaturan: ' + err.message, true);
    }).then(function () { btn.disabled = false; });
  });

  document.getElementById('resetBtn').addEventListener('click', function () {
    retention.value = '1'; email.checked = true; wa.checked = true; if (senderName) senderName.value = 'E-Slip Gaji Mitratani'; if (kpiStatus) kpiStatus.checked = true; if (kpiMasaKerja) kpiMasaKerja.checked = true;
    show('Nilai default dipulihkan di form. Klik Simpan Pengaturan untuk menerapkannya.');
  });

  document.getElementById('cleanupBtn').addEventListener('click', function () {
    const btn = this, months = retention.value;
    UI.confirm('Riwayat pengiriman dan data payroll yang lebih lama dari ' + months + ' bulan akan dihapus permanen. Log aktivitas tidak ikut dihapus. Lanjutkan?', { danger: true, title: 'Terapkan retensi sekarang?', okText: 'Ya, Bersihkan' }).then(function (ok) {
      if (!ok) return;
      btn.disabled = true;
      SlipStore.cleanupStoredData().then(function (res) {
        refreshStats();
        show('Retensi diterapkan: ' + (res.riwayat || 0) + ' riwayat dan ' + (res.payroll || 0) + ' payroll lama dihapus.');
      }).catch(function (err) {
        show('Gagal menerapkan retensi: ' + err.message, true);
      }).then(function () { btn.disabled = false; });
    });
  });

  load();
});
