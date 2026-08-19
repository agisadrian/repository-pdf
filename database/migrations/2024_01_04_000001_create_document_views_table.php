<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45); // 45 = cukup buat IPv6 terpanjang
            $table->timestamp('viewed_at');

            // Kombinasi ini yang dicek tiap ada orang buka halaman dokumen:
            // udah pernah kecatat dalam 24 jam terakhir apa belum.
            $table->index(['document_id', 'ip_address', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_views');
    }
};
