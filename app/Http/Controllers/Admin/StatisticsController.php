<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentView;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    // Halaman Laporan Statistik: ringkasan angka + grafik kunjungan, khusus admin/super admin.
    // Rentang waktu bisa diganti lewat query string ?days=7|30|90 (default 30 hari terakhir).
    public function index(Request $request)
    {
        $days = (int) $request->query('days', 30);
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;

        $startDate = now()->subDays($days - 1)->startOfDay();

        // Total kunjungan (baris di document_views) dalam rentang waktu yang dipilih
        $totalViewsInRange = DocumentView::where('viewed_at', '>=', $startDate)->count();

        // Pengunjung unik (IP berbeda) dalam rentang waktu yang dipilih -- di seluruh situs,
        // bukan per dokumen (beda dari unique_viewers_count di halaman detail dokumen)
        $uniqueVisitorsInRange = DocumentView::where('viewed_at', '>=', $startDate)
            ->distinct('ip_address')
            ->count('ip_address');

        // Kunjungan per hari, buat digambar jadi grafik batang sederhana.
        // Diisi 0 buat hari yang memang nggak ada kunjungan, biar grafiknya tetap rapi/konsisten.
        $viewsPerDay = DocumentView::selectRaw('DATE(viewed_at) as date, COUNT(*) as total')
            ->where('viewed_at', '>=', $startDate)
            ->groupBy('date')
            ->pluck('total', 'date');

        $dailyChart = collect(range($days - 1, 0))->map(function ($daysAgo) use ($viewsPerDay) {
            $date = now()->subDays($daysAgo);
            $key = $date->format('Y-m-d');

            return [
                'label' => $date->translatedFormat('d M'),
                'count' => (int) ($viewsPerDay[$key] ?? 0),
            ];
        });

        // Dokumen paling banyak dikunjungi DALAM rentang waktu yang dipilih (bukan sepanjang
        // masa -- beda dari "Dokumen Terpopuler" di dashboard yang pakai total view_count)
        $topDocumentsInRange = Document::query()
            ->withCount(['views' => function ($query) use ($startDate) {
                $query->where('viewed_at', '>=', $startDate);
            }])
            ->having('views_count', '>', 0)
            ->orderByDesc('views_count')
            ->take(10)
            ->get();

        return view('admin.statistics', [
            'days' => $days,
            'totalViewsInRange' => $totalViewsInRange,
            'uniqueVisitorsInRange' => $uniqueVisitorsInRange,
            'dailyChart' => $dailyChart,
            'topDocumentsInRange' => $topDocumentsInRange,
        ]);
    }
}
