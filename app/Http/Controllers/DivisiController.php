<?php

namespace App\Http\Controllers;

use App\Models\MasterData\Divisi;
use Illuminate\Http\Request;

/**
 * DivisiController
 *
 * Menyambungkan public/js/data-divisi.js (yang sebelumnya memakai
 * array in-memory `divisiList`) ke tabel `divisis` di database
 * db_indukk. Semua response memakai bentuk {id, nama} supaya JS
 * tidak perlu diubah banyak, hanya sumber datanya saja.
 */
class DivisiController extends Controller
{
    public function index()
    {
        return Divisi::orderBy('nama_divisi')->get()->map(fn (Divisi $d) => [
            'id' => $d->id,
            'nama' => $d->nama_divisi,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150', 'unique:db_induk.divisis,nama_divisi'],
        ]);

        $divisi = Divisi::create(['nama_divisi' => $data['nama']]);

        return response()->json(['id' => $divisi->id, 'nama' => $divisi->nama_divisi], 201);
    }

    public function update(Request $request, Divisi $divisi)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150', 'unique:db_induk.divisis,nama_divisi,'.$divisi->id],
        ]);

        $divisi->update(['nama_divisi' => $data['nama']]);

        return response()->json(['id' => $divisi->id, 'nama' => $divisi->nama_divisi]);
    }

    public function destroy(Divisi $divisi)
    {
        if ($divisi->karyawans()->exists()) {
            return response()->json([
                'message' => 'Divisi tidak bisa dihapus karena masih dipakai oleh data karyawan.',
            ], 422);
        }

        $divisi->delete();

        return response()->json(['ok' => true]);
    }
}
