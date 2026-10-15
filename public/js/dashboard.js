/* ===== Data ditarik dari /api/dashboard-summary (database db_indukk) ===== */
function initDashboard(data) {


  /* ===== Dua kartu ringkasan utama ===== */
  (function renderKpi() {
    const kpi = data.kpi || {};
    const fmt = n => Number(n || 0).toLocaleString('id-ID');
    const totalEl = document.getElementById('kpiTotal');
    const tenureEl = document.getElementById('kpiMasaKerja');
    if (totalEl) totalEl.textContent = fmt(kpi.total ?? data.totalKaryawan ?? 0);
    if (tenureEl) tenureEl.textContent = fmt(kpi.masaKerja5 ?? 0);
    const statusRows = Array.isArray(kpi.status) ? kpi.status : [];
    const aliases = {
      pkwtt: ['pkwtt', 'PKWTT'], pkwt: ['pkwt', 'PKWT'],
      pkwt_hl: ['pkwt_hl', 'PKWT-HL'], pkwt_os: ['pkwt_os', 'PKWT-OS'],
      penugasan: ['penugasan', 'Karyawan Penugasan']
    };
    // Pengaturan > Kotak Dashboard: tampilkan/sembunyikan komponen ringkasan.
    const pilih = Array.isArray(kpi.dashboardKpis) ? kpi.dashboardKpis : ['total', 'status', 'masaKerja5'];
    const statusBox = document.getElementById('kpiStatusBreakdown');
    const tenureCard = document.getElementById('kpiMasaKerjaCard');
    if (statusBox) statusBox.hidden = !pilih.includes('status');
    if (tenureCard) tenureCard.hidden = !pilih.includes('masaKerja5');
    const grid = tenureCard && tenureCard.parentElement;
    if (grid) grid.classList.toggle('kpi-grid--single', !pilih.includes('masaKerja5'));
    Object.entries(aliases).forEach(([key, variants]) => {
      const node = document.querySelector('[data-status="' + key + '"]');
      const row = statusRows.find(item => variants.includes(item.key) || variants.includes(item.label));
      if (node) node.textContent = fmt(row ? row.jumlah : 0);
    });
  })();

  /* ===== Keterangan tanggal pembaruan data karyawan ===== */
  (function renderUpdated() {
    const el = document.querySelector('#kpiUpdated span');
    if (!el) return;
    const d = data.lastUpdate ? new Date(data.lastUpdate) : null;
    if (!d || isNaN(d)) { el.textContent = 'Belum ada data karyawan yang diisi.'; return; }
    const tgl = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    const jam = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');
    el.textContent = 'Data karyawan terakhir diperbarui pada ' + tgl + ', pukul ' + jam + ' WIB (berdasarkan pengisian terakhir di menu Karyawan).';
  })();

  /* ===== Warna dari CSS variables ===== */
  const css = getComputedStyle(document.documentElement);
  const C = {
    bar: css.getPropertyValue('--g-300').trim(),
    line: css.getPropertyValue('--g-800').trim(),
    dark: css.getPropertyValue('--g-700').trim(),
    text: css.getPropertyValue('--muted').trim()
  };

  Chart.register(ChartDataLabels);
  Chart.defaults.font.family = '"Plus Jakarta Sans", system-ui, sans-serif';
  Chart.defaults.color = C.text;

  // Saat ukuran berubah (sidebar dibuka/ditutup) gambar ulang langsung di tiap frame,
  // tanpa animasi tambahan, supaya grafik mengikuti kotaknya dengan mulus.
  Chart.defaults.transitions.resize = { animation: { duration: 0 } };

  const persen = arr => {
    const total = arr.reduce((a, b) => a + b, 0);
    return arr.map(v => (total ? Math.round((v / total) * 100) : 0));
  };

  /* ===== Chart batang + garis persentase ===== */
  function comboChart(canvasId, labels, jumlah, barColor) {
    const pct = persen(jumlah);
    const maxVal = Math.max(...jumlah);
    new Chart(document.getElementById(canvasId), {
      data: {
        labels,
        datasets: [
          {
            type: 'bar',
            label: 'Jumlah',
            data: jumlah,
            backgroundColor: barColor || C.bar,
            borderRadius: 4,
            maxBarThickness: 44,
            yAxisID: 'y',
            order: 2,
            datalabels: {
              anchor: 'center', align: 'center', color: C.line, font: { weight: 700 },
              // bar yang terlalu pendek: angkanya dipindah ke label garis
              display: ctx => ctx.dataset.data[ctx.dataIndex] >= maxVal * 0.1
            }
          },
          {
            type: 'line',
            label: '%',
            // titik garis = tinggi batang (skala sumbu sama), persentase hanya jadi label
            data: jumlah,
            borderColor: C.line,
            borderDash: [7, 5],
            borderWidth: 2,
            pointRadius: 0,
            tension: 0,
            yAxisID: 'y',
            order: 1,
            datalabels: {
              align: 'top', anchor: 'end', color: C.line, font: { weight: 600 },
              formatter: (v, ctx) =>
                v >= maxVal * 0.1
                  ? pct[ctx.dataIndex] + '%'
                  : `${v} (${pct[ctx.dataIndex]}%)`
            }
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 24 } },
        plugins: {
          legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10 } },
          tooltip: { mode: 'index', intersect: false }
        },
        scales: {
          x: { grid: { display: false } },
          y: { display: false, beginAtZero: true, suggestedMax: maxVal * 1.25 }
        }
      }
    });
  }

  comboChart('chartPendidikan', data.pendidikan.labels, data.pendidikan.jumlah, '#4F83CC');
  comboChart('chartUsia', data.usia.labels, data.usia.jumlah, '#43A67A');

  /* ===== Donut status (tampil dalam persen saja) ===== */
  const statusData = data.status.jumlah || [];
  const statusTotal = statusData.reduce((a, b) => a + Number(b || 0), 0);
  const pctText = v => {
    if (!statusTotal || !v) return '0%';
    const p = (Number(v) / statusTotal) * 100;
    return (Number.isInteger(Math.round(p * 10) / 10) ? Math.round(p) : (Math.round(p * 10) / 10).toLocaleString('id-ID')) + '%';
  };
  new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
      labels: data.status.labels || [],
      datasets: [{
        data: statusData,
        backgroundColor: ['#176B4D', '#43A67A', '#E6B655', '#4F83CC', '#8D6CAB'],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10 } },
        tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + pctText(ctx.parsed) } },
        datalabels: {
          color: '#fff',
          font: { weight: 700 },
          display: ctx => Number(ctx.dataset.data[ctx.dataIndex]) > 0,
          formatter: v => pctText(v)
        }
      }
    }
  });

  /* ===== Matriks Komposisi SDM =====
     Kolom: [Umur ... | Total Umur] [Pendidikan ... | Total Pendidikan]
     Baris paling bawah: total tiap kolom (atas ke bawah). */
  (function renderMatrix() {
    const table = document.getElementById('matrixTable');
    const thead = table.querySelector('thead');
    const tbody = table.querySelector('tbody');
    const tfoot = table.querySelector('tfoot');
    const ageLabels = data.usia.labels || [];
    const eduLabels = data.pendidikan.labels || [];
    const nAge = ageLabels.length, nEdu = eduLabels.length;
    const sum = arr => arr.reduce((a, b) => a + Number(b || 0), 0);

    thead.innerHTML =
      '<tr>' +
      '<th rowspan="2" class="mx-first">Komposisi SDM</th>' +
      '<th colspan="' + (nAge + 1) + '" class="mx-group mx-age-head">Umur</th>' +
      '<th colspan="' + (nEdu + 1) + '" class="mx-group mx-edu-head">Pendidikan</th>' +
      '</tr><tr>' +
      ageLabels.map(h => '<th class="mx-age-sub">' + h + '</th>').join('') +
      '<th class="mx-age-sub mx-total">Total</th>' +
      eduLabels.map(h => '<th class="mx-edu-sub">' + h + '</th>').join('') +
      '<th class="mx-edu-sub mx-total">Total</th>' +
      '</tr>';

    const colTotals = Array(nAge + nEdu).fill(0);
    tbody.innerHTML = '';
    (data.matriks || []).forEach(row => {
      const ages = row.nilai.slice(0, nAge), edus = row.nilai.slice(nAge, nAge + nEdu);
      row.nilai.forEach((v, i) => { colTotals[i] += Number(v || 0); });
      tbody.insertAdjacentHTML('beforeend',
        '<tr><td class="mx-first">' + row.nama + '</td>' +
        ages.map((v, i) => '<td class="mx-age' + (i === 0 ? ' mx-sep' : '') + '">' + v + '</td>').join('') +
        '<td class="mx-age mx-total">' + sum(ages) + '</td>' +
        edus.map((v, i) => '<td class="mx-edu' + (i === 0 ? ' mx-sep' : '') + '">' + v + '</td>').join('') +
        '<td class="mx-edu mx-total">' + sum(edus) + '</td></tr>');
    });

    const ageTot = colTotals.slice(0, nAge), eduTot = colTotals.slice(nAge);
    tfoot.innerHTML =
      '<tr><td class="mx-first">Total</td>' +
      ageTot.map((v, i) => '<td class="mx-age' + (i === 0 ? ' mx-sep' : '') + '">' + v + '</td>').join('') +
      '<td class="mx-age mx-total">' + sum(ageTot) + '</td>' +
      eduTot.map((v, i) => '<td class="mx-edu' + (i === 0 ? ' mx-sep' : '') + '">' + v + '</td>').join('') +
      '<td class="mx-edu mx-total">' + sum(eduTot) + '</td></tr>';
  })();


  /* ===== Resize grafik sinkron dengan layout =====
     ResizeObserver dijalankan browser SETELAH layout dihitung dan SEBELUM
     frame digambar. Dengan menggambar ulang grafik langsung di sini, grafik
     selalu pas dengan kartunya di setiap frame (tanpa jeda satu frame).
     Tidak perlu timer/durasi, jadi otomatis mengikuti transisi sidebar
     berapa pun lamanya, juga saat jendela browser diubah ukurannya. */
  (function syncChartResize() {
    if (!('ResizeObserver' in window)) return;

    const observer = new ResizeObserver(function (entries) {
      entries.forEach(function (entry) {
        const canvas = entry.target.querySelector('canvas');
        const chart = canvas && Chart.getChart(canvas);
        if (chart) chart.resize();
      });
    });

    document.querySelectorAll('.chart-box').forEach(function (box) {
      observer.observe(box);
    });
  })();

} // akhir initDashboard()

Api.get('/api/dashboard-summary')
  .then(function (data) { initDashboard(data); })
  .catch(function (err) {
    console.error('Gagal memuat data dashboard:', err);
    initDashboard({
      pendidikan: { labels: ['SD', 'SLTP', 'SLTA', 'PT'], jumlah: [0, 0, 0, 0] },
      usia: { labels: ['25–35', '36–45', '>45'], jumlah: [0, 0, 0] },
      status: { labels: ['PKWTT', 'PKWT', 'PKWT-HL', 'PKWT-OS', 'Karyawan Penugasan'], jumlah: [0, 0, 0, 0, 0] },
      matrixHeaders: ['25–35', '36–45', '>45', 'S1', 'DIII', 'SMA', 'SMP'],
      kpi: { dashboardKpis: ['total', 'status', 'masaKerja5'], total: 0, masaKerja5: 0 },
      matriks: [
        { nama: 'PKWTT', nilai: [0, 0, 0, 0, 0, 0, 0] },
        { nama: 'PKWT', nilai: [0, 0, 0, 0, 0, 0, 0] },
        { nama: 'PKWT-HL', nilai: [0, 0, 0, 0, 0, 0, 0] },
        { nama: 'PKWT-OS', nilai: [0, 0, 0, 0, 0, 0, 0] },
        { nama: 'Karyawan Penugasan', nilai: [0, 0, 0, 0, 0, 0, 0] }
      ]
    });
  });