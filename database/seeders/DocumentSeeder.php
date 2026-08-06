<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $categories = Category::all();

        $dummyDocs = [
            'Analisis Sistem Informasi Perpustakaan Berbasis Web',
            'Perancangan Aplikasi Repository Dokumen Digital',
            'Implementasi Full-Text Search pada Sistem Pencarian Dokumen',
            'Studi Kasus Penerapan Laravel dalam Pengembangan Web',
            'Optimalisasi Pencarian Data menggunakan MySQL',
        ];

        foreach ($dummyDocs as $i => $title) {
            Document::updateOrCreate(
                ['slug' => str($title)->slug()],
                [
                    'title' => $title,
                    'author' => 'Penulis Contoh ' . ($i + 1),
                    'publisher' => 'Universitas Contoh',
                    'abstract' => 'Ini adalah abstrak contoh untuk dokumen "' . $title . '". Nanti bisa diganti dengan abstrak asli saat upload dokumen sungguhan.',
                    'keywords' => 'contoh, dummy, repository',
                    'year' => 2020 + $i,
                    'cover' => null,
                    // NOTE: ini masih nama file placeholder, belum ada file PDF fisiknya.
                    // Nanti di tahap "Upload PDF" baru kita ganti dengan file asli.
                    'pdf_file' => 'placeholder.pdf',
                    'pdf_text' => null,
                    'total_pages' => 0,
                    'category_id' => $categories->random()->id,
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
