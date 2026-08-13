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

    // Ambil potongan teks di sekitar kata kunci yang cocok (dari isi PDF, abstrak,
    // atau keyword), dengan kata kuncinya di-highlight <mark>. Dipakai di hasil pencarian,
    // mirip cuplikan hasil Google Search. Return HTML string (aman, sudah di-escape),
    // atau null kalau tidak ada kecocokan / tidak ada kata kunci.
    public function searchSnippet(?string $keyword, int $contextLength = 180): ?string
    {
        if (blank($keyword)) {
            return null;
        }

        // Urutan prioritas: isi PDF dulu (paling informatif), baru abstrak, baru keyword
        foreach ([$this->pdf_text, $this->abstract, $this->keywords] as $field) {
            if (blank($field)) {
                continue;
            }

            $position = mb_stripos($field, $keyword);

            if ($position === false) {
                continue;
            }

            $start = max(0, $position - intdiv($contextLength, 2));
            $raw = mb_substr($field, $start, $contextLength);

            // Hasil ekstraksi teks PDF sering berantakan (baris baru, spasi ganda) — rapikan dulu
            $raw = trim(preg_replace('/\s+/u', ' ', $raw));

            $prefix = $start > 0 ? '&hellip; ' : '';
            $suffix = ($start + $contextLength) < mb_strlen($field) ? ' &hellip;' : '';

            // Escape dulu (isi PDF adalah teks mentah, bukan HTML tepercaya), baru highlight
            $escaped = e($raw);
            $highlighted = preg_replace(
                '/(' . preg_quote(e($keyword), '/') . ')/iu',
                '<mark>$1</mark>',
                $escaped
            );

            return $prefix . $highlighted . $suffix;
        }

        return null;
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
