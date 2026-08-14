<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('author')->nullable();
            $table->string('publisher')->nullable();
            $table->text('abstract')->nullable();
            $table->string('keywords')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('cover')->nullable();
            $table->string('pdf_file');
            $table->longText('pdf_text')->nullable();
            $table->unsignedInteger('total_pages')->default(0);
            $table->unsignedBigInteger('download_count')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);

            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });

        // index full-text buat pencarian judul, abstrak, keyword, & isi PDF.
        // Cuma didukung MySQL/MariaDB/PostgreSQL -- di-skip di SQLite (misal
        // waktu testing) supaya migration nggak gagal total gara-gara ini.
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb', 'pgsql'])) {
            Schema::table('documents', function (Blueprint $table) {
                $table->fullText(['title', 'abstract', 'keywords', 'pdf_text']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};