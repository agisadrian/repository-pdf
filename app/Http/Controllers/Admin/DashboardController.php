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

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentDocuments' => $recentDocuments,
        ]);
    }
}
