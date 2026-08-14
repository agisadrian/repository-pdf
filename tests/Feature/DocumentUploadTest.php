<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_upload_dokumen_pdf(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.documents.store'), [
            'title' => 'Skripsi Sistem Informasi Perpustakaan',
            'author' => 'Budi Santoso',
            'category_id' => $category->id,
            'year' => 2026,
            'month' => 8,
            'pdf_file' => UploadedFile::fake()->create('dokumen.pdf', 500, 'application/pdf'),
        ]);

        $response->assertRedirect(route('admin.documents.index'));

        $this->assertDatabaseHas('documents', [
            'title' => 'Skripsi Sistem Informasi Perpustakaan',
            'category_id' => $category->id,
            'created_by' => $admin->id,
        ]);

        $document = Document::where('title', 'Skripsi Sistem Informasi Perpustakaan')->first();

        Storage::disk('public')->assertExists($document->pdf_file);
    }

    public function test_user_biasa_tidak_bisa_akses_halaman_upload(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('admin.documents.create'));

        $response->assertForbidden();
    }

    public function test_tamu_yang_belum_login_diarahkan_ke_halaman_login(): void
    {
        $response = $this->get(route('admin.documents.create'));

        $response->assertRedirect('/login');
    }

    public function test_upload_gagal_kalau_file_bukan_pdf(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.documents.store'), [
            'title' => 'Dokumen Salah Format',
            'pdf_file' => UploadedFile::fake()->create('dokumen.txt', 10, 'text/plain'),
        ]);

        $response->assertSessionHasErrors('pdf_file');
        $this->assertDatabaseMissing('documents', ['title' => 'Dokumen Salah Format']);
    }

    public function test_upload_gagal_kalau_file_pdf_lebih_dari_100mb(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        // 102400 KB = batas max di validasi (max:102400), jadi 102401 KB harus gagal
        $response = $this->actingAs($admin)->post(route('admin.documents.store'), [
            'title' => 'Dokumen Kebesaran',
            'pdf_file' => UploadedFile::fake()->create('dokumen.pdf', 102401, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('pdf_file');
    }

    public function test_upload_gagal_kalau_judul_kosong(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.documents.store'), [
            'title' => '',
            'pdf_file' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_upload_gagal_kalau_tidak_ada_file_pdf_sama_sekali(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.documents.store'), [
            'title' => 'Dokumen Tanpa File',
        ]);

        $response->assertSessionHasErrors('pdf_file');
    }
}
