<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class AdminRequestController extends Controller
{
    // Daftar semua user yang lagi ngajuin jadi Admin dan belum diproses
    public function index()
    {
        $pendingRequests = User::where('role', 'user')
            ->whereNotNull('admin_requested_at')
            ->orderBy('admin_requested_at')
            ->get();

        return view('admin.requests.index', [
            'pendingRequests' => $pendingRequests,
        ]);
    }

    // Setujui: naikin role user jadi 'admin', bersihkan tanda pengajuan
    public function approve(User $user)
    {
        if (! $user->hasPendingAdminRequest()) {
            return back()->with('error', 'Pengajuan ini sudah tidak berlaku (mungkin sudah diproses sebelumnya).');
        }

        $user->update([
            'role' => 'admin',
            'admin_requested_at' => null,
        ]);

        return back()->with('success', 'Pengajuan "' . $user->name . '" disetujui. User ini sekarang Admin.');
    }

    // Tolak: bersihkan tanda pengajuan, role tetap 'user'
    public function reject(User $user)
    {
        if (! $user->hasPendingAdminRequest()) {
            return back()->with('error', 'Pengajuan ini sudah tidak berlaku (mungkin sudah diproses sebelumnya).');
        }

        $user->update([
            'admin_requested_at' => null,
        ]);

        return back()->with('success', 'Pengajuan "' . $user->name . '" ditolak.');
    }
}
