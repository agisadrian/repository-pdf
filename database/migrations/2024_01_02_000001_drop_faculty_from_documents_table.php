<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus foreign key + kolom faculty_id dari tabel documents
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['faculty_id']);
            $table->dropColumn('faculty_id');
        });

        // Tabel faculties sudah tidak dipakai lagi
        Schema::dropIfExists('faculties');
    }

    public function down(): void
    {
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('faculty_id')->nullable()->after('category_id')->constrained('faculties')->nullOnDelete();
        });
    }
};
