<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;

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
        ]);
    }
}
