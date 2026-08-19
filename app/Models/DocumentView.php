<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentView extends Model
{
    public $timestamps = false; // pakai kolom viewed_at sendiri, bukan created_at/updated_at bawaan

    protected $fillable = [
        'document_id',
        'ip_address',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
