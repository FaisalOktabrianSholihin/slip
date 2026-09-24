<?php $__env->startSection('title', 'Data User - E-Slip Gaji Mitratani'); ?>
<?php $__env->startSection('page-title', 'Data User'); ?>
<?php $__env->startSection('page-subtitle', 'Kelola akun dan hak akses pengguna sistem'); ?>
<?php $__env->startSection('page-icon', 'bi-people-fill'); ?>

<?php $__env->startPush('styles'); ?>
  <link rel="stylesheet" href="<?php echo e(asset('css/data-user.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('modals'); ?>
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
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
  <script src="<?php echo e(asset('js/data-user.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\project slip gaji\slip4\resources\views/pages/data-user.blade.php ENDPATH**/ ?>