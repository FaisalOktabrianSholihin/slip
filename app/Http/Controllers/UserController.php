<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * UserController
 *
 * Menyambungkan public/js/data-user.js ke tabel users+roles (gajii).
 * Form frontend TIDAK punya field password (hanya nama/email/role),
 * jadi saat user baru dibuat, password acak digenerate di server dan
 * dikembalikan SATU KALI di response (`generatedPassword`) supaya
 * admin bisa menyampaikannya ke user yang bersangkutan.
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
        return User::with('role')->orderBy('name')->get()->map(fn (User $u) => $this->toJson($u));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::exists('roles', 'slug')],
        ]);

        $generatedPassword = Str::password(12);

        $user = User::create([
            'name' => $data['nama'],
            'email' => $data['email'],
            'password' => Hash::make($generatedPassword),
            'role_id' => Role::where('slug', $data['role'])->value('id'),
            'status_aktif' => true,
        ]);

        ActivityLog::catat('Tambah user', "Menambahkan user {$user->name} ({$user->email}).");

        return response()->json(
            $this->toJson($user->load('role')) + ['generatedPassword' => $generatedPassword],
            201
        );
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::exists('roles', 'slug')],
        ]);

        $user->update([
            'name' => $data['nama'],
            'email' => $data['email'],
            'role_id' => Role::where('slug', $data['role'])->value('id'),
        ]);

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
