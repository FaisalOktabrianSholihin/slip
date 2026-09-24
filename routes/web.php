<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisiController;
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

    // Halaman-halaman utama aplikasi (layout dengan sidebar & topheader)
    Route::view('/dashboard', 'pages.dashboard')->name('dashboard');

    Route::view('/data-user', 'pages.data-user')->name('data-user');
    Route::view('/data-divisi', 'pages.data-divisi')->name('data-divisi');
    Route::view('/data-jabatan', 'pages.data-jabatan')->name('data-jabatan');
    Route::view('/data-karyawan', 'pages.data-karyawan')->name('data-karyawan');

    Route::view('/kirim-slip', 'pages.kirim-slip')->name('kirim-slip');
    Route::view('/preview-slip', 'pages.preview-slip')->name('preview-slip');
    Route::view('/detail-riwayat', 'pages.detail-riwayat')->name('detail-riwayat');

    Route::view('/role-management', 'pages.role-management')->name('role-management');
    Route::view('/pengaturan', 'pages.pengaturan')->name('pengaturan');
    Route::view('/log-aktivitas', 'pages.log-aktivitas')->name('log-aktivitas');

    /*
    |----------------------------------------------------------------
    | API (dipanggil dari public/js/*.js lewat window.Api / fetch)
    |----------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {
        // Data master (database db_indukk)
        Route::get('/divisi', [DivisiController::class, 'index'])->name('divisi.index');
        Route::post('/divisi', [DivisiController::class, 'store'])->name('divisi.store');
        Route::put('/divisi/{divisi}', [DivisiController::class, 'update'])->name('divisi.update');
        Route::delete('/divisi/{divisi}', [DivisiController::class, 'destroy'])->name('divisi.destroy');

        Route::get('/jabatan', [JabatanController::class, 'index'])->name('jabatan.index');
        Route::post('/jabatan', [JabatanController::class, 'store'])->name('jabatan.store');
        Route::put('/jabatan/{jabatan}', [JabatanController::class, 'update'])->name('jabatan.update');
        Route::delete('/jabatan/{jabatan}', [JabatanController::class, 'destroy'])->name('jabatan.destroy');

        Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
        Route::post('/karyawan', [KaryawanController::class, 'store'])->name('karyawan.store');
        Route::put('/karyawan/{karyawan}', [KaryawanController::class, 'update'])->name('karyawan.update');
        Route::delete('/karyawan/{karyawan}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');
        Route::post('/karyawan/import', [KaryawanController::class, 'import'])->name('karyawan.import');

        // Pengguna aplikasi & role (database gajii)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Pengaturan aplikasi (single-row)
        Route::get('/pengaturan', [SettingController::class, 'show'])->name('pengaturan.show');
        Route::put('/pengaturan', [SettingController::class, 'update'])->name('pengaturan.update');

        // Log aktivitas (read-only + tambah manual dari klien)
        Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])->name('log-aktivitas.index');
        Route::post('/log-aktivitas', [ActivityLogController::class, 'store'])->name('log-aktivitas.store');

        // Dashboard ringkasan
        Route::get('/dashboard-summary', [DashboardController::class, 'summary'])->name('dashboard.summary');

        // Riwayat pengiriman slip (untuk detail-riwayat.blade.php)
        Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat.index');

        // Kirim Slip Gaji (padanan SlipController@upload/@preview/@storeDivisi/@storeAll)
        Route::post('/slip/import', [SlipController::class, 'importPayroll'])->name('slip.import');
        Route::get('/slip/preview', [SlipController::class, 'preview'])->name('slip.preview');
        Route::get('/slip/{payroll}/pdf', [SlipController::class, 'pdf'])->name('slip.pdf');
        Route::post('/slip/kirim-semua', [SlipController::class, 'storeAll'])->name('slip.kirim-semua');
        Route::post('/slip/kirim-divisi/{divisi}', [SlipController::class, 'storeDivisi'])->name('slip.kirim-divisi');
        Route::post('/slip/{payroll}/kirim', [SlipController::class, 'kirimSatu'])->name('slip.kirim-satu');
    });
});
