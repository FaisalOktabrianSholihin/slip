@extends('layouts.app')

@section('title', 'Data User - E-Slip Gaji Mitratani')
@section('page-title', 'Data User')
@section('page-subtitle', 'Kelola akun dan hak akses pengguna sistem')
@section('page-icon', 'bi-people-fill')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/data-user.css') }}">
@endpush

@section('content')
<div class="dm-page">
<div class="dm-stat-card" id="statTotalUser">
<div class="dm-stat-main">
<div class="dm-stat-icon"><i class="bi bi-people-fill"></i></div>
<div class="dm-stat-body">
<span class="dm-stat-label">Total User</span>
<strong class="dm-stat-value" id="statTotalValue">0</strong>
</div>
</div>
<div class="dm-stat-side">
<div class="dm-stat-chip">
<span class="dm-stat-chip-dot dm-stat-chip-dot--superadmin"></span>
<span id="statSuperadminValue">0</span> Superadmin
          </div>
<div class="dm-stat-chip">
<span class="dm-stat-chip-dot dm-stat-chip-dot--admin"></span>
<span id="statAdminValue">0</span> Admin
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
<input id="searchInput" placeholder="Cari nama, email, atau role..." type="text"/>
</div>
<button class="btn btn-primary" id="btnAdd">
<i class="bi bi-plus-lg"></i>
              Tambah Data
            </button>
</div>
</div>
<div class="dm-table-wrap">
<table class="dm-table" id="userTable">
<thead>
<tr>
<th class="sortable" data-key="id"><span class="th-inner">No <span class="sort-icon">↕</span></span></th>
<th class="sortable" data-key="nama"><span class="th-inner">Nama <span class="sort-icon">↕</span></span></th>
<th class="sortable" data-key="email"><span class="th-inner">Email <span class="sort-icon">↕</span></span></th>
<th class="sortable" data-key="role"><span class="th-inner">Role Sistem <span class="sort-icon">↕</span></span></th>
<th class="dm-col-actions">Aksi</th>
</tr>
</thead>
<tbody id="tableBody">
<!-- rows dirender oleh js/data-user.js -->
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
<h2 id="modalTitle">Tambah User</h2>
<button aria-label="Tutup" class="dm-modal-close" id="modalClose">×</button>
</div>
<form id="userForm">
<div class="dm-field">
<label for="fieldNama">Nama</label>
<input id="fieldNama" required="" type="text"/>
</div>
<div class="dm-field">
<label for="fieldEmail">Email</label>
<input id="fieldEmail" required="" type="email"/>
</div>
<div class="dm-field">
<label for="fieldRole">Role Sistem</label>
<select id="fieldRole">
<option value="superadmin">Superadmin</option>
<option value="admin">Admin</option>
</select>
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
<h2>Hapus User?</h2>
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
  <script src="{{ asset('js/data-user.js') }}"></script>
@endpush
