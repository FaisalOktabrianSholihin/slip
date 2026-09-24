<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * RoleController
 *
 * Menyambungkan public/js/role-management.js (variabel ROLES) ke
 * tabel roles (gajii). `akses` di frontend = kolom `permissions`
 * (JSON) di database.
 */
class RoleController extends Controller
{
    protected function toJson(Role $r): array
    {
        return [
            'id' => $r->id,
            'nama' => $r->nama,
            'deskripsi' => $r->deskripsi,
            'jumlahPengguna' => $r->users_count ?? $r->users()->count(),
            'akses' => $r->permissions ?? [],
        ];
    }

    public function index()
    {
        return Role::withCount('users')->orderBy('nama')->get()->map(fn (Role $r) => $this->toJson($r));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'akses' => ['array'],
            'akses.*' => ['string'],
        ]);

        $slug = Str::slug($data['nama']);
        $slug = Role::where('slug', $slug)->exists() ? $slug.'-'.Str::random(4) : $slug;

        $role = Role::create([
            'nama' => $data['nama'],
            'slug' => $slug,
            'deskripsi' => $data['deskripsi'] ?? null,
            'permissions' => $data['akses'] ?? [],
        ]);

        ActivityLog::catat('Tambah role', "Menambahkan role {$role->nama}.");

        return response()->json($this->toJson($role), 201);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'akses' => ['array'],
            'akses.*' => ['string'],
        ]);

        $role->update([
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'permissions' => $data['akses'] ?? [],
        ]);

        ActivityLog::catat('Ubah role', "Memperbarui role {$role->nama}.");

        return response()->json($this->toJson($role));
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Role tidak bisa dihapus karena masih dipakai oleh user. Pindahkan user tersebut ke role lain dahulu.',
            ], 422);
        }

        $role->delete();

        ActivityLog::catat('Hapus role', "Menghapus role {$role->nama}.");

        return response()->json(['ok' => true]);
    }
}
