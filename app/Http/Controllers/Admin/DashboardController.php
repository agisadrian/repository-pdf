<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'documents' => Document::count(),
            'categories' => Category::count(),
            'users' => User::count(),
            'total_views' => Document::sum('view_count'),
            'total_downloads' => Document::sum('download_count'),
        ];

        $recentDocuments = Document::with('category')
            ->latest()
            ->take(5)
            ->get();

        // Dokumen per kategori, diurutkan dari yang paling banyak
        $documentsPerCategory = Category::withCount('documents')
            ->orderByDesc('documents_count')
            ->get();

        // Upload per bulan, 6 bulan terakhir (termasuk bulan yang 0 dokumen, biar grafik tetap konsisten)
        $uploadsPerMonth = collect(range(5, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            $count = Document::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            return [
                'label' => $date->translatedFormat('M Y'),
                'count' => $count,
            ];
        });

        // 5 dokumen paling banyak dilihat sepanjang waktu
        $topDocuments = Document::with('category')
            ->orderByDesc('view_count')
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentDocuments' => $recentDocuments,
            'documentsPerCategory' => $documentsPerCategory,
            'uploadsPerMonth' => $uploadsPerMonth,
            'topDocuments' => $topDocuments,
            // Tombol "Jadikan Saya Super Admin" cuma muncul kalau: (a) belum ada
            // Super Admin sama sekali di sistem, dan (b) yang login sekarang admin biasa.
            // Ini jalan keluar biar bisa naik jadi Super Admin pertama kali tanpa buka database.
            'canBecomeSuperAdmin' => ! Auth::user()->isSuperAdmin() && ! User::where('role', 'super_admin')->exists(),
        ]);
    }

    // Naikin diri sendiri jadi Super Admin. Cuma bisa dipakai SEKALI, sebelum ada
    // Super Admin lain -- setelah itu, ganti role harus lewat halaman Kelola Pengguna
    // (yang cuma bisa diakses Super Admin), bukan lewat sini lagi.
    public function becomeSuperAdmin()
    {
        if (User::where('role', 'super_admin')->exists()) {
            abort(403, 'Sudah ada Super Admin di sistem ini. Minta Super Admin yang ada buat naikin role kamu lewat halaman Kelola Pengguna.');
        }

        Auth::user()->update(['role' => 'super_admin']);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Selamat, kamu sekarang Super Admin! Sekarang kamu bisa akses "Kelola Kategori" dan "Kelola Pengguna".');
    }
}
