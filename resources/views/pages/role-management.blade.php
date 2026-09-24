@extends('layouts.app')

@section('title', 'Role Management')
@section('page-title', 'Role Management')
@section('page-subtitle', 'Kelola role dan hak akses pengguna')
@section('page-icon', 'bi-shield-fill')

@push('styles')
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Source+Serif+4:opsz,wght@8..60,500;8..60,600&display=swap">
  <link rel="stylesheet" href="{{ asset('css/role-management.css') }}">
@endpush

@section('content')
<div class="page">
<div class="card">
<div class="toolbar">
<div class="field">
<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg>
<input id="searchRole" placeholder="Cari role..." type="text"/>
</div>
<div class="spacer"></div>
<button class="btn btn-primary" id="addRoleBtn">
<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></svg>
              Tambah Role
            </button>
</div>
<div class="table-scroll">
<table>
<thead>
<tr>
<th>No</th>
<th>Nama Role</th>
<th>Deskripsi</th>
<th>Hak Akses</th>
<th>Pengguna</th>
<th class="col-aksi">Aksi</th>
</tr>
</thead>
<tbody id="tableBody"></tbody>
</table>
<div class="empty-state" id="emptyState" style="display:none;">
<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" viewbox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg>
<p>Tidak ada role yang cocok dengan pencarian.</p>
</div>
</div>
<div class="table-footer">
<span id="footerCount">Menampilkan 0 dari 0 data</span>
</div>
</div>
</div><!-- /.page -->
@endsection

@section('modals')
<div class="modal-overlay" hidden="" id="modalOverlay">
<div aria-labelledby="modalTitle" aria-modal="true" class="modal" role="dialog">
<div class="modal-header">
<h2 id="modalTitle">Tambah Role</h2>
<button aria-label="Tutup" class="modal-close" id="modalClose" type="button">
<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"></path></svg>
</button>
</div>
<div class="modal-body">
<div class="form-group">
<label for="roleName">Nama Role</label>
<input id="roleName" placeholder="mis. Admin SDM" type="text"/>
</div>
<div class="form-group">
<label for="roleDesc">Deskripsi</label>
<input id="roleDesc" placeholder="Deskripsi singkat peran ini" type="text"/>
</div>
<div class="form-group">
<label>Hak Akses Menu</label>
<div class="preset-row">
<div class="preset-buttons" id="presetButtons">
<button class="preset-btn" data-preset="staff" type="button">Staff</button>
<button class="preset-btn" data-preset="admin" type="button">Admin</button>
<button class="preset-btn" data-preset="superadmin" type="button">Superadmin</button>
<button class="preset-btn" data-preset="manual" type="button">Manual</button>
</div>
<label class="select-all">
<input id="selectAllPermissions" type="checkbox"/>
<span>Pilih semua</span>
</label>
</div>
<div class="permission-grid" id="permissionGrid"></div>
</div>
</div>
<div class="modal-footer">
<button class="btn btn-ghost" id="modalCancel" type="button">Batal</button>
<button class="btn btn-primary" id="modalSave" type="button">Simpan</button>
</div>
</div>
</div>
@endsection

@push('scripts')
  <script src="{{ asset('js/role-management.js') }}"></script>
@endpush
