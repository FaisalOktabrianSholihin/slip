<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * UserController
 *
 * Menyambungkan public/js/data-user.js ke tabel users+roles (gajii).
 * Password diinput sendiri oleh admin lewat form (dengan konfirmasi).
 * Saat edit, password boleh dikosongkan (berarti tidak diubah).
 * Hash dilakukan otomatis oleh cast 'hashed' pada model User.
 */
class UserController extends Controller
{
    protected function toJson(User $u): array
    {
        return [
            'id' => $u->id,
            'nama' => $u->name,
            'email' => $u->email,
            'role' => $u->role?->slug,
        ];
    }

    public function index()
    {
        return User::with('role')->orderBy('name')->get()->map(fn(User $u) => $this->toJson($u));
    }

    protected function pesan(): array
    {
        return [
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama dengan password.',
            'email.unique' => 'Email sudah dipakai user lain.',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::exists('roles', 'slug')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], $this->pesan());

        $user = User::create([
            'name' => $data['nama'],
            'email' => $data['email'],
            'password' => $data['password'], // di-hash oleh cast 'hashed'
            'role_id' => Role::where('slug', $data['role'])->value('id'),
            'status_aktif' => true,
        ]);

        ActivityLog::catat('Tambah user', "Menambahkan user {$user->name} ({$user->email}).");

        return response()->json($this->toJson($user->load('role')), 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::exists('roles', 'slug')],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], $this->pesan());

        $user->update([
            'name' => $data['nama'],
            'email' => $data['email'],
            'role_id' => Role::where('slug', $data['role'])->value('id'),
        ]);

        // Password hanya diganti kalau diisi.
        if (! empty($data['password'])) {
            $user->update(['password' => $data['password']]);
        }

        ActivityLog::catat('Ubah user', "Memperbarui user {$user->name} ({$user->email}).");

        return response()->json($this->toJson($user->load('role')));
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Tidak bisa menghapus akun yang sedang login.'], 422);
        }

        $user->delete();

        ActivityLog::catat('Hapus user', "Menghapus user {$user->name} ({$user->email}).");

        return response()->json(['ok' => true]);
    }
}
