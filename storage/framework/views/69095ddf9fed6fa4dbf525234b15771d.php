<?php $__env->startSection('title', 'Detail Riwayat'); ?>
<?php $__env->startSection('page-title', 'Riwayat Slip'); ?>
<?php $__env->startSection('page-subtitle', 'Riwayat pengiriman slip gaji'); ?>
<?php $__env->startSection('page-icon', 'bi-clock-history'); ?>

<?php $__env->startPush('styles'); ?>
  <link rel="stylesheet" href="<?php echo e(asset('css/detail-riwayat.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

<div class="page">
<div class="card">
<div class="toolbar">
<div class="field">
<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg>
<input id="searchDivisi" placeholder="Cari Divisi..." type="text"/>
</div>
<div class="field filter-field" id="filterField">
<button aria-expanded="false" aria-haspopup="true" class="filter-trigger" id="filterTrigger" title="September 2026" type="button">
<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><polygon points="3 4 21 4 14 12.5 14 19 10 21 10 12.5 3 4"></polygon></svg>
</button>
<div class="filter-dropdown" hidden="" id="filterDropdown">
<div class="filter-dropdown-row">
<label for="filterBulanSelect">Bulan</label>
<select id="filterBulanSelect">
<option value="1">Januari</option>
<option value="2">Februari</option>
<option value="3">Maret</option>
<option value="4">April</option>
<option value="5">Mei</option>
<option value="6">Juni</option>
<option value="7">Juli</option>
<option value="8">Agustus</option>
<option selected="" value="9">September</option>
<option value="10">Oktober</option>
<option value="11">November</option>
<option value="12">Desember</option>
</select>
</div>
<div class="filter-dropdown-row">
<label for="filterTahunSelect">Tahun</label>
<select id="filterTahunSelect">
<option value="2024">2024</option>
<option value="2025">2025</option>
<option selected="" value="2026">2026</option>
<option value="2027">2027</option>
</select>
</div>
<div class="filter-dropdown-actions">
<button class="btn-reset" id="filterReset" type="button">Semua bulan</button>
<button class="btn-apply" id="filterApply" type="button">Terapkan</button>
</div>
</div>
</div>
<div class="spacer"></div>
<button class="btn btn-export" id="exportBtn">
<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>
              Export
            </button>
<button class="btn btn-danger" id="hapusBtn">
<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"></path></svg>
              Hapus
            </button>
</div>
<div class="table-scroll">
<table>
<thead>
<tr>
<th>No</th>
<th>Nama</th>
<th>Pengiriman</th>
<th>Divisi</th>
<th class="col-file">File</th>
<th>Status</th>
<th>Tanggal</th>
</tr>
</thead>
<tbody id="tableBody"></tbody>
</table>
<div class="empty-state" id="emptyState" style="display:none;">
<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" viewbox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg>
<p>Tidak ada slip yang cocok dengan pencarian.</p>
</div>
</div>
<div class="table-footer">
<span id="footerCount">Menampilkan 5 dari 5 data</span>
<span>September 2026</span>
</div>
</div>
</div><!-- /.page -->
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
  <script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js"></script>
  <script src="<?php echo e(asset('js/slip-common.js')); ?>"></script>
  <script src="<?php echo e(asset('js/detail-riwayat.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\project slip gaji\slip4\resources\views/pages/detail-riwayat.blade.php ENDPATH**/ ?>