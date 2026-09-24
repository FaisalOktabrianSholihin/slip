<?php $__env->startSection('title', 'Data Karyawan'); ?>
<?php $__env->startSection('page-title', 'Data Karyawan'); ?>
<?php $__env->startSection('page-subtitle', 'Kelola data karyawan, divisi, dan status keaktifannya dalam satu tempat'); ?>
<?php $__env->startSection('page-icon', 'bi-people'); ?>

<?php $__env->startPush('styles'); ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?php echo e(asset('css/data-karyawan.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

<!-- ====== KONTEN HALAMAN KARYAWAN ====== -->
<div class="kw">
<!-- Ringkasan -->
<section aria-label="Ringkasan karyawan" class="kw-card kw-stats">
<div class="kw-stat">
<span aria-hidden="true" class="kw-stat__icon"><i class="fa-solid fa-users"></i></span>
<div>
<p class="kw-stat__label">Total karyawan</p>
<p class="kw-stat__value" id="kwStatTotal">0</p>
</div>
</div>
<div class="kw-stat">
<span aria-hidden="true" class="kw-stat__icon"><i class="fa-solid fa-user-check"></i></span>
<div>
<p class="kw-stat__label">Aktif</p>
<p class="kw-stat__value" id="kwStatAktif">0</p>
</div>
</div>
<div class="kw-stat">
<span aria-hidden="true" class="kw-stat__icon kw-stat__icon--off"><i class="fa-solid fa-user-slash"></i></span>
<div>
<p class="kw-stat__label">Nonaktif</p>
<p class="kw-stat__value" id="kwStatNonaktif">0</p>
</div>
</div>
<div class="kw-stat">
<span aria-hidden="true" class="kw-stat__icon"><i class="fa-solid fa-sitemap"></i></span>
<div>
<p class="kw-stat__label">Divisi</p>
<p class="kw-stat__value" id="kwStatDivisi">0</p>
</div>
</div>
</section>
<!-- Tabel -->
<section aria-label="Daftar karyawan" class="kw-card">
<div class="kw-toolbar">
<label class="kw-perpage">
              Tampilkan
              <select class="kw-select" id="kwPerPage">
<option value="5">5</option>
<option selected="" value="10">10</option>
<option value="25">25</option>
<option value="50">50</option>
</select>
</label>
<div class="kw-search">
<i aria-hidden="true" class="fa-solid fa-magnifying-glass"></i>
<input aria-label="Cari karyawan" autocomplete="off" class="kw-input" id="kwSearch" placeholder="Cari nama, email, WA, NIK, atau divisi" type="search"/>
</div>
<div class="kw-filter">
<button aria-expanded="false" aria-haspopup="true" aria-label="Filter" class="kw-btn kw-btn--outline kw-filter__btn" id="kwFilterBtn" title="Filter" type="button">
<i class="fa-solid fa-filter"></i>
<span class="kw-filter__badge" hidden="" id="kwFilterBadge">0</span>
</button>
<div class="kw-filter__panel" hidden="" id="kwFilterPanel">
<div class="kw-field">
<label for="kwFilterDivisi">Divisi</label>
<select aria-label="Filter divisi" class="kw-select" id="kwFilterDivisi">
<option value="">Semua divisi</option>
</select>
</div>
<div class="kw-field">
<label for="kwFilterStatus">Status</label>
<select aria-label="Filter status" class="kw-select" id="kwFilterStatus">
<option value="">Semua status</option>
<option value="aktif">Aktif</option>
<option value="nonaktif">Nonaktif</option>
</select>
</div>
<div class="kw-filter__foot">
<button class="kw-btn kw-btn--ghost kw-btn--sm" id="kwFilterReset" type="button">Reset filter</button>
</div>
</div>
</div>
<span class="kw-toolbar__spacer"></span>
<div class="kw-actions">
<button class="kw-btn kw-btn--primary" id="kwBtnAdd" type="button">
<i class="fa-solid fa-plus"></i> Tambah data
              </button>
<button class="kw-btn kw-btn--outline" id="kwBtnExport" type="button">
<i class="fa-solid fa-file-excel"></i> Export Excel
              </button>
<button class="kw-btn kw-btn--soft" id="kwBtnImport" type="button">
<i class="fa-solid fa-file-import"></i> Import Excel
              </button>
</div>
</div>
<div class="kw-table-wrap" id="kwTableWrap">
<table class="kw-table">
<thead>
<tr>
<th class="kw-col-no" scope="col">No</th>
<th aria-sort="none" scope="col">
<button class="kw-sort" data-sort="nama" type="button">Nama <i class="fa-solid fa-sort"></i></button>
</th>
<th scope="col">Email</th>
<th scope="col">NIK</th>
<th aria-sort="none" scope="col">
<button class="kw-sort" data-sort="divisi" type="button">Divisi <i class="fa-solid fa-sort"></i></button>
</th>
<th aria-sort="none" scope="col">
<button class="kw-sort" data-sort="aktif" type="button">Status aktif <i class="fa-solid fa-sort"></i></button>
</th>
<th class="kw-col-actions" scope="col">Aksi</th>
</tr>
</thead>
<tbody id="kwTbody"></tbody>
</table>
</div>
<div class="kw-empty" hidden="" id="kwEmpty">
<i aria-hidden="true" class="fa-solid fa-magnifying-glass fa-fw"></i>
<h3>Karyawan tidak ditemukan</h3>
<p>Ubah kata kunci atau filter, atau tambahkan karyawan baru.</p>
</div>
<div class="kw-foot">
<span aria-live="polite" id="kwInfo"></span>
<nav aria-label="Halaman" class="kw-pager" id="kwPager"></nav>
</div>
</section>
</div>
<!-- ====== SELESAI KONTEN HALAMAN ====== -->
<?php $__env->stopSection(); ?>

<?php $__env->startSection('modals'); ?>
<div class="kw-modal" hidden="" id="kwModalForm">
<div class="kw-modal__backdrop" data-close=""></div>
<div aria-labelledby="kwFormTitle" aria-modal="true" class="kw-modal__dialog kw-modal__dialog--xl" role="dialog">
<div class="kw-modal__head">
<h2 id="kwFormTitle">Tambah karyawan</h2>
<button aria-label="Tutup" class="kw-modal__close" data-close="" type="button"><i class="fa-solid fa-xmark"></i></button>
</div>
<!-- Tab navigasi -->
<div class="kw-tabs" id="kwTabs" role="tablist">
<button aria-controls="kwPanelUtama" aria-selected="true" class="kw-tab is-active" data-tab="utama" role="tab" type="button">
<i class="fa-solid fa-id-card"></i> Data Utama
        </button>
<button aria-controls="kwPanelPribadi" aria-selected="false" class="kw-tab" data-tab="pribadi" role="tab" type="button">
<i class="fa-solid fa-user"></i> Data Pribadi
        </button>
<button aria-controls="kwPanelBank" aria-selected="false" class="kw-tab" data-tab="bank" role="tab" type="button">
<i class="fa-solid fa-building-columns"></i> Bank &amp; Pendidikan
        </button>
<button aria-controls="kwPanelKeluarga" aria-selected="false" class="kw-tab" data-tab="keluarga" role="tab" type="button">
<i class="fa-solid fa-people-group"></i> Keluarga
        </button>
</div>
<form id="kwForm" novalidate="">
<div class="kw-modal__body kw-formbody">
<!-- =================== TAB 1: DATA UTAMA =================== -->
<section class="kw-tabpanel is-active" data-panel="utama" id="kwPanelUtama" role="tabpanel">
<h3 class="kw-formsec"><i class="fa-solid fa-briefcase"></i> Informasi Kepegawaian</h3>
<div class="kw-grid">
<div class="kw-field kw-s4">
<label for="kwFNik">NIK <span class="kw-req">*</span></label>
<input autocomplete="off" class="kw-input" data-autofocus="" id="kwFNik" inputmode="numeric" maxlength="8" name="nik" type="text"/>
<p class="kw-field__error" data-error-for="nik"></p>
</div>
<div class="kw-field kw-s4">
<label for="kwFNoAbsen">No Absen</label>
<input autocomplete="off" class="kw-input" id="kwFNoAbsen" name="noAbsen" type="text"/>
</div>
<div class="kw-field kw-s4">
<label for="kwFGolongan">Golongan</label>
<select class="kw-select" id="kwFGolongan" name="golongan">
<option value="">-- Pilih --</option>
<option>I</option><option>II</option><option>III</option><option>IV</option>
</select>
</div>
<div class="kw-field kw-s8">
<label for="kwFNama">Nama <span class="kw-req">*</span></label>
<input autocomplete="off" class="kw-input" id="kwFNama" name="nama" type="text"/>
<p class="kw-field__error" data-error-for="nama"></p>
</div>
<div class="kw-field kw-s4">
<label for="kwFNamaPanggilan">Nama Panggilan</label>
<input autocomplete="off" class="kw-input" id="kwFNamaPanggilan" name="namaPanggilan" type="text"/>
</div>
<div class="kw-field kw-s3">
<label for="kwFJabatan">Jabatan <span class="kw-req">*</span></label>
<select class="kw-select" id="kwFJabatan" name="jabatan">
<option value="">Pilih Jabatan</option>
</select>
<p class="kw-field__error" data-error-for="jabatan"></p>
</div>
<div class="kw-field kw-s3">
<label for="kwFDivisi">Divisi <span class="kw-req">*</span></label>
<select class="kw-select" id="kwFDivisi" name="divisi">
<option value="">Pilih Divisi</option>
</select>
<p class="kw-field__error" data-error-for="divisi"></p>
</div>
<div class="kw-field kw-s3">
<label for="kwFStatusPegawai">Status Pegawai</label>
<select class="kw-select" id="kwFStatusPegawai" name="statusPegawai">
<option value="">-- Pilih --</option>
<option value="tetap">Karyawan Tetap</option>
<option value="pkwt">PKWT</option>
<option value="honorer">Honorer</option>
<option value="penugasan">Karyawan Penugasan</option>
</select>
</div>
<div class="kw-field kw-s3">
<label for="kwFStatus">Status Aktif</label>
<select class="kw-select" id="kwFStatus" name="status">
<option value="aktif">Aktif</option>
<option value="nonaktif">Nonaktif</option>
</select>
</div>
</div>
<h3 class="kw-formsec kw-formsec--spaced"><i class="fa-solid fa-calendar-days"></i> Masa Kerja &amp; Cuti</h3>
<div class="kw-grid">
<div class="kw-field kw-s4">
<label for="kwFTglMasuk">Tanggal Masuk</label>
<input class="kw-input" id="kwFTglMasuk" name="tglMasuk" type="date"/>
</div>
<div class="kw-field kw-s4">
<label for="kwFTglAngkat">Tanggal Pengangkatan</label>
<input class="kw-input" id="kwFTglAngkat" name="tglAngkat" type="date"/>
</div>
<div class="kw-field kw-s4">
<label for="kwFTglPensiun">Tanggal Pensiun</label>
<input class="kw-input" id="kwFTglPensiun" name="tglPensiun" readonly="" type="date"/>
<p class="kw-field__hint">Terisi otomatis dari tanggal lahir (usia 56 th).</p>
</div>
<div class="kw-field kw-s4">
<label for="kwFMasaKerja">Masa Kerja (tahun)</label>
<input class="kw-input" id="kwFMasaKerja" min="0" name="masaKerja" step="1" type="number"/>
</div>
<div class="kw-field kw-s4">
<label for="kwFJatahCuti">Jatah Cuti (hari)</label>
<input class="kw-input" id="kwFJatahCuti" min="0" name="jatahCuti" type="number" value="12"/>
</div>
<div class="kw-field kw-s4">
<label for="kwFSisaCuti">Sisa Cuti (hari)</label>
<input class="kw-input" id="kwFSisaCuti" min="0" name="sisaCuti" type="number" value="12"/>
</div>
</div>
<h3 class="kw-formsec kw-formsec--spaced"><i class="fa-solid fa-address-card"></i> Kontak &amp; Identitas</h3>
<div class="kw-grid">
<div class="kw-field kw-s4">
<label for="kwFEmail">Email <span class="kw-req">*</span></label>
<input autocomplete="off" class="kw-input" id="kwFEmail" name="email" type="email"/>
<p class="kw-field__error" data-error-for="email"></p>
</div>
<div class="kw-field kw-s4">
<label for="kwFHp">No HP <span class="kw-req">*</span></label>
<input autocomplete="off" class="kw-input" id="kwFHp" inputmode="tel" name="hp" type="text"/>
<p class="kw-field__error" data-error-for="hp"></p>
</div>
<div class="kw-field kw-s4">
<label for="kwFKtp">No KTP <span class="kw-req">*</span></label>
<input autocomplete="off" class="kw-input" id="kwFKtp" inputmode="numeric" maxlength="16" name="ktp" type="text"/>
<p class="kw-field__error" data-error-for="ktp"></p>
</div>
<div class="kw-field kw-s12">
<label for="kwFAlamat">Alamat <span class="kw-req">*</span></label>
<textarea class="kw-input kw-textarea" id="kwFAlamat" name="alamat" rows="3"></textarea>
<p class="kw-field__error" data-error-for="alamat"></p>
</div>
</div>
</section>
<!-- =================== TAB 2: DATA PRIBADI =================== -->
<section class="kw-tabpanel" data-panel="pribadi" hidden="" id="kwPanelPribadi" role="tabpanel">
<h3 class="kw-formsec"><i class="fa-solid fa-user"></i> Identitas Pribadi</h3>
<div class="kw-grid">
<div class="kw-field kw-s4">
<label for="kwFJk">Jenis Kelamin <span class="kw-req">*</span></label>
<select class="kw-select" id="kwFJk" name="jenisKelamin">
<option value="">Pilih Jenis Kelamin</option>
<option value="L">Laki-laki</option>
<option value="P">Perempuan</option>
</select>
<p class="kw-field__error" data-error-for="jenisKelamin"></p>
</div>
<div class="kw-field kw-s4">
<label for="kwFAgama">Agama</label>
<select class="kw-select" id="kwFAgama" name="agama">
<option value="">-- Pilih --</option>
<option>Islam</option><option>Kristen</option><option>Katolik</option>
<option>Hindu</option><option>Buddha</option><option>Konghucu</option>
</select>
</div>
<div class="kw-field kw-s4">
<label for="kwFSuku">Suku</label>
<input autocomplete="off" class="kw-input" id="kwFSuku" name="suku" type="text"/>
</div>
<div class="kw-field kw-s4">
<label for="kwFTempatLahir">Tempat Lahir</label>
<input autocomplete="off" class="kw-input" id="kwFTempatLahir" name="tempatLahir" type="text"/>
</div>
<div class="kw-field kw-s4">
<label for="kwFTglLahir">Tanggal Lahir <span class="kw-req">*</span></label>
<input class="kw-input" id="kwFTglLahir" name="tglLahir" type="date"/>
<p class="kw-field__error" data-error-for="tglLahir"></p>
</div>
<div class="kw-field kw-s4">
<label for="kwFStatusNikah">Status Nikah</label>
<select class="kw-select" id="kwFStatusNikah" name="statusNikah">
<option value="">-- Pilih --</option>
<option>Belum Menikah</option><option>Menikah</option><option>Cerai Hidup</option><option>Cerai Mati</option>
</select>
</div>
<div class="kw-field kw-s12">
<label for="kwFHobby">Hobby</label>
<input autocomplete="off" class="kw-input" id="kwFHobby" name="hobby" type="text"/>
</div>
</div>
</section>
<!-- =================== TAB 3: BANK & PENDIDIKAN =================== -->
<section class="kw-tabpanel" data-panel="bank" hidden="" id="kwPanelBank" role="tabpanel">
<h3 class="kw-formsec"><i class="fa-solid fa-building-columns"></i> Informasi Bank</h3>
<div class="kw-bank-list" id="kwBankList"></div>
<button class="kw-btn kw-btn--outline kw-btn--sm" id="kwBtnTambahBank" type="button">
<i class="fa-solid fa-plus"></i> Tambah Bank
            </button>
<h3 class="kw-formsec kw-formsec--spaced"><i class="fa-solid fa-graduation-cap"></i> Riwayat Pendidikan</h3>
<div class="kw-grid">
<div class="kw-field kw-s6">
<label for="kwFSd">SD</label>
<input autocomplete="off" class="kw-input" id="kwFSd" name="sd" placeholder="Nama sekolah &amp; tahun" type="text"/>
</div>
<div class="kw-field kw-s6">
<label for="kwFSltp">SLTP</label>
<input autocomplete="off" class="kw-input" id="kwFSltp" name="sltp" placeholder="Nama sekolah &amp; tahun" type="text"/>
</div>
<div class="kw-field kw-s6">
<label for="kwFSlta">SLTA</label>
<input autocomplete="off" class="kw-input" id="kwFSlta" name="slta" placeholder="Nama sekolah &amp; tahun" type="text"/>
</div>
<div class="kw-field kw-s6">
<label for="kwFPt">Perguruan Tinggi (PT)</label>
<input autocomplete="off" class="kw-input" id="kwFPt" name="pt" placeholder="Nama kampus &amp; tahun" type="text"/>
</div>
</div>
<div class="kw-grid">
<div class="kw-field kw-s3">
<label for="kwFPendidikanTerakhir">Pendidikan Terakhir</label>
<select class="kw-select" id="kwFPendidikanTerakhir" name="pendidikanTerakhir">
<option value="">-- Pilih --</option>
<option>SD</option><option>SLTP</option><option>SLTA</option>
<option>DIII</option><option>S1</option><option>S2</option><option>S3</option>
</select>
</div>
<div class="kw-field kw-s3">
<label for="kwFJurusan">Jurusan</label>
<input autocomplete="off" class="kw-input" id="kwFJurusan" name="jurusan" type="text"/>
</div>
<div class="kw-field kw-s3">
<label for="kwFTahunMasuk">Tahun Masuk</label>
<input autocomplete="off" class="kw-input" id="kwFTahunMasuk" inputmode="numeric" maxlength="4" name="tahunMasuk" type="text"/>
</div>
<div class="kw-field kw-s3">
<label for="kwFTahunKeluar">Tahun Keluar</label>
<input autocomplete="off" class="kw-input" id="kwFTahunKeluar" inputmode="numeric" maxlength="4" name="tahunKeluar" type="text"/>
</div>
</div>
</section>
<!-- =================== TAB 4: KELUARGA =================== -->
<section class="kw-tabpanel" data-panel="keluarga" hidden="" id="kwPanelKeluarga" role="tabpanel">
<h3 class="kw-formsec"><i class="fa-solid fa-people-roof"></i> Orang Tua</h3>
<div class="kw-grid">
<div class="kw-field kw-s6">
<label for="kwFAyah">Nama Ayah</label>
<input autocomplete="off" class="kw-input" id="kwFAyah" name="namaAyah" type="text"/>
</div>
<div class="kw-field kw-s6">
<label for="kwFIbu">Nama Ibu</label>
<input autocomplete="off" class="kw-input" id="kwFIbu" name="namaIbu" type="text"/>
</div>
</div>
<h3 class="kw-formsec kw-formsec--spaced"><i class="fa-solid fa-heart"></i> Suami / Istri</h3>
<div class="kw-grid">
<div class="kw-field kw-s4">
<label for="kwFPasangan">Nama Pasangan</label>
<input autocomplete="off" class="kw-input" id="kwFPasangan" name="namaPasangan" type="text"/>
</div>
<div class="kw-field kw-s4">
<label for="kwFTempatLahirPasangan">Tempat Lahir Pasangan</label>
<input autocomplete="off" class="kw-input" id="kwFTempatLahirPasangan" name="tempatLahirPasangan" type="text"/>
</div>
<div class="kw-field kw-s4">
<label for="kwFTglLahirPasangan">Tanggal Lahir Pasangan</label>
<input class="kw-input" id="kwFTglLahirPasangan" name="tglLahirPasangan" type="date"/>
</div>
<div class="kw-field kw-s4">
<label for="kwFPekerjaanPasangan">Pekerjaan Pasangan</label>
<input autocomplete="off" class="kw-input" id="kwFPekerjaanPasangan" name="pekerjaanPasangan" type="text"/>
</div>
<div class="kw-field kw-s4">
<label for="kwFJkPasangan">Jenis Kelamin Pasangan</label>
<select class="kw-select" id="kwFJkPasangan" name="jkPasangan">
<option value="">-- Pilih --</option>
<option value="L">Laki-laki</option>
<option value="P">Perempuan</option>
</select>
</div>
<div class="kw-field kw-s4">
<label for="kwFPendidikanPasangan">Pendidikan Pasangan</label>
<input autocomplete="off" class="kw-input" id="kwFPendidikanPasangan" name="pendidikanPasangan" type="text"/>
</div>
</div>
<h3 class="kw-formsec kw-formsec--spaced"><i class="fa-solid fa-child-reaching"></i> Data Anak</h3>
<div class="kw-anak-wrap">
<table class="kw-table kw-table--anak">
<colgroup>
<col style="width:48px"/>
<col/>
<col style="width:15%"/>
<col style="width:150px"/>
<col style="width:15%"/>
<col style="width:120px"/>
<col style="width:14%"/>
<col style="width:52px"/>
</colgroup>
<thead>
<tr>
<th scope="col">No</th>
<th scope="col">Nama</th>
<th scope="col">Tempat Lahir</th>
<th scope="col">Tanggal Lahir</th>
<th scope="col">Pekerjaan</th>
<th scope="col">Jenis Kelamin</th>
<th scope="col">Pendidikan</th>
<th aria-label="Aksi" scope="col"></th>
</tr>
</thead>
<tbody id="kwAnakTbody"></tbody>
</table>
</div>
<button class="kw-btn kw-btn--outline kw-btn--sm" id="kwBtnTambahAnak" type="button">
<i class="fa-solid fa-plus"></i> Tambah Anak
            </button>
<h3 class="kw-formsec kw-formsec--spaced"><i class="fa-solid fa-shield-heart"></i> Jaminan Kesehatan</h3>
<div class="kw-grid">
<div class="kw-field kw-s4">
<label for="kwFJaminanKesehatan">Jenis Jaminan</label>
<select class="kw-select" id="kwFJaminanKesehatan" name="jaminanKesehatan">
<option value="">-- Pilih --</option>
<option>BPJS Kesehatan</option><option>Asuransi Swasta</option><option>Tidak Ada</option>
</select>
</div>
</div>
</section>
</div>
<div class="kw-modal__foot">
<span class="kw-modal__note"><span class="kw-req">*</span> Wajib diisi</span>
<button class="kw-btn kw-btn--ghost" data-close="" type="button"><i class="fa-solid fa-arrow-left"></i> Kembali</button>
<button class="kw-btn kw-btn--primary" id="kwFormSubmit" type="submit"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
</div>
</form>
</div>
</div>
<div class="kw-modal" hidden="" id="kwModalImport">
<div class="kw-modal__backdrop" data-close=""></div>
<div aria-labelledby="kwImportTitle" aria-modal="true" class="kw-modal__dialog kw-modal__dialog--lg" role="dialog">
<div class="kw-modal__head">
<h2 id="kwImportTitle">Import Data Karyawan</h2>
<button aria-label="Tutup" class="kw-modal__close" data-close="" type="button"><i class="fa-solid fa-xmark"></i></button>
</div>
<div class="kw-modal__body kw-import">
<!-- Download template -->
<div class="kw-import__tpl">
<span class="kw-import__tpl-title"><i aria-hidden="true" class="fa-solid fa-file-excel"></i> Download Template Import</span>
<button class="kw-btn kw-btn--primary kw-btn--sm" id="kwBtnTemplate" type="button">
<i class="fa-solid fa-download"></i> Download Template
          </button>
</div>
<!-- Upload -->
<section aria-label="Upload file Excel" class="kw-import__card">
<h3 class="kw-import__head"><i aria-hidden="true" class="fa-solid fa-upload"></i> Upload File Excel</h3>
<div class="kw-import__body">
<div class="kw-notice">
<p class="kw-notice__title"><i aria-hidden="true" class="fa-solid fa-triangle-exclamation"></i> Perhatian:</p>
<ol class="kw-notice__list">
<li>Download template Excel gaji di atas terlebih dahulu</li>
<li>Isi data gaji mulai dari <strong>baris ke-2</strong> (baris header jangan diubah)</li>
<li>Simpan file dalam format <strong>.xlsx</strong> (nama file bebas)</li>
<li>Upload file tersebut melalui form di bawah</li>
<li>Kolom <strong>NIK</strong> harus 16 digit berformat <strong>Teks</strong>; <strong>Tanggal Lahir</strong> ditulis <strong>dd-mm-yyyy</strong>; <strong>Status Aktif</strong> diisi <strong>AKTIF</strong> atau <strong>NONAKTIF</strong></li>
</ol>
</div>
<div class="kw-field">
<label for="kwImportFile">Pilih File Excel</label>
<input accept=".xlsx" class="kw-input kw-file" id="kwImportFile" type="file"/>
</div>
<div aria-live="polite" class="kw-import__result" hidden="" id="kwImportResult" role="status"></div>
<div class="kw-import__actions">
<button class="kw-btn kw-btn--primary" disabled="" id="kwImportSubmit" type="button">
<i class="fa-solid fa-upload"></i> Import Excel
              </button>
<button class="kw-btn kw-btn--ghost" data-close="" type="button">
<i class="fa-solid fa-arrow-left"></i> Kembali
              </button>
</div>
</div>
</section>
</div>
</div>
</div>
<div class="kw-modal" hidden="" id="kwModalDetail">
<div class="kw-modal__backdrop" data-close=""></div>
<div aria-labelledby="kwDetailTitle" aria-modal="true" class="kw-modal__dialog kw-modal__dialog--xl" role="dialog">
<div class="kw-modal__head">
<h2 id="kwDetailTitle">Detail karyawan</h2>
<button aria-label="Tutup" class="kw-modal__close" data-close="" type="button"><i class="fa-solid fa-xmark"></i></button>
</div>
<div class="kw-detail__top" id="kwDetailTop"></div>
<!-- Tab navigasi (sama gayanya dengan form tambah/ubah) -->
<div class="kw-tabs" id="kwDetailTabs" role="tablist">
<button aria-controls="kwDetailPanelUtama" aria-selected="true" class="kw-tab is-active" data-dtab="utama" role="tab" type="button">
<i class="fa-solid fa-id-card"></i> Data Utama
        </button>
<button aria-controls="kwDetailPanelPribadi" aria-selected="false" class="kw-tab" data-dtab="pribadi" role="tab" type="button">
<i class="fa-solid fa-user"></i> Data Pribadi
        </button>
<button aria-controls="kwDetailPanelBank" aria-selected="false" class="kw-tab" data-dtab="bank" role="tab" type="button">
<i class="fa-solid fa-building-columns"></i> Bank &amp; Pendidikan
        </button>
<button aria-controls="kwDetailPanelKeluarga" aria-selected="false" class="kw-tab" data-dtab="keluarga" role="tab" type="button">
<i class="fa-solid fa-people-group"></i> Keluarga
        </button>
</div>
<div class="kw-modal__body kw-formbody" id="kwDetailBody">
<section class="kw-tabpanel is-active" data-dpanel="utama" id="kwDetailPanelUtama" role="tabpanel"></section>
<section class="kw-tabpanel" data-dpanel="pribadi" hidden="" id="kwDetailPanelPribadi" role="tabpanel"></section>
<section class="kw-tabpanel" data-dpanel="bank" hidden="" id="kwDetailPanelBank" role="tabpanel"></section>
<section class="kw-tabpanel" data-dpanel="keluarga" hidden="" id="kwDetailPanelKeluarga" role="tabpanel"></section>
</div>
<div class="kw-modal__foot">
<button class="kw-btn kw-btn--ghost" data-close="" type="button">Tutup</button>
<button class="kw-btn kw-btn--primary" id="kwDetailEdit" type="button"><i class="fa-solid fa-pen-to-square"></i> Ubah data</button>
</div>
</div>
</div>
<div class="kw-modal" hidden="" id="kwModalDelete">
<div class="kw-modal__backdrop" data-close=""></div>
<div aria-describedby="kwDeleteText" aria-labelledby="kwDeleteTitle" aria-modal="true" class="kw-modal__dialog kw-modal__dialog--sm" role="alertdialog">
<div class="kw-confirm">
<div aria-hidden="true" class="kw-confirm__icon"><i class="fa-solid fa-trash-can"></i></div>
<h2 id="kwDeleteTitle">Hapus karyawan?</h2>
<p id="kwDeleteText"></p>
</div>
<div class="kw-modal__foot">
<button class="kw-btn kw-btn--ghost" data-autofocus="" data-close="" type="button">Batal</button>
<button class="kw-btn kw-btn--danger" id="kwDeleteConfirm" type="button">Hapus</button>
</div>
</div>
</div>
<div aria-live="polite" class="kw-toasts" id="kwToasts" role="status"></div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
  <script src="<?php echo e(asset('js/data-karyawan.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\project slip gaji\slip4\resources\views/pages/data-karyawan.blade.php ENDPATH**/ ?>