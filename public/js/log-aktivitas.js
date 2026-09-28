document.addEventListener('DOMContentLoaded', function () {
  const body = document.getElementById('body'), empty = document.getElementById('empty'), count = document.getElementById('count'), search = document.getElementById('search'), userFilter = document.getElementById('userFilter'), actionFilter = document.getElementById('actionFilter'), modal = document.getElementById('detailModal');
  let logs = [];
  const esc = SlipUI.esc;

  function load() {
    SlipStore.getActivityLogs().then(function (rows) {
      logs = rows;
      const users = [...new Set(logs.map(x => x.user).filter(Boolean))].sort();
      const acts = [...new Set(logs.map(x => x.action).filter(Boolean))].sort();
      userFilter.innerHTML = '<option value="">Semua user</option>' + users.map(x => '<option>' + esc(x) + '</option>').join('');
      actionFilter.innerHTML = '<option value="">Semua aktivitas</option>' + acts.map(x => '<option>' + esc(x) + '</option>').join('');
      render();
    }).catch(function (err) {
      body.innerHTML = '<tr><td colspan="5">Gagal memuat log: ' + esc(err.message) + '</td></tr>';
    });
  }

  function render() {
    const q = search.value.trim().toLowerCase(), uf = userFilter.value, af = actionFilter.value;
    const data = logs.filter(x => (!q || [x.user, x.action, x.detail].join(' ').toLowerCase().includes(q)) && (!uf || x.user === uf) && (!af || x.action === af));
    body.innerHTML = data.map((x, i) => '<tr><td class="time">' + esc(x.tanggal) + '<br>' + esc(x.jam) + '</td><td class="user">' + esc(x.user || '-') + '</td><td><span class="action">' + esc(x.action || '-') + '</span></td><td class="detail">' + esc(x.detail || '-') + '</td><td><button class="view-btn" data-id="' + esc(x.id) + '" title="Lihat detail"><i class="bi bi-eye"></i></button></td></tr>').join('');
    empty.hidden = data.length > 0;
    count.textContent = 'Menampilkan ' + data.length + ' dari ' + logs.length + ' aktivitas';
  }

  [search, userFilter, actionFilter].forEach(x => x.addEventListener('input', render));
  body.addEventListener('click', e => {
    const b = e.target.closest('[data-id]'); if (!b) return;
    const x = logs.find(a => a.id === b.dataset.id); if (!x) return;
    document.getElementById('modalAction').textContent = x.action || 'Aktivitas';
    document.getElementById('modalContent').innerHTML = '<dl class="detail-list"><dt>User</dt><dd>' + esc(x.user || '-') + '</dd><dt>Tanggal</dt><dd>' + esc(x.tanggal || '-') + ' ' + esc(x.jam || '') + '</dd><dt>Aktivitas</dt><dd>' + esc(x.action || '-') + '</dd><dt>Detail</dt><dd>' + esc(x.detail || '-') + '</dd></dl>';
    modal.hidden = false;
  });
  modal.addEventListener('click', e => { if (e.target.closest('[data-close]')) modal.hidden = true });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') modal.hidden = true });

  document.getElementById('clearLog').addEventListener('click', function () {
    UI.confirm('Hapus seluruh log aktivitas?', { danger: true, title: 'Hapus Log Aktivitas?' }).then(function (ok) {
      if (!ok) return;
      // Catatan: log aktivitas di database TIDAK dihapus otomatis dari sini
      // (server tidak menyediakan endpoint hapus massal untuk menjaga jejak
      // audit). Tombol ini hanya menyegarkan tampilan dan mencatat niat
      // pembersihan sebagai satu entri log baru.
      SlipStore.addActivity('Hapus log aktivitas', 'Permintaan pembersihan log aktivitas (tidak menghapus data di server).').then(load);
    });
  });

  load();
});
