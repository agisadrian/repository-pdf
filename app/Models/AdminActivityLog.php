<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    public $timestamps = false; // pakai kolom created_at sendiri, nggak butuh updated_at

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Cara singkat buat nyatet 1 aktivitas dari mana aja di aplikasi, contoh:
    //   AdminActivityLog::record('document.deleted', 'Menghapus dokumen "Panduan 2026"', $document);
    // $subject opsional -- kalau diisi, otomatis kesimpen tipe & id-nya (misal buat
    // dokumen yang sudah dihapus, subject_id-nya tetap ada di log walau dokumennya
    // sendiri sudah nggak ada lagi).
    public static function record(string $action, string $description, $subject = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject->id ?? null,
        ]);
    }
}
