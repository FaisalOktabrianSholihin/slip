<?php

namespace App\Http\Controllers;

use App\Models\MasterData\Jabatan;
use Illuminate\Http\Request;

class JabatanController extends Controller
{
    public function index()
    {
        return Jabatan::orderBy('nama_jabatan')->get()->map(fn (Jabatan $j) => [
            'id' => $j->id,
            'nama' => $j->nama_jabatan,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150', 'unique:db_induk.jabatans,nama_jabatan'],
        ]);

        $jabatan = Jabatan::create(['nama_jabatan' => $data['nama']]);

        return response()->json(['id' => $jabatan->id, 'nama' => $jabatan->nama_jabatan], 201);
    }

    public function update(Request $request, Jabatan $jabatan)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150', 'unique:db_induk.jabatans,nama_jabatan,'.$jabatan->id],
        ]);

        $jabatan->update(['nama_jabatan' => $data['nama']]);

        return response()->json(['id' => $jabatan->id, 'nama' => $jabatan->nama_jabatan]);
    }

    public function destroy(Jabatan $jabatan)
    {
        if ($jabatan->karyawans()->exists()) {
            return response()->json([
                'message' => 'Jabatan tidak bisa dihapus karena masih dipakai oleh data karyawan.',
            ], 422);
        }

        $jabatan->delete();

        return response()->json(['ok' => true]);
    }
}
