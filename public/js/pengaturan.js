document.addEventListener('DOMContentLoaded', function () {
  const retention = document.getElementById('retentionMonths'), email = document.getElementById('emailEnabled'), wa = document.getElementById('waEnabled'), notice = document.getElementById('saveNotice');

  function load() {
    Promise.all([
      SlipStore.getSettings(),
      SlipStore.getHistory(),
      SlipStore.getPreviewRows(),
      SlipStore.getActivityLogs()
    ]).then(function (results) {
      const s = results[0];
      retention.value = String(s.retentionMonths);
      email.checked = s.emailEnabled;
      wa.checked = s.waEnabled;
      document.getElementById('historyCount').textContent = results[1].length;
      document.getElementById('payrollCount').textContent = results[2].length;
      document.getElementById('activityCount').textContent = results[3].length;
    }).catch(function (err) {
      show('Gagal memuat pengaturan: ' + err.message, true);
    });
  }

  function refreshStats() {
    Promise.all([SlipStore.getHistory(), SlipStore.getPreviewRows(), SlipStore.getActivityLogs()])
      .then(function (r) {
        document.getElementById('historyCount').textContent = r[0].length;
        document.getElementById('payrollCount').textContent = r[1].length;
        document.getElementById('activityCount').textContent = r[2].length;
      });
  }

  function show(msg, error) {
    notice.hidden = false;
    notice.className = 'save-notice' + (error ? ' error' : '');
    notice.innerHTML = '<i class="bi ' + (error ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill') + '"></i> ' + SlipUI.esc(msg);
    setTimeout(() => notice.hidden = true, 3500);
  }

  document.getElementById('saveBtn').addEventListener('click', function () {
    const payload = { retentionMonths: Number(retention.value), emailEnabled: email.checked, waEnabled: wa.checked };
    SlipStore.saveSettings(payload).then(function (s) {
      refreshStats();
      show('Pengaturan berhasil disimpan.');
    }).catch(function (err) {
      show('Gagal menyimpan pengaturan: ' + err.message, true);
    });
  });

  document.getElementById('resetBtn').addEventListener('click', function () {
    retention.value = '1'; email.checked = true; wa.checked = true;
    show('Nilai default dipulihkan di form. Klik Simpan Pengaturan untuk menerapkannya.');
  });

  document.getElementById('cleanupBtn').addEventListener('click', function () {
    SlipStore.cleanupStoredData().then(function () {
      return SlipStore.addActivity('Terapkan retensi', 'Menjalankan pembersihan data slip sesuai periode retensi.');
    }).then(function () {
      refreshStats();
      show('Retensi data berhasil diterapkan.');
    });
  });

  load();
});
