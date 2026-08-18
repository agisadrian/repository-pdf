<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Diisi waktu user mengajukan diri jadi admin lewat halaman Daftar.
            // null = tidak ada pengajuan / sudah diproses (disetujui atau ditolak).
            $table->timestamp('admin_requested_at')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('admin_requested_at');
        });
    }
};
