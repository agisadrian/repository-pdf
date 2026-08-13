<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Ambil dokumen terbaru buat ditampilin di halaman Home
        $documents = Document::with(['category'])
            ->latest()
            ->take(8)
            ->get();

        return view('home', [
            'documents' => $documents,
            'totalDocuments' => Document::count(),
            'totalViews' => Document::sum('view_count'),
            'totalDownloads' => Document::sum('download_count'),
        ]);
    }
}
