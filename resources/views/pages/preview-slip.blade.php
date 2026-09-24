@extends('layouts.app')

@section('title', 'Preview Slip')
@section('page-title', 'Preview Slip')
@section('page-subtitle', 'Periksa slip hasil import sebelum dikirim melalui email atau WhatsApp')
@section('page-icon', 'bi-eye-fill')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/kirim-slip.css') }}">
  <link rel="stylesheet" href="{{ asset('css/preview-slip.css') }}">
@endpush

@section('content')

<!-- ====== KONTEN HALAMAN PREVIEW SLIP ====== -->
<div class="ks ps">
<div class="ks-card">
<div class="ps-top">
<a class="ks-btn ks-btn--outline" href="{{ route('kirim-slip') }}">
<i aria-hidden="true" class="bi bi-arrow-left"></i> <span>Kembali ke Data Karyawan</span>
</a>
<p aria-live="polite" class="ps-summary" id="psSummary"></p>
</div>
<!-- Daftar slip (disembunyikan bila kosong) -->
<div hidden="" id="psContent">
<!-- Cari divisi -->
<div class="ps-search" id="psCombo">
<label class="ps-sr" for="psSearch">Cari divisi</label>
<i aria-hidden="true" class="bi bi-search ps-search__icon"></i>
<input aria-autocomplete="list" aria-controls="psSuggest" aria-expanded="false" autocomplete="off" id="psSearch" placeholder="Cari atau ketik divisi..." role="combobox" type="text"/>
<ul class="ps-suggest" hidden="" id="psSuggest" role="listbox"></ul>
</div>
<!-- Slip per divisi (diisi preview-slip.js) -->
<div class="ps-list" id="psList"></div>
<!-- Kirim semua -->
<div class="ps-foot">
<button class="ks-btn ks-btn--primary" id="psSendAll" type="button">
<i aria-hidden="true" class="bi bi-envelope-fill"></i> <span>Kirim Semua Email</span>
</button>
<button class="ks-btn ks-btn--outline" id="psSendAllWa" title="Kirim semua slip WhatsApp" type="button">
<i aria-hidden="true" class="bi bi-whatsapp"></i> <span>Kirim Semua WhatsApp</span>
</button>
</div>
</div>
<!-- Keadaan kosong -->
<div class="ps-empty" hidden="" id="psEmpty">
<i aria-hidden="true" class="bi bi-inbox"></i>
<p class="ps-empty__title" id="psEmptyTitle">Belum ada slip untuk dipreview</p>
<p class="ps-empty__text" id="psEmptyText">Import Excel gaji terlebih dahulu pada halaman Data Karyawan.</p>
<div class="ps-empty__actions">
<a class="ks-btn ks-btn--primary" href="{{ route('kirim-slip') }}"><i aria-hidden="true" class="bi bi-file-earmark-spreadsheet"></i> <span>Ke Import Gaji</span></a>
<a class="ks-btn ks-btn--outline" href="{{ route('detail-riwayat') }}"><i aria-hidden="true" class="bi bi-clock-history"></i> <span>Lihat Riwayat</span></a>
</div>
</div>
</div>
</div>
<!-- ====== SELESAI KONTEN HALAMAN ====== -->
@endsection

@section('modals')
<div aria-live="polite" class="ks-toasts" id="ksToasts" role="status"></div>
@endsection

@push('scripts')
  <script>window.ESLIP_LOGO_URL = @json(asset('assets/logo.png'));</script>
  <script src="{{ asset('js/slip-common.js') }}"></script>
  <script src="{{ asset('js/preview-slip.js') }}"></script>
@endpush
