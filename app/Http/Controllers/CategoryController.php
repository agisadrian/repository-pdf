<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    // Halaman /kategori — nampilin semua kategori sebagai pintu masuk browsing,
    // masing-masing dengan jumlah dokumen dan 1 cover representatif (dokumen terbaru di kategori itu)
    public function index()
    {
        $categories = Category::withCount('documents')
            ->orderByDesc('documents_count')
            ->get()
            ->map(function ($category) {
                $latestWithCover = $category->documents()
                    ->whereNotNull('cover')
                    ->latest()
                    ->first();

                $category->cover_url = $latestWithCover
                    ? Storage::url($latestWithCover->cover)
                    : null;

                return $category;
            });

        return view('category.index', [
            'categories' => $categories,
        ]);
    }
}
