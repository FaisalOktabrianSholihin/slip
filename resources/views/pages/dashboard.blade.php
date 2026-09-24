@extends('layouts.app')

@section('title', 'Dashboard SDM')
@section('page-title', 'Dashboard SDM')
@section('page-subtitle', 'Ringkasan data sumber daya manusia')
@section('page-icon', 'bi-speedometer2')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')

<div class="dashboard">
<!-- KPI -->
<section class="kpi-grid">
<article class="kpi-card">
<svg aria-hidden="true" class="kpi-icon" viewbox="0 0 24 24"><circle cx="12" cy="8" r="3.2"></circle><path d="M5.5 20v-1.5a4.5 4.5 0 0 1 4.5-4.5h4a4.5 4.5 0 0 1 4.5 4.5V20"></path><circle cx="4.5" cy="10" r="2"></circle><circle cx="19.5" cy="10" r="2"></circle></svg>
<div><strong id="kpiTotal">298</strong><span>Total Karyawan</span></div>
</article>
<article class="kpi-card">
<svg aria-hidden="true" class="kpi-icon" viewbox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"></path></svg>
<div><strong id="kpiPkwt">127</strong><span>Total Karyawan PKWT</span></div>
</article>
<article class="kpi-card">
<svg aria-hidden="true" class="kpi-icon" viewbox="0 0 24 24"><circle cx="12" cy="7" r="2.5"></circle><circle cx="6" cy="10" r="2"></circle><circle cx="18" cy="10" r="2"></circle><path d="M3 21c0-3 2-5 4-5M21 21c0-3-2-5-4-5M8 20c0-2.5 1.8-4.5 4-4.5s4 2 4 4.5"></path></svg>
<div><strong id="kpiHl">1.623</strong><span>Total Karyawan HL</span></div>
</article>
<article class="kpi-card">
<svg aria-hidden="true" class="kpi-icon" viewbox="0 0 24 24"><circle cx="12" cy="8" r="3"></circle><path d="M12 3v1M12 12v1M7 8h1M16 8h1M8.5 4.5l.7.7M14.8 10.8l.7.7M15.5 4.5l-.7.7M9.2 10.8l-.7.7"></path><path d="M4 21v-6l8-4 8 4v6z"></path></svg>
<div><strong id="kpiMasaKerja">147</strong><span>Masa Kerja &gt; 5 TH</span></div>
</article>
</section>
<!-- CHARTS -->
<section class="chart-grid">
<article class="card">
<h2 class="card-title">Pendidikan Karyawan</h2>
<div class="chart-box"><canvas id="chartPendidikan"></canvas></div>
</article>
<article class="card">
<h2 class="card-title">Komposisi Status</h2>
<div class="chart-box"><canvas id="chartStatus"></canvas></div>
</article>
<article class="card">
<h2 class="card-title">Usia Karyawan</h2>
<div class="chart-box"><canvas id="chartUsia"></canvas></div>
</article>
</section>
<!-- MATRIKS -->
<section class="card matrix-card">
<h2 class="card-title left">Matriks Komposisi SDM</h2>
<div class="table-wrap">
<table class="matrix" id="matrixTable">
<thead>
<tr>
<th>Komposisi SDM</th>
<th>25–35</th><th>36–45</th><th>&gt;45</th>
<th>S1</th><th>DIII</th><th>SMA</th><th>SMP</th>
</tr>
</thead>
<tbody></tbody>
<tfoot></tfoot>
</table>
</div>
</section>
</div><!-- /.dashboard -->
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
  <script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
