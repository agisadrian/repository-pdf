<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentController extends Controller
{
    // Tampilkan daftar semua dokumen
    public function index(Request $request)
    {
        $documents = Document::with(['category'])
            ->when($request->query('q'), function ($query, $q) {
                $query->where('title', 'like', '%' . $q . '%');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.documents.index', [
            'documents' => $documents,
            'keyword' => $request->query('q'),
        ]);
    }

    // Tampilkan form tambah dokumen baru
    public function create()
    {
        return view('admin.documents.create', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    // Simpan dokumen baru
    public function store(Request $request)
    {
        $data = $this->validateData($request, null, true);

        $data['slug'] = $this->generateUniqueSlug($data['title']);
        $data['created_by'] = Auth::id();

        // Upload file PDF asli + ekstrak isinya biar bisa dicari
        $pdfInfo = $this->storeAndExtractPdf($request->file('pdf_file'));
        $data['pdf_file'] = $pdfInfo['path'];
        $data['pdf_text'] = $pdfInfo['text'];
        $data['total_pages'] = $pdfInfo['total_pages'];

        // Sampul: pakai upload manual kalau ada, atau hasil auto-generate dari halaman pertama PDF
        unset($data['cover'], $data['cover_auto']);
        $data['cover'] = $this->storeCover($request);

        Document::create($data);

        return redirect()
            ->route('admin.documents.index')
            ->with('success', 'Dokumen berhasil ditambahkan.');
    }

    // Tampilkan form edit dokumen
    public function edit(Document $document)
    {
        return view('admin.documents.edit', [
            'document' => $document,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    // Update dokumen
    public function update(Request $request, Document $document)
    {
        $data = $this->validateData($request, $document->id, false);

        // Slug diupdate cuma kalau judulnya berubah
        if ($data['title'] !== $document->title) {
            $data['slug'] = $this->generateUniqueSlug($data['title'], $document->id);
        }

        // Kalau admin upload file PDF baru, ganti file lama + ekstrak ulang isinya
        if ($request->hasFile('pdf_file')) {
            $pdfInfo = $this->storeAndExtractPdf($request->file('pdf_file'));

            // Hapus file lama dari storage (kalau bukan placeholder)
            if ($document->pdf_file && $document->pdf_file !== 'placeholder.pdf') {
                Storage::disk('public')->delete($document->pdf_file);
            }

            $data['pdf_file'] = $pdfInfo['path'];
            $data['pdf_text'] = $pdfInfo['text'];
            $data['total_pages'] = $pdfInfo['total_pages'];
        }

        unset($data['cover'], $data['cover_auto']);

        $newCover = $this->storeCover($request);
        if ($newCover) {
            // Hapus cover lama kalau ada, ganti dengan yang baru
            if ($document->cover) {
                Storage::disk('public')->delete($document->cover);
            }
            $data['cover'] = $newCover;
        }

        $document->update($data);

        return redirect()
            ->route('admin.documents.index')
            ->with('success', 'Dokumen berhasil diperbarui.');
    }

    // Hapus dokumen (sekalian file PDF-nya dari storage)
    public function destroy(Document $document)
    {
        if ($document->pdf_file && $document->pdf_file !== 'placeholder.pdf') {
            Storage::disk('public')->delete($document->pdf_file);
        }

        if ($document->cover) {
            Storage::disk('public')->delete($document->cover);
        }

        $document->delete();

        return redirect()
            ->route('admin.documents.index')
            ->with('success', 'Dokumen berhasil dihapus.');
    }

    // Tampilkan halaman upload banyak dokumen sekaligus
    public function bulkCreate()
    {
        return view('admin.documents.bulk-create', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    // Simpan 1 dokumen dari proses upload massal (dipanggil berkali-kali via AJAX,
    // 1 request per file PDF, dari halaman Upload Banyak Dokumen)
    public function bulkStore(Request $request)
    {
        $request->validate([
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'cover_auto' => ['nullable', 'string'],
        ]);

        $file = $request->file('pdf_file');

        // Judul diambil otomatis dari nama file (tanda - dan _ dijadiin spasi, huruf awal dikapitalin)
        $title = Str::of(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            ->replace(['-', '_'], ' ')
            ->squish()
            ->title()
            ->toString();

        if (blank($title)) {
            $title = 'Dokumen Tanpa Judul';
        }

        $pdfInfo = $this->storeAndExtractPdf($file);

        $cover = null;
        $auto = $request->input('cover_auto');
        if ($auto && str_starts_with($auto, 'data:image')) {
            [, $encoded] = explode(',', $auto, 2);
            $cover = 'uploads/cover/' . uniqid('auto_') . '.jpg';
            Storage::disk('public')->put($cover, base64_decode($encoded));
        }

        $document = Document::create([
            'title' => $title,
            'slug' => $this->generateUniqueSlug($title),
            'category_id' => $request->input('category_id'),
            'pdf_file' => $pdfInfo['path'],
            'pdf_text' => $pdfInfo['text'],
            'total_pages' => $pdfInfo['total_pages'],
            'cover' => $cover,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Berhasil diupload.',
            'title' => $document->title,
            'edit_url' => route('admin.documents.edit', $document),
        ]);
    }

    // Validasi input form (dipakai bareng di store & update)
    // $pdfRequired: true di store (wajib upload), false di update (opsional, boleh tetap pakai file lama)
    private function validateData(Request $request, ?int $ignoreId = null, bool $pdfRequired = false): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'abstract' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'pdf_file' => [$pdfRequired ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:20480'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_auto' => ['nullable', 'string'],
        ]);
    }

    // Simpan file PDF ke storage + ekstrak teks & jumlah halamannya pakai smalot/pdfparser
    private function storeAndExtractPdf($file): array
    {
        // Simpan ke storage/app/public/uploads/pdf dengan nama unik
        $path = $file->store('uploads/pdf', 'public');

        $text = '';
        $totalPages = 0;

        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile(Storage::disk('public')->path($path));

            $text = $pdf->getText();
            $totalPages = count($pdf->getPages());
        } catch (\Throwable $e) {
            // Kalau PDF-nya rusak/terenkripsi/gagal dibaca, dokumen tetap disimpan
            // hanya saja isi teksnya kosong (nggak ikut kena full-text search)
            $text = '';
        }

        return [
            'path' => $path,
            'text' => $text,
            'total_pages' => $totalPages,
        ];
    }

    // Simpan cover: prioritas file upload manual, kalau nggak ada pakai hasil
    // auto-generate dari halaman pertama PDF (dikirim JS sebagai base64)
    private function storeCover(Request $request): ?string
    {
        if ($request->hasFile('cover')) {
            return $request->file('cover')->store('uploads/cover', 'public');
        }

        $auto = $request->input('cover_auto');

        if ($auto && str_starts_with($auto, 'data:image')) {
            [, $encoded] = explode(',', $auto, 2);
            $binary = base64_decode($encoded);

            $filename = 'uploads/cover/' . uniqid('auto_') . '.jpg';
            Storage::disk('public')->put($filename, $binary);

            return $filename;
        }

        return null;
    }

    // Generate ulang sampul dari PDF yang sudah ada (dipanggil via AJAX dari halaman Kelola Dokumen,
    // buat dokumen lama yang belum sempat dapet sampul otomatis)
    public function generateCover(Request $request, Document $document)
    {
        $request->validate([
            'cover_auto' => ['required', 'string'],
        ]);

        $auto = $request->input('cover_auto');

        if (! str_starts_with($auto, 'data:image')) {
            return response()->json(['message' => 'Data gambar tidak valid.'], 422);
        }

        [, $encoded] = explode(',', $auto, 2);
        $binary = base64_decode($encoded);

        $filename = 'uploads/cover/' . uniqid('auto_') . '.jpg';
        Storage::disk('public')->put($filename, $binary);

        if ($document->cover) {
            Storage::disk('public')->delete($document->cover);
        }

        $document->update(['cover' => $filename]);

        return response()->json([
            'message' => 'Sampul berhasil dibuat.',
            'cover_url' => Storage::url($filename),
        ]);
    }

    // Bikin slug unik dari judul (kalau sudah ada, tambah angka di belakang)
    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 1;

        while (
            Document::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
