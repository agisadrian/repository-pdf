<?php

namespace Tests\Feature;

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_dokumen_bisa_didownload_dan_download_count_bertambah(): void
    {
        Storage::fake('public');

        $document = Document::factory()->create([
            'pdf_file' => 'uploads/pdf/contoh.pdf',
            'download_count' => 0,
        ]);

        Storage::disk('public')->put($document->pdf_file, 'isi pdf palsu untuk test');

        $response = $this->get(route('document.download', $document->slug));

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $this->assertEquals(1, $document->fresh()->download_count);
    }

    public function test_download_404_kalau_slug_tidak_ada(): void
    {
        $response = $this->get(route('document.download', 'slug-tidak-ada'));

        $response->assertNotFound();
    }

    public function test_download_404_kalau_file_hilang_dari_storage(): void
    {
        Storage::fake('public');

        // pdf_file tercatat di DB, tapi filenya sengaja tidak dibuat di storage
        $document = Document::factory()->create([
            'pdf_file' => 'uploads/pdf/hilang.pdf',
        ]);

        $response = $this->get(route('document.download', $document->slug));

        $response->assertNotFound();
        $this->assertEquals(0, $document->fresh()->download_count);
    }

    public function test_preview_menampilkan_pdf_inline_tanpa_menambah_download_count(): void
    {
        Storage::fake('public');

        $document = Document::factory()->create([
            'pdf_file' => 'uploads/pdf/contoh.pdf',
            'download_count' => 0,
        ]);

        Storage::disk('public')->put($document->pdf_file, 'isi pdf palsu untuk test');

        $response = $this->get(route('document.preview', $document->slug));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));

        // Preview beda dari download: tidak boleh nambah download_count
        $this->assertEquals(0, $document->fresh()->download_count);
    }

    public function test_preview_404_kalau_file_hilang_dari_storage(): void
    {
        Storage::fake('public');

        $document = Document::factory()->create([
            'pdf_file' => 'uploads/pdf/hilang.pdf',
        ]);

        $response = $this->get(route('document.preview', $document->slug));

        $response->assertNotFound();
    }

    public function test_halaman_detail_dokumen_menambah_view_count(): void
    {
        $document = Document::factory()->create(['view_count' => 0]);

        $response = $this->get(route('document.show', $document->slug));

        $response->assertOk();
        $response->assertSee($document->title);
        $this->assertEquals(1, $document->fresh()->view_count);
    }

    public function test_halaman_detail_404_kalau_slug_tidak_ada(): void
    {
        $response = $this->get(route('document.show', 'slug-tidak-ada'));

        $response->assertNotFound();
    }
}
