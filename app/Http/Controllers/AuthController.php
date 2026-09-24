<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * AuthController
 *
 * Menggantikan simulasi login di public/js/login.js (yang sebelumnya
 * hanya menandai sesi lewat sessionStorage tanpa validasi ke server).
 * Form login memakai field id="username", tapi tabel `users` bawaan
 * Laravel hanya punya kolom `name` & `email` (tidak ada kolom
 * `username`) — sehingga input tersebut dicocokkan ke `email`.
 */
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'], // diisi dengan email
            'password' => ['required', 'string'],
        ]);

        $user = \App\Models\User::where('email', $credentials['username'])->first();

        if (! $user
            || ! $user->status_aktif
            || ! Auth::attempt(['email' => $user->email, 'password' => $credentials['password']])
        ) {
            throw ValidationException::withMessages([
                'username' => 'Username/email atau password salah, atau akun tidak aktif.',
            ]);
        }

        $request->session()->regenerate();

        ActivityLog::catat('Login', "{$user->name} masuk ke sistem.");

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'redirect' => route('dashboard'),
                'user' => [
                    'name' => $user->name,
                    'role' => $user->role?->slug,
                ],
            ]);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        if ($user = Auth::user()) {
            ActivityLog::catat('Logout', "{$user->name} keluar dari sistem.");
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'redirect' => route('login')]);
        }

        return redirect()->route('login');
    }
}
