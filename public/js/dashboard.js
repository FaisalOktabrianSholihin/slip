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
    Object.entries(aliases).forEach(([key, variants]) => {
      const node = document.querySelector('[data-status="' + key + '"]');
      const row = statusRows.find(item => variants.includes(item.key) || variants.includes(item.label));
      if (node) node.textContent = fmt(row ? row.jumlah : 0);
    });
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

  /* ===== Donut status ===== */
  new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
      labels: data.status.labels || [],
      datasets: [{
        data: data.status.jumlah || [],
        backgroundColor: ['#176B4D', '#43A67A', '#E6B655', '#4F83CC', '#8D6CAB'],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '72%',
      plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10 } },
        datalabels: {
          color: '#fff',
          font: { weight: 700 },
          formatter: v => v.toLocaleString('id-ID')
        }
      }
    }
  });

  /* ===== Matriks Komposisi SDM ===== */
  (function renderMatrix() {
    const tbody = document.querySelector('#matrixTable tbody');
    const tfoot = document.querySelector('#matrixTable tfoot');
    const headers = data.matrixHeaders || [...data.usia.labels, ...data.pendidikan.labels];
    const head = document.querySelector('#matrixTable thead tr');
    head.innerHTML = '<th>Komposisi SDM</th>' + headers.map((h, i) => `<th style="background:${i < data.usia.labels.length ? '#E8F3ED' : '#EAF0FA'}">${h}</th>`).join('');
    const cols = data.matriks.length ? data.matriks[0].nilai.length : 0;
    const totals = Array(cols).fill(0);

    data.matriks.forEach(row => {
      row.nilai.forEach((v, i) => (totals[i] += v));
      tbody.insertAdjacentHTML(
        'beforeend',
        `<tr><td>${row.nama}</td>${row.nilai.map(v => `<td>${v}</td>`).join('')}</tr>`
      );
    });

    tfoot.innerHTML = `<tr><td>Jumlah</td>${totals.map(v => `<td>${v}</td>`).join('')}</tr>`;
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