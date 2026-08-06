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

        return view('document.show', [
            'document' => $document,
        ]);
    }

    // Halaman pencarian dokumen — sekarang nyari di judul, abstrak, keyword,
    // DAN isi teks di dalam file PDF-nya (hasil ekstraksi pdf_text)
    public function search(Request $request)
    {
        $keyword = $request->query('q');

        $documents = Document::with(['category'])
            ->when($keyword, function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', '%' . $keyword . '%')
                        ->orWhere('abstract', 'like', '%' . $keyword . '%')
                        ->orWhere('keywords', 'like', '%' . $keyword . '%')
                        ->orWhere('pdf_text', 'like', '%' . $keyword . '%');
                });
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('document.search', [
            'documents' => $documents,
            'keyword' => $keyword,
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
