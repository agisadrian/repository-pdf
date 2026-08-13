<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // Halaman detail 1 dokumen, diakses lewat slug (contoh: /dokumen/judul-dokumen)
    public function show(string $slug)
    {
        $document = Document::with(['category', 'creator'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Hitung jumlah kali dokumen ini dilihat
        $document->increment('view_count');

        // Dokumen terkait: dari kategori yang sama, dokumen ini sendiri di-exclude
        $relatedDocuments = $document->category_id
            ? Document::where('category_id', $document->category_id)
                ->where('id', '!=', $document->id)
                ->latest()
                ->take(4)
                ->get()
            : collect();

        return view('document.show', [
            'document' => $document,
            'relatedDocuments' => $relatedDocuments,
        ]);
    }

    // Halaman pencarian dokumen — nyari di judul, abstrak, keyword, isi teks PDF,
    // bisa difilter per kategori, tahun, bulan, diurutkan, dikelompokkan per bulan-tahun terbit
    public function search(Request $request)
    {
        $keyword = $request->query('q');
        $categoryId = $request->query('category');
        $year = $request->query('year');
        $month = $request->query('month');
        $sort = $request->query('sort', 'newest');

        $documents = Document::with(['category'])
            ->when($keyword, function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', '%' . $keyword . '%')
                        ->orWhere('abstract', 'like', '%' . $keyword . '%')
                        ->orWhere('keywords', 'like', '%' . $keyword . '%')
                        ->orWhere('pdf_text', 'like', '%' . $keyword . '%');
                });
            })
            ->when($categoryId, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($year, function ($query, $year) {
                $query->where('year', $year);
            })
            ->when($month, function ($query, $month) {
                $query->where('month', $month);
            })
            ->when($sort === 'title', function ($query) {
                $query->orderBy('title');
            })
            ->when($sort === 'popular', function ($query) {
                $query->orderByDesc('view_count');
            })
            ->when($sort === 'newest' || ! in_array($sort, ['title', 'popular', 'newest']), function ($query) {
                $query->orderByRaw('year IS NULL, year DESC')
                    ->orderByRaw('month IS NULL, month DESC')
                    ->latest();
            })
            ->paginate(12)
            ->withQueryString();

        // Kelompokkan hasil di halaman ini per label periode (misal "Agustus 2026")
        // Kalau lagi diurutkan berdasarkan judul/popularitas, grouping per periode nggak relevan
        $grouped = $sort === 'newest'
            ? $documents->getCollection()->groupBy('period_label')
            : collect(['Hasil Pencarian' => $documents->getCollection()]);

        // Daftar tahun yang tersedia (buat dropdown filter), diambil dari data yang ada
        $years = Document::query()
            ->whereNotNull('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('document.search', [
            'documents' => $documents,
            'grouped' => $grouped,
            'keyword' => $keyword,
            'categoryId' => $categoryId,
            'year' => $year,
            'month' => $month,
            'sort' => $sort,
            'categories' => \App\Models\Category::orderBy('name')->get(),
            'years' => $years,
            'months' => Document::MONTH_NAMES,
        ]);
    }

    // Download file PDF asli + hitung jumlah download
    public function download(string $slug)
    {
        $document = Document::where('slug', $slug)->firstOrFail();

        if (! $document->pdf_file || ! Storage::disk('public')->exists($document->pdf_file)) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        $document->increment('download_count');

        $downloadName = \Illuminate\Support\Str::slug($document->title) . '.pdf';

        return Storage::disk('public')->download($document->pdf_file, $downloadName);
    }

    // Tampilkan PDF langsung di browser (buat fitur "Baca Online"), tanpa dihitung sebagai download
    public function preview(string $slug)
    {
        $document = Document::where('slug', $slug)->firstOrFail();

        if (! $document->pdf_file || ! Storage::disk('public')->exists($document->pdf_file)) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        // response() (bukan download()) defaultnya "inline", jadi browser nampilin PDF-nya
        // langsung di dalam halaman, bukan minta save-as.
        return Storage::disk('public')->response($document->pdf_file);
    }
}
