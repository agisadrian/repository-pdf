<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // Halaman detail 1 dokumen, diakses lewat slug (contoh: /dokumen/judul-dokumen)
    public function show(string $slug, Request $request)
    {
        $document = Document::with(['category', 'creator'])
            ->where('slug', $slug)
            ->firstOrFail();

        $this->recordView($document, $request->ip());

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

    // Catat 1 kali lihat dari 1 IP, dibatasi 1x per 24 jam per dokumen.
    // Jadi refresh berkali-kali dalam sehari nggak bikin view_count nambah terus.
    // Tiap kunjungan yang lolos batas ini juga dicatat ke tabel document_views,
    // dipakai buat hitung jumlah PENGUNJUNG UNIK (beda dari total_view yang bisa
    // dobel kalau orang yang sama balik lagi besok/lusa).
    private function recordView(Document $document, ?string $ipAddress): void
    {
        $ipAddress = $ipAddress ?: '0.0.0.0';

        $alreadyViewedToday = DocumentView::where('document_id', $document->id)
            ->where('ip_address', $ipAddress)
            ->where('viewed_at', '>=', now()->subDay())
            ->exists();

        if ($alreadyViewedToday) {
            return;
        }

        DocumentView::create([
            'document_id' => $document->id,
            'ip_address' => $ipAddress,
            'viewed_at' => now(),
        ]);

        $document->increment('view_count');
    }

    // Bikin query "boolean mode" buat MATCH() AGAINST(), dipake bareng oleh suggest() dan search().
    // Tiap kata dikasih + (wajib ada) dan * (biar cocok juga walau kata belum selesai diketik).
    private function buildBooleanFullTextQuery(string $keyword): string
    {
        return collect(preg_split('/\s+/', trim($keyword)))
            ->map(fn ($word) => preg_replace('/[+\-><()~*"@]/', '', $word))
            ->filter(fn ($word) => $word !== '')
            ->map(fn ($word) => '+' . $word . '*')
            ->implode(' ');
    }

    // Endpoint autocomplete: dipanggil AJAX pas orang lagi ngetik di kolom cari.
    // Balikin JSON ringan (cuma judul, slug, cover) -- bukan hasil pencarian lengkap.
    public function suggest(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));

        // Kurang dari 2 karakter: jangan query dulu, hasilnya bakal terlalu luas/berat
        if (mb_strlen($keyword) < 2) {
            return response()->json([]);
        }

        // FULLTEXT (MATCH AGAINST) butuh minimal 3 karakter per kata biar match ke index-nya
        // (bawaan MySQL/InnoDB: innodb_ft_min_token_size = 3). Buat 2 karakter, fallback ke LIKE
        // di judul doang -- tetep ringan karena cuma nyentuh 1 kolom pendek, bukan pdf_text.
        if (mb_strlen($keyword) < 3) {
            $documents = Document::query()
                ->where('title', 'like', $keyword . '%')
                ->orderByDesc('view_count')
                ->limit(8)
                ->get(['slug', 'title', 'cover', 'author']);

            return response()->json($this->formatSuggestions($documents));
        }

        // 3+ karakter: pake FULLTEXT index yang udah ada di migration (title, abstract,
        // keywords, pdf_text sekaligus) -- jadi autocomplete ini juga nyentuh isi PDF,
        // tapi tetep cepat karena baca dari index, bukan scan LIKE '%...%' di kolom longtext.
        $booleanQuery = $this->buildBooleanFullTextQuery($keyword);

        // Kalau setelah dibersihin ternyata kosong semua (keyword-nya cuma simbol aneh),
        // jangan lanjut query FULLTEXT -- balikin kosong aja daripada bikin syntax error MySQL
        if ($booleanQuery === '') {
            return response()->json([]);
        }

        $documents = Document::query()
            ->whereFullText(['title', 'abstract', 'keywords', 'pdf_text'], $booleanQuery, ['mode' => 'boolean'])
            ->orderByDesc('view_count')
            ->limit(8)
            ->get(['slug', 'title', 'cover', 'author']);

        return response()->json($this->formatSuggestions($documents));
    }

    private function formatSuggestions($documents)
    {
        return $documents->map(fn ($doc) => [
            'title' => $doc->title,
            'author' => $doc->author,
            'url' => route('document.show', $doc->slug),
            'cover' => $doc->cover ? Storage::url($doc->cover) : null,
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
                $keyword = trim($keyword);

                // Kata pendek (di bawah 3 huruf): FULLTEXT nggak bakal match ke index-nya,
                // jadi tetep pake LIKE lama -- di skala kecil ini masih cukup cepat.
                if (mb_strlen($keyword) < 3) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('title', 'like', '%' . $keyword . '%')
                            ->orWhere('abstract', 'like', '%' . $keyword . '%')
                            ->orWhere('keywords', 'like', '%' . $keyword . '%')
                            ->orWhere('pdf_text', 'like', '%' . $keyword . '%');
                    });

                    return;
                }

                // 3+ huruf: pake FULLTEXT index (MATCH AGAINST), baca dari index bukan scan
                // ulang tiap baris -- jauh lebih ringan begitu jumlah/isi dokumen makin banyak.
                $booleanQuery = $this->buildBooleanFullTextQuery($keyword);

                if ($booleanQuery !== '') {
                    $query->whereFullText(['title', 'abstract', 'keywords', 'pdf_text'], $booleanQuery, ['mode' => 'boolean']);
                }
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
