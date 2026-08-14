<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DocumentSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * NOTE PENTING:
     * Pencarian utama (DocumentController::search & suggest) memakai MySQL/MariaDB
     * FULLTEXT (whereFullText) untuk keyword 3 huruf ke atas. SQLite TIDAK mendukung
     * whereFullText dan akan melempar RuntimeException kalau dipaksa dipakai.
     *
     * Karena phpunit.xml default project ini pakai DB_CONNECTION=sqlite (:memory:),
     * test-test di bawah ini dibagi 2:
     *  - Test yang aman jalan di SQLite: pakai keyword < 3 huruf (lewat jalur fallback
     *    LIKE), filter kategori/tahun, dan endpoint yang tidak menyentuh whereFullText.
     *  - Test khusus FULLTEXT (di bagian bawah): otomatis di-skip kalau koneksi test
     *    bukan mysql/mariadb. Supaya benar-benar tervalidasi, jalankan test ini dengan
     *    DB MySQL, misalnya:
     *      php artisan test --env=testing.mysql
     *    (butuh konfigurasi koneksi "mysql" terpisah untuk testing di phpunit.xml/.env.testing)
     */
    public function test_pencarian_menemukan_dokumen_berdasarkan_judul(): void
    {
        Document::factory()->create(['title' => 'Panduan Skripsi Teknik Informatika']);
        Document::factory()->create(['title' => 'Laporan Praktik Kerja Lapangan']);

        // Keyword 2 huruf -> lewat jalur LIKE (aman di SQLite maupun MySQL)
        $response = $this->get(route('document.search', ['q' => 'Sk']));

        $response->assertOk();
        $response->assertSee('Panduan Skripsi Teknik Informatika');
        $response->assertDontSee('Laporan Praktik Kerja Lapangan');
    }

    public function test_pencarian_dengan_keyword_tidak_cocok_hasil_kosong(): void
    {
        Document::factory()->create(['title' => 'Panduan Skripsi Teknik Informatika']);

        $response = $this->get(route('document.search', ['q' => 'Zx']));

        $response->assertOk();
        $response->assertDontSee('Panduan Skripsi Teknik Informatika');
    }

    public function test_pencarian_bisa_difilter_berdasarkan_kategori(): void
    {
        $kategoriA = Category::factory()->create(['name' => 'Skripsi']);
        $kategoriB = Category::factory()->create(['name' => 'Jurnal']);

        Document::factory()->create(['title' => 'Dokumen Kategori A', 'category_id' => $kategoriA->id]);
        Document::factory()->create(['title' => 'Dokumen Kategori B', 'category_id' => $kategoriB->id]);

        $response = $this->get(route('document.search', ['category' => $kategoriA->id]));

        $response->assertOk();
        $response->assertSee('Dokumen Kategori A');
        $response->assertDontSee('Dokumen Kategori B');
    }

    public function test_pencarian_bisa_difilter_berdasarkan_tahun(): void
    {
        Document::factory()->create(['title' => 'Dokumen Tahun 2024', 'year' => 2024]);
        Document::factory()->create(['title' => 'Dokumen Tahun 2025', 'year' => 2025]);

        $response = $this->get(route('document.search', ['year' => 2024]));

        $response->assertOk();
        $response->assertSee('Dokumen Tahun 2024');
        $response->assertDontSee('Dokumen Tahun 2025');
    }

    public function test_autocomplete_mengembalikan_json(): void
    {
        Document::factory()->create(['title' => 'Skripsi Rekayasa Perangkat Lunak']);

        // 2 huruf -> jalur LIKE di suggest(), aman di SQLite
        $response = $this->get(route('document.suggest', ['q' => 'Sk']));

        $response->assertOk();
        $response->assertJsonIsArray();
        $response->assertJsonFragment(['title' => 'Skripsi Rekayasa Perangkat Lunak']);
    }

    public function test_autocomplete_kurang_dari_2_karakter_balikin_kosong(): void
    {
        Document::factory()->create(['title' => 'Skripsi Rekayasa Perangkat Lunak']);

        $response = $this->get(route('document.suggest', ['q' => 'S']));

        $response->assertOk();
        $response->assertExactJson([]);
    }

    // ── Test khusus FULLTEXT (butuh MySQL/MariaDB) ──────────────────────────

    public function test_pencarian_fulltext_menemukan_dokumen_dari_isi_pdf(): void
    {
        $this->skipIfNotMySql();

        Document::factory()->create([
            'title' => 'Dokumen Umum',
            'pdf_text' => 'Isi dokumen ini membahas tentang machine learning dan kecerdasan buatan.',
        ]);
        Document::factory()->create([
            'title' => 'Dokumen Lain',
            'pdf_text' => 'Isi dokumen ini membahas tentang jaringan komputer.',
        ]);

        $response = $this->get(route('document.search', ['q' => 'machine learning']));

        $response->assertOk();
        $response->assertSee('Dokumen Umum');
        $response->assertDontSee('Dokumen Lain');
    }

    public function test_pencarian_fulltext_kombinasi_dengan_filter_kategori(): void
    {
        $this->skipIfNotMySql();

        $kategori = Category::factory()->create();

        Document::factory()->create([
            'title' => 'Dokumen Cocok Kategori',
            'category_id' => $kategori->id,
            'pdf_text' => 'Pembahasan tentang basis data terdistribusi.',
        ]);
        Document::factory()->create([
            'title' => 'Dokumen Beda Kategori',
            'pdf_text' => 'Pembahasan tentang basis data terdistribusi.',
        ]);

        $response = $this->get(route('document.search', [
            'q' => 'basis data',
            'category' => $kategori->id,
        ]));

        $response->assertOk();
        $response->assertSee('Dokumen Cocok Kategori');
        $response->assertDontSee('Dokumen Beda Kategori');
    }

    private function skipIfNotMySql(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'])) {
            $this->markTestSkipped(
                'Pencarian FULLTEXT butuh MySQL/MariaDB. Koneksi test saat ini: '
                . DB::connection()->getDriverName()
                . '. Jalankan test ini dengan koneksi MySQL untuk memvalidasi fitur ini.'
            );
        }
    }
}
