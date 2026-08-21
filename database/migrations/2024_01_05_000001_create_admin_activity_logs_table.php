<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();

            // Siapa yang ngelakuin. nullOnDelete: kalau akun admin itu suatu saat
            // dihapus, catatan aktivitasnya TETAP ada (histori nggak boleh ilang),
            // cuma kolom user_id-nya jadi kosong.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Nama singkat aksi, contoh: 'document.created', 'document.deleted',
            // 'request.approved', 'category.updated', dst -- dipakai buat filter/icon
            $table->string('action', 60);

            // Kalimat siap-baca buat ditampilin di halaman log, contoh:
            // "Menghapus dokumen \"Panduan Akademik 2026\""
            $table->string('description');

            // Opsional: jenis & id data yang kena aksi (dokumen mana, user mana, dst),
            // buat referensi lebih lanjut kalau suatu saat dibutuhkan
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_logs');
    }
};
