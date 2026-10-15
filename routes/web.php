<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemografiController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SlipController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Rute untuk aplikasi E-Slip Gaji. Halaman (Route::view) dilindungi
| middleware 'auth' (kecuali /login), dan seluruh data ditarik lewat
| endpoint JSON di bawah blok "API" lewat public/js/api-client.js.
|
*/

// Halaman login (tanpa sidebar, tanpa proteksi)
Route::view('/login', 'pages.login')->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Redirect root ke halaman login
Route::redirect('/', '/login');

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Halaman-halaman utama aplikasi (layout dengan sidebar & topheader).
    // Dashboard SENGAJA tidak digerbang izin: jadi halaman pulang yang selalu
    // bisa dibuka siapa pun yang sudah login, apa pun role-nya, supaya tidak
    // ada role yang justru terkunci total begitu login.
    Route::view('/dashboard', 'pages.dashboard')->name('dashboard');
    Route::view('/demografi', 'pages.demografi')->name('demografi')->middleware('permission:demografi.view');

    Route::view('/data-user', 'pages.data-user')->name('data-user')->middleware('permission:master-user.view');
    Route::view('/data-divisi', 'pages.data-divisi')->name('data-divisi')->middleware('permission:master-divisi.view');
    Route::view('/data-jabatan', 'pages.data-jabatan')->name('data-jabatan')->middleware('permission:master-jabatan.view');
    Route::view('/data-karyawan', 'pages.data-karyawan')->name('data-karyawan')->middleware('permission:master-karyawan.view');

    Route::view('/kirim-slip', 'pages.kirim-slip')->name('kirim-slip')->middleware('permission:kirim-slip.view');
    Route::view('/preview-slip', 'pages.preview-slip')->name('preview-slip')->middleware('permission:kirim-slip.view');
    Route::view('/detail-riwayat', 'pages.detail-riwayat')->name('detail-riwayat')->middleware('permission:detail-riwayat.view');

    Route::view('/template-email', 'pages.template-email')->name('template-email')->middleware('permission:template-email.view');
    Route::view('/role-management', 'pages.role-management')->name('role-management')->middleware('permission:role-management.view');
    Route::view('/pengaturan', 'pages.pengaturan')->name('pengaturan')->middleware('permission:setting.view');
    Route::view('/log-aktivitas', 'pages.log-aktivitas')->name('log-aktivitas')->middleware('permission:log-aktivitas.view');

    /*
    |----------------------------------------------------------------
    | API (dipanggil dari public/js/*.js lewat window.Api / fetch)
    |----------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {
        // Data master (database db_indukk)
        Route::get('/divisi', [DivisiController::class, 'index'])->name('divisi.index')->middleware('permission:master-divisi.view');
        Route::post('/divisi', [DivisiController::class, 'store'])->name('divisi.store')->middleware('permission:master-divisi.create');
        Route::put('/divisi/{divisi}', [DivisiController::class, 'update'])->name('divisi.update')->middleware('permission:master-divisi.edit');
        Route::delete('/divisi/{divisi}', [DivisiController::class, 'destroy'])->name('divisi.destroy')->middleware('permission:master-divisi.delete');

        Route::get('/jabatan', [JabatanController::class, 'index'])->name('jabatan.index')->middleware('permission:master-jabatan.view');
        Route::post('/jabatan', [JabatanController::class, 'store'])->name('jabatan.store')->middleware('permission:master-jabatan.create');
        Route::put('/jabatan/{jabatan}', [JabatanController::class, 'update'])->name('jabatan.update')->middleware('permission:master-jabatan.edit');
        Route::delete('/jabatan/{jabatan}', [JabatanController::class, 'destroy'])->name('jabatan.destroy')->middleware('permission:master-jabatan.delete');

        Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index')->middleware('permission:master-karyawan.view');
        Route::post('/karyawan', [KaryawanController::class, 'store'])->name('karyawan.store')->middleware('permission:master-karyawan.create');
        Route::put('/karyawan/{karyawan}', [KaryawanController::class, 'update'])->name('karyawan.update')->middleware('permission:master-karyawan.edit');
        Route::delete('/karyawan/{karyawan}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy')->middleware('permission:master-karyawan.delete');
        Route::post('/karyawan/import', [KaryawanController::class, 'import'])->name('karyawan.import')->middleware('permission:master-karyawan.import');

        // Pengguna aplikasi & role (database gajii)
        Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('permission:master-user.view');
        Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware('permission:master-user.create');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('permission:master-user.edit');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('permission:master-user.delete');

        // Katalog & daftar role dibutuhkan beberapa halaman lain (mis. dropdown
        // role di Data User), jadi cukup gerbang 'view' tanpa membatasi modul asal.
        Route::get('/permissions', [RoleController::class, 'catalog'])->name('permissions.catalog');
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store')->middleware('permission:role-management.create');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update')->middleware('permission:role-management.edit');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy')->middleware('permission:role-management.delete');

        // Template redaksi email slip gaji (single-row)
        Route::get('/template-email', [EmailTemplateController::class, 'show'])->name('template-email.show')->middleware('permission:template-email.view');
        Route::put('/template-email', [EmailTemplateController::class, 'update'])->name('template-email.update')->middleware('permission:template-email.edit');

        // Pengaturan aplikasi (single-row)
        Route::get('/pengaturan', [SettingController::class, 'show'])->name('pengaturan.show')->middleware('permission:setting.view');
        Route::put('/pengaturan', [SettingController::class, 'update'])->name('pengaturan.update')->middleware('permission:setting.edit');
        Route::post('/pengaturan/retensi', [SettingController::class, 'terapkanRetensi'])->name('pengaturan.retensi')->middleware('permission:setting.edit');

        // Log aktivitas (read-only + tambah manual dari klien)
        Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])->name('log-aktivitas.index')->middleware('permission:log-aktivitas.view');
        // Dipakai halaman lain (mis. kirim-slip.js) utk mencatat aktivitas sendiri; tidak digerbang permission log-aktivitas.
        Route::post('/log-aktivitas', [ActivityLogController::class, 'store'])->name('log-aktivitas.store');

        Route::get('/demografi', [DemografiController::class, 'show'])->name('demografi.show')->middleware('permission:demografi.view');
        Route::put('/demografi', [DemografiController::class, 'update'])->name('demografi.update')->middleware('permission:demografi.edit');

        // Dashboard ringkasan: selaras dengan halaman Dashboard, tidak digerbang izin.
        Route::get('/dashboard-summary', [DashboardController::class, 'summary'])->name('dashboard.summary');

        // Riwayat pengiriman slip (untuk detail-riwayat.blade.php)
        Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat.index')->middleware('permission:detail-riwayat.view');

        // Kirim Slip Gaji (padanan SlipController@upload/@preview/@storeDivisi/@storeAll)
        Route::post('/slip/import', [SlipController::class, 'importPayroll'])->name('slip.import')->middleware('permission:kirim-slip.import');
        // Import di latar belakang (lanjut walau pindah menu)
        Route::get('/slip/import-jobs/aktif', [SlipController::class, 'jobAktif'])->name('slip.job.aktif')->middleware('permission:kirim-slip.view');
        Route::post('/slip/import-jobs', [SlipController::class, 'jobBuat'])->name('slip.job.buat')->middleware('permission:kirim-slip.import');
        Route::post('/slip/import-jobs/{job}/proses', [SlipController::class, 'jobProses'])->name('slip.job.proses')->middleware('permission:kirim-slip.import');
        Route::post('/slip/import-jobs/{job}/batal', [SlipController::class, 'jobBatal'])->name('slip.job.batal')->middleware('permission:kirim-slip.import');
        Route::post('/slip/import-jobs/{job}/dilihat', [SlipController::class, 'jobDilihat'])->name('slip.job.dilihat')->middleware('permission:kirim-slip.view');
        // Kirim slip di latar belakang (lanjut walau pindah menu)
        Route::get('/slip/kirim-jobs/aktif', [SlipController::class, 'sendAktif'])->name('slip.send.aktif')->middleware('permission:kirim-slip.view');
        Route::post('/slip/kirim-jobs', [SlipController::class, 'sendBuat'])->name('slip.send.buat')->middleware('permission:kirim-slip.send');
        Route::post('/slip/kirim-jobs/{job}/proses', [SlipController::class, 'sendProses'])->name('slip.send.proses')->middleware('permission:kirim-slip.send');
        Route::post('/slip/kirim-jobs/{job}/batal', [SlipController::class, 'sendBatal'])->name('slip.send.batal')->middleware('permission:kirim-slip.send');
        Route::post('/slip/kirim-jobs/{job}/dilihat', [SlipController::class, 'sendDilihat'])->name('slip.send.dilihat')->middleware('permission:kirim-slip.view');
        Route::get('/slip/preview', [SlipController::class, 'preview'])->name('slip.preview')->middleware('permission:kirim-slip.view');
        Route::delete('/slip/preview', [SlipController::class, 'hapusSemuaPreview'])->name('slip.preview.hapus-semua')->middleware('permission:kirim-slip.import');
        Route::delete('/slip/preview/{payroll}', [SlipController::class, 'hapusPreview'])->name('slip.preview.hapus')->middleware('permission:kirim-slip.import');
        Route::get('/slip/{payroll}/pdf', [SlipController::class, 'pdf'])->name('slip.pdf')->middleware('permission:detail-riwayat.view');
        Route::post('/slip/kirim-semua', [SlipController::class, 'storeAll'])->name('slip.kirim-semua')->middleware('permission:kirim-slip.send');
        Route::post('/slip/kirim-divisi/{divisi}', [SlipController::class, 'storeDivisi'])->name('slip.kirim-divisi')->middleware('permission:kirim-slip.send');
        Route::post('/slip/{payroll}/kirim', [SlipController::class, 'kirimSatu'])->name('slip.kirim-satu')->middleware('permission:kirim-slip.send');
    });
});
