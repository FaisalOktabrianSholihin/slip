@extends('layouts.app')

@section('title', 'Log Aktivitas')
@section('page-title', 'Log Aktivitas')
@section('page-subtitle', 'Jejak aktivitas pengguna di dalam sistem')
@section('page-icon', 'bi-journal-text')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/log-aktivitas.css') }}">
@endpush

@section('content')
<div class="log-page"><div class="log-head"><div><h1>Aktivitas Sistem</h1><p>Catat login, import payroll, pengiriman slip, dan perubahan pengaturan agar aktivitas tiap user mudah ditelusuri.</p></div><button class="clear-btn" id="clearLog"><i class="bi bi-trash3"></i> Bersihkan Log</button></div><div class="log-card"><div class="log-toolbar"><div class="search"><i class="bi bi-search"></i><input id="search" placeholder="Cari user atau aktivitas..."/></div><select id="userFilter"><option value="">Semua user</option></select><select id="actionFilter"><option value="">Semua aktivitas</option></select></div><div class="table-wrap"><table><thead><tr><th>Waktu</th><th>User</th><th>Aktivitas</th><th>Detail</th><th></th></tr></thead><tbody id="body"></tbody></table><div class="empty" hidden="" id="empty"><i class="bi bi-journal-x"></i><strong>Belum ada log aktivitas</strong><span>Aktivitas akan muncul setelah user melakukan tindakan di sistem.</span></div></div><div class="log-foot" id="count">0 aktivitas</div></div></div>
@endsection

@section('modals')
<div class="detail-modal" hidden="" id="detailModal"><div class="detail-backdrop" data-close=""></div><div class="detail-dialog"><button class="modal-close" data-close=""><i class="bi bi-x-lg"></i></button><span class="eyebrow">DETAIL AKTIVITAS</span><h2 id="modalAction">-</h2><div id="modalContent"></div></div></div>
@endsection

@push('scripts')
  <script src="{{ asset('js/slip-common.js') }}"></script>
  <script src="{{ asset('js/log-aktivitas.js') }}"></script>
@endpush
