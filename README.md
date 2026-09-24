# E-Slip Gaji — Backend Terintegrasi Penuh

Seluruh front-end di proyek ini sekarang tersambung ke database sungguhan
(dua koneksi MySQL: `gajii` transaksional & `db_indukk` data master),
tidak ada lagi simulasi `localStorage`.

## 1. Pasang

```bash
composer install
php artisan migrate
php artisan db:seed
php artisan storage:link
```

Login dengan `test@example.com` / password default factory `password`
(role Superadmin, dari `DatabaseSeeder`).

## 2. Apa yang tersambung ke database

| Halaman | Controller | Tabel |
|---|---|---|
| Data Divisi | `DivisiController` | `divisis` (db_indukk) |
| Data Jabatan | `JabatanController` | `jabatans` (db_indukk) |
| Data Karyawan | `KaryawanController` | `karyawans` + 5 tabel anak (db_indukk) |
| Data User | `UserController` | `users` + `roles` (gajii) |
| Role Management | `RoleController` | `roles` (gajii) |
| Pengaturan | `SettingController` | `settings` (gajii) |
| Log Aktivitas | `ActivityLogController` | `activity_logs` (gajii) |
| Dashboard | `DashboardController` | agregat dari `karyawans` (db_indukk) |
| Kirim Slip / Preview | `SlipController` | `payrolls` + `payroll_items` (gajii) |
| Detail Riwayat | `RiwayatController` | `slip_histories` (gajii) |
| Login/Logout | `AuthController` | `users` (gajii, session Laravel) |

Semua endpoint di atas berada di bawah prefix `/api/*` dan middleware
`auth` (lihat `routes/web.php`), dipanggil dari JS lewat
`public/js/api-client.js` (helper `Api.get/post/put/delete` + CSRF
otomatis).

## 3. Perubahan penting di sisi front-end

- **`public/js/api-client.js`** (baru): helper fetch + CSRF, dipakai semua halaman.
- **`public/js/slip-common.js`**: `SlipStore` yang tadinya baca/tulis
  `localStorage` sekarang jadi wrapper `Api.*` ke database — **semua
  method-nya mengembalikan Promise**, jadi pemanggil pakai `.then()`
  atau `async/await`. `SlipUI` (toast/dialog) tidak berubah.
- **`data-divisi.js`, `data-jabatan.js`, `data-user.js`,
  `role-management.js`, `data-karyawan.js`**: array in-memory diganti
  hasil `Api.get(...)`, tombol tambah/edit/hapus memanggil
  POST/PUT/DELETE lalu memperbarui tampilan dari respons server.
- **`kirim-slip.js`**: validasi NIK & kontak karyawan sekarang
  dilakukan di server (`SlipController@importPayroll`), bukan lagi
  lookup ke array dummy di `slip-common.js`.
- **`preview-slip.js`**: tombol "Kirim Email/WhatsApp" (per divisi
  atau semua) memanggil `SlipController@storeDivisi` /
  `@storeAll` dengan query `?channel=email|wa`.
- **`dashboard.js`**: grafik pendidikan/usia/status/matriks dihitung
  nyata dari tabel `karyawans` (lihat `DashboardController::summary()`).
- **`auth.js`**: dikosongkan (no-op) — proteksi halaman sekarang
  murni middleware `auth` Laravel di `routes/web.php`.
- **`login.js`**: mengirim POST sungguhan ke `/login`
  (`AuthController@login`, session-based), bukan lagi menandai
  `sessionStorage`.

## 4. Keterbatasan yang masih ada (di luar cakupan awal permintaan)

- **WhatsApp**: `SlipController::kirimSatuSlip()` sudah punya cabang
  channel `wa`, tapi belum disambungkan ke gateway WA manapun (masih
  ditandai gagal dengan keterangan "belum diimplementasikan"). Tinggal
  isi bagian itu sesuai provider WA yang dipakai.
- **Generate PDF slip**: `payrolls.file_slip` disediakan sebagai
  kolom nama file, tapi tidak ada proses yang benar-benar men-generate
  PDF fisik. Slip email saat ini berupa ringkasan HTML
  (`resources/views/emails/slip-gaji.blade.php`), bukan lampiran PDF.
  Kalau dibutuhkan, tambahkan library seperti `barryvdh/laravel-dompdf`
  di `SlipController::kirimSatuSlip()`.
- **Hapus massal Log Aktivitas / Riwayat**: sengaja tidak dibuatkan
  endpoint hapus permanen (demi jejak audit) — tombol "Bersihkan
  Log"/"Hapus Riwayat" di UI hanya mencatat aktivitas & me-refresh
  tampilan, tidak menghapus data di database.
- **Retensi data otomatis** (`Pengaturan → Terapkan retensi`): baru
  tersimpan sebagai kebijakan (`retention_months` dst di tabel
  `settings`), belum ada job/scheduler yang benar-benar menghapus data
  lama sesuai kebijakan itu.
- **Progress pengiriman real-time**: sebelumnya simulasi menampilkan
  "Mengirim 3/10…"; sekarang satu request menangani seluruh batch
  sekaligus, jadi UI hanya menampilkan "Mengirim…" tanpa hitungan
  progres per-baris.

## 5. Catatan konfigurasi

`config/database.php` koneksi `db_induk` sudah diperbaiki agar
memakai variabel env `DB_INDUK_*` (sebelumnya salah memakai
`DB_SECOND_*`), sesuai `.env` yang Anda berikan.
