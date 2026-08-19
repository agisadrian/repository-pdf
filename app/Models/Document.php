<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'author',
        'publisher',
        'abstract',
        'keywords',
        'year',
        'month',
        'cover',
        'pdf_file',
        'pdf_text',
        'total_pages',
        'download_count',
        'view_count',
        'category_id',
        'created_by',
    ];

    const MONTH_NAMES = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function getMonthNameAttribute(): ?string
    {
        return $this->month ? self::MONTH_NAMES[$this->month] : null;
    }

    // Label buat header grouping, misal "Agustus 2026" / "2026" / "Tahun Tidak Diketahui"
    public function getPeriodLabelAttribute(): string
    {
        if (! $this->year) {
            return 'Tahun Tidak Diketahui';
        }

        return $this->month_name ? "{$this->month_name} {$this->year}" : (string) $this->year;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function views()
    {
        return $this->hasMany(DocumentView::class);
    }

    // Jumlah IP berbeda yang pernah buka dokumen ini -- beda dari view_count yang
    // ngitung TOTAL kunjungan (1 IP yang balik lagi besok tetap nambah view_count,
    // tapi nggak nambah jumlah pengunjung unik karena IP-nya sudah pernah kehitung).
    public function getUniqueViewersCountAttribute(): int
    {
        return $this->views()->distinct('ip_address')->count('ip_address');
    }

    // Ambil abstrak dokumen: kalau admin sudah isi abstrak manual, pakai itu.
    // Kalau kosong, otomatis ambil 2-3 kalimat pertama dari isi PDF sebagai gantinya,
    // ditandai sebagai ringkasan otomatis. Dipakai buat tampilan halaman detail & meta
    // description, biar dokumen tetap ada gambaran isinya walau admin belum sempat isi abstrak.
    public function getDisplayAbstractAttribute(): ?string
    {
        if (filled($this->abstract)) {
            return $this->abstract;
        }

        if (blank($this->pdf_text)) {
            return null;
        }

        // Rapikan dulu teks hasil ekstraksi PDF (baris baru/spasi berantakan)
        $text = trim(preg_replace('/\s+/u', ' ', $this->pdf_text));

        // Ambil 2-3 kalimat pertama, berhenti di titik/tanda tanya/seru, maksimal ~280 karakter
        preg_match_all('/[^.!?]+[.!?]+/u', $text, $matches);
        $sentences = array_slice($matches[0] ?? [], 0, 3);

        if (empty($sentences)) {
            // Kalau nggak ketemu pola kalimat (jarang, tapi jaga-jaga), potong langsung
            return mb_substr($text, 0, 240) . (mb_strlen($text) > 240 ? '…' : '');
        }

        $summary = trim(implode(' ', $sentences));

        return mb_strlen($summary) > 320
            ? mb_substr($summary, 0, 320) . '…'
            : $summary;
    }

    // True kalau abstrak yang ditampilkan itu hasil auto-generate dari isi PDF
    // (bukan yang diisi manual sama admin) — dipakai buat kasih label "Ringkasan otomatis"
    public function getAbstractIsAutoAttribute(): bool
    {
        return blank($this->abstract) && filled($this->display_abstract);
    }

    // Ambil potongan teks (dari isi PDF, abstrak, atau keyword) yang paling banyak memuat
    // kata-kata kunci yang cocok, dengan tiap kata kuncinya di-highlight <mark>. Dipakai di
    // hasil pencarian, mirip cuplikan hasil Google Search.
    //
    // PENTING: sejak pencarian dokumen (lihat DocumentController::search()) memakai MySQL
    // FULLTEXT boolean mode -- yang mensyaratkan SEMUA kata kunci ada di teks TAPI TIDAK
    // HARUS bersebelahan/nempel jadi satu frasa -- method ini ikut mencari kata SATU PER
    // SATU (bukan mencari "kata kunci lengkap" sebagai satu frasa utuh), lalu memilih jendela
    // teks yang memuat kata-kata berbeda paling banyak. Kalau tetap mencari sebagai frasa
    // utuh, banyak dokumen yang valid cocok di pencarian akan gagal dapat cuplikan (snippet-
    // nya kosong) walau sebenarnya semua kata kuncinya ada, cuma di lokasi terpisah.
    public function searchSnippet(?string $keyword, int $contextLength = 220): ?string
    {
        if (blank($keyword)) {
            return null;
        }

        // Pecah kata kunci jadi kata-kata terpisah, dengan cara yang SAMA seperti
        // DocumentController::buildBooleanFullTextQuery(), biar konsisten dengan kata
        // apa saja yang sebenarnya dipakai buat mencocokkan dokumen ini di pencarian.
        $words = collect(preg_split('/\s+/u', trim($keyword)))
            ->map(fn ($w) => preg_replace('/[+\-><()~*"@]/u', '', $w))
            ->filter(fn ($w) => $w !== '')
            ->values();

        if ($words->isEmpty()) {
            return null;
        }

        $bestField = null;
        $bestMatches = [];

        // Urutan prioritas field: isi PDF dulu (paling informatif), baru abstrak, baru keyword
        foreach ([$this->pdf_text, $this->abstract, $this->keywords] as $field) {
            if (blank($field)) {
                continue;
            }

            // Rapikan semua jenis spasi (spasi biasa, non-breaking space, tab, baris baru
            // -- sering campur aduk di hasil ekstraksi teks PDF) jadi satu spasi biasa
            $normalized = preg_replace('/[\s\x{00A0}\x{200B}]+/u', ' ', $field);

            $matches = [];

            foreach ($words as $wordIndex => $word) {
                $offset = 0;

                // Cari SEMUA kemunculan kata ini (bukan cuma yang pertama), biar bisa
                // milih jendela cuplikan yang paling banyak memuat kata kunci berbeda
                while (($pos = mb_stripos($normalized, $word, $offset)) !== false) {
                    $matches[] = ['pos' => $pos, 'word' => $wordIndex];
                    $offset = $pos + 1;

                    if (count($matches) > 200) {
                        break 2; // jaga-jaga biar nggak berat di dokumen yang sangat panjang
                    }
                }
            }

            if (empty($matches)) {
                continue;
            }

            $distinctWords = collect($matches)->pluck('word')->unique()->count();
            $bestDistinctSoFar = $bestMatches === [] ? -1 : collect($bestMatches)->pluck('word')->unique()->count();

            if ($distinctWords > $bestDistinctSoFar) {
                $bestField = $normalized;
                $bestMatches = $matches;
            }

            // Field ini sudah memuat SEMUA kata kunci? nggak perlu cek field lain lagi
            if ($distinctWords === $words->count()) {
                break;
            }
        }

        if ($bestField === null) {
            return null;
        }

        // Cari jendela (window) sepanjang $contextLength karakter yang memuat kata kunci
        // BERBEDA paling banyak, pakai teknik sliding window dua pointer
        usort($bestMatches, fn ($a, $b) => $a['pos'] <=> $b['pos']);

        $windowStart = $bestMatches[0]['pos'];
        $bestScore = -1;
        $left = 0;

        foreach ($bestMatches as $right => $match) {
            while ($match['pos'] - $bestMatches[$left]['pos'] > $contextLength) {
                $left++;
            }

            $distinctInWindow = collect(array_slice($bestMatches, $left, $right - $left + 1))
                ->pluck('word')
                ->unique()
                ->count();

            if ($distinctInWindow > $bestScore) {
                $bestScore = $distinctInWindow;
                $windowStart = $bestMatches[$left]['pos'];
            }
        }

        $start = max(0, $windowStart - 40);
        $raw = trim(mb_substr($bestField, $start, $contextLength));

        $prefix = $start > 0 ? '&hellip; ' : '';
        $suffix = ($start + $contextLength) < mb_strlen($bestField) ? ' &hellip;' : '';

        // Escape dulu (isi PDF adalah teks mentah, bukan HTML tepercaya), baru highlight.
        //
        // PENTING: semua kata kunci digabung jadi SATU pola regex dan di-replace SEKALI
        // JALAN -- bukan di-loop per kata satu-satu seperti sebelumnya. Kalau di-loop,
        // ada 2 bug yang muncul:
        //   1. Kata kunci yang sama muncul 2x di query (mis. "informatika informatika")
        //      bikin teks yang sudah di-<mark> ke-highlight LAGI oleh kata kedua, jadi
        //      nested <mark><mark>...</mark></mark> (HTML rusak, hitungan navigasi salah).
        //   2. Kalau 1 kata kunci adalah bagian dari kata kunci lain (mis. "kerja" di
        //      dalam "pekerjaan"), kata pendeknya di-highlight duluan lalu MEMUTUS teks
        //      "pekerjaan" jadi "pe<mark>kerja</mark>an" -- pas giliran kata "pekerjaan"
        //      dicari, teksnya sudah gak utuh lagi (ke-selip tag <mark>), jadi GAGAL
        //      ke-highlight sama sekali walau jelas ada di teks.
        //
        // Fix: kata kunci di-unique-kan dulu, diurutkan dari yang PALING PANJANG, baru
        // digabung jadi satu pola alternation (kata|kata2|...). Urutan terpanjang-dulu
        // penting biar regex nyoba cocokin "pekerjaan" duluan sebelum "kerja" sempat
        // "nyerobot" sebagian dari kata itu.
        $escaped = e($raw);

        $uniqueWords = $words->unique()->sortByDesc(fn ($w) => mb_strlen($w))->values();

        if ($uniqueWords->isNotEmpty()) {
            $pattern = $uniqueWords->map(fn ($w) => preg_quote(e($w), '/'))->implode('|');

            $escaped = preg_replace('/(' . $pattern . ')/iu', '<mark>$1</mark>', $escaped);
        }

        return $prefix . $escaped . $suffix;
    }

    // Scope untuk pencarian judul + full-text sederhana
    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        return $query->whereFullText(
            ['title', 'abstract', 'keywords', 'pdf_text'],
            $term
        );
    }
}
