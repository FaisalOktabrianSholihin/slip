@extends('layouts.app')

@section('title', 'Kirim Slip Gaji')
@section('page-title', 'Kirim Slip Gaji')
@section('page-subtitle', 'Import data gaji dari Excel, buat slip otomatis, lalu kirimkan ke Email atau WhatsApp')
@section('page-icon', 'bi-send-fill')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/kirim-slip.css') }}">
@endpush

@section('content')
<div class="ks">
<section class="ks-card">
<div class="ks-import-head">
<div>
<h1 class="ks-title"><span class="ks-title__icon"><i class="bi bi-file-earmark-spreadsheet-fill"></i></span>Import Data Gaji</h1>
<p class="ks-sub">Upload Excel untuk membuat slip gaji secara otomatis.</p>
</div>
<span class="ks-import-badge"><i class="bi bi-shield-check"></i> Validasi otomatis</span>
</div>
<div class="ks-info-grid">
<div class="ks-info"><i class="bi bi-1-circle-fill"></i><div><strong>NIK wajib</strong><span>8 digit dan harus terdaftar di master Karyawan.</span></div></div>
<div class="ks-info"><i class="bi bi-2-circle-fill"></i><div><strong>Kontak dari Master</strong><span>Email dan WhatsApp diambil otomatis dari data Karyawan.</span></div></div>
<div class="ks-info"><i class="bi bi-3-circle-fill"></i><div><strong>Prioritas Channel</strong><span>Email diprioritaskan jika Email dan WhatsApp sama-sama tersedia.</span></div></div>
<div class="ks-info"><i class="bi bi-4-circle-fill"></i><div><strong>Slip otomatis</strong><span>Setelah import, slip siap dipreview dan dikirim.</span></div></div>
</div>
<div class="ks-field ks-excel-field">
<label>Pilih Excel Gaji (.xlsx)</label>
<input accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="ks-file" hidden="" id="ksExcel" type="file"/>
<button class="ks-file-chooser" id="ksChooseFile" type="button"><i class="bi bi-folder2-open"></i><span>Choose File</span></button>
<p class="ks-file-name" id="ksExcelName"><i class="bi bi-file-earmark-excel"></i> Belum ada file dipilih</p>
<p class="ks-hint"><i class="bi bi-info-circle"></i> Excel berisi NIK, Nama, Gaji Pokok, serta kolom Tambahan dan Potongan sesuai komponen yang aktif. Kontak dan data identitas mengikuti master Karyawan.</p>
<p class="ks-error" id="ksExcelError" role="alert"></p>
</div>
<div class="ks-actions">
<a class="ks-btn ks-btn--secondary" download="template-import-gaji.xlsx" href="{{ asset('assets/template-import-gaji.xlsx') }}" id="ksBtnTemplate"><i class="bi bi-download"></i><span>Download Template</span></a>
<button class="ks-btn ks-btn--primary" disabled="" id="ksBtnImport" type="button"><i class="bi bi-cloud-arrow-up-fill"></i><span>Import Excel</span></button>
<button class="ks-btn ks-btn--outline" id="ksBtnPreview" type="button"><i class="bi bi-eye"></i><span>Preview Slip</span><span class="ks-badge" hidden="" id="ksPendingBadge">0</span></button>
</div>
<div class="ks-result" hidden="" id="ksImportResult"></div>
</section>
</div>
@endsection

@section('modals')
<div aria-live="polite" class="ks-toasts" id="ksToasts" role="status"></div>
@endsection

@push('scripts')
  <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
  <script src="{{ asset('js/slip-common.js') }}"></script>
  <script src="{{ asset('js/kirim-slip.js') }}"></script>
@endpush
