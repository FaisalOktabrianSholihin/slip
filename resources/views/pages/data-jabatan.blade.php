@extends('layouts.app')

@section('title', 'Data Jabatan - E-Slip Gaji Mitratani')
@section('page-title', 'Data Jabatan')
@section('page-subtitle', 'Kelola data jabatan/posisi perusahaan')
@section('page-icon', 'bi-briefcase-fill')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/data-jabatan.css') }}">
@endpush

@section('content')
<div class="dm-page">
<div class="dm-stat-card" id="statTotalJabatan">
<div class="dm-stat-main">
<div class="dm-stat-icon"><i class="bi bi-briefcase-fill"></i></div>
<div class="dm-stat-body">
<span class="dm-stat-label">Total Jabatan</span>
<strong class="dm-stat-value" id="statTotalValue">0</strong>
</div>
</div>
</div>
<div class="dm-card">
<div class="dm-controls">
<div class="dm-show-entries">
            Show
            <select id="entriesPerPage">
<option value="5">5</option>
<option selected="" value="10">10</option>
<option value="25">25</option>
<option value="50">50</option>
</select>
            entries
          </div>
<div class="dm-controls-right">
<div class="dm-search">
<i class="bi bi-search"></i>
<input id="searchInput" placeholder="Cari jabatan..." type="text"/>
</div>
<button class="btn btn-primary" id="btnAdd">
<i class="bi bi-plus-lg"></i>
              Tambah Data
            </button>
</div>
</div>
<div class="dm-table-wrap">
<table class="dm-table" id="jabatanTable">
<thead>
<tr>
<th class="sortable" data-key="id"><span class="th-inner">No <span class="sort-icon">↕</span></span></th>
<th class="sortable" data-key="nama"><span class="th-inner">Nama Jabatan <span class="sort-icon">↕</span></span></th>
<th class="dm-col-actions">Aksi</th>
</tr>
</thead>
<tbody id="tableBody">
<!-- rows dirender oleh js/data-jabatan.js -->
</tbody>
</table>
</div>
<div class="dm-footer">
<p id="entriesInfo">Menampilkan 0 entri</p>
<div class="dm-pagination" id="pagination"></div>
</div>
</div>
</div>
@endsection

@section('modals')
<div class="dm-modal-overlay" id="modalOverlay">
<div class="dm-modal">
<div class="dm-modal-header">
<h2 id="modalTitle">Tambah Jabatan</h2>
<button aria-label="Tutup" class="dm-modal-close" id="modalClose">×</button>
</div>
<form id="jabatanForm">
<div class="dm-field">
<label for="fieldNama">Nama Jabatan</label>
<input id="fieldNama" placeholder="Contoh: Manager" required="" type="text"/>
</div>
<div class="dm-modal-actions">
<button class="btn btn-ghost" id="btnCancel" type="button">Batal</button>
<button class="btn btn-primary" type="submit">Simpan</button>
</div>
</form>
</div>
</div>
<div class="dm-modal-overlay dm-confirm" id="confirmOverlay">
<div class="dm-modal">
<div class="dm-modal-header">
<h2>Hapus Jabatan?</h2>
</div>
<p>Data yang sudah dihapus tidak dapat dikembalikan. Yakin ingin melanjutkan?</p>
<div class="dm-modal-actions" style="padding:0 22px 22px;">
<button class="btn btn-ghost" id="confirmCancel" type="button">Batal</button>
<button class="btn btn-danger" id="confirmDelete" type="button">Ya, Hapus</button>
</div>
</div>
</div>
@endsection

@push('scripts')
  <script src="{{ asset('js/data-jabatan.js') }}"></script>
@endpush
