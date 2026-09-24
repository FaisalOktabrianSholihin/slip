<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

/**
 * ActivityLogController
 *
 * Menyambungkan public/js/log-aktivitas.js ke tabel activity_logs
 * (gajii). Bentuk baris disamakan dengan yang dipakai
 * SlipStore.getActivityLogs() di slip-common.js lama: {id, user,
 * action, detail, tanggal, jam}.
 */
class ActivityLogController extends Controller
{
    protected function toJson(ActivityLog $log): array
    {
        return [
            'id' => (string) $log->id,
            'user' => $log->user?->name,
            'action' => $log->aksi,
            'detail' => $log->detail,
            'tanggal' => $log->created_at->format('d-m-Y'),
            'jam' => $log->created_at->format('H:i:s'),
        ];
    }

    public function index()
    {
        return ActivityLog::with('user')
            ->latest()
            ->limit(500)
            ->get()
            ->map(fn (ActivityLog $l) => $this->toJson($l));
    }

    /**
     * Dipakai halaman lain (mis. kirim-slip.js) untuk mencatat aktivitas
     * dengan pesan bebas dari sisi klien (mis. "Download template").
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'max:150'],
            'detail' => ['nullable', 'string'],
        ]);

        $log = ActivityLog::catat($data['action'], $data['detail'] ?? null);

        return response()->json($this->toJson($log->load('user')), 201);
    }
}
