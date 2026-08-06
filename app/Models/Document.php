<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'author',
        'publisher',
        'abstract',
        'keywords',
        'year',
        'cover',
        'pdf_file',
        'pdf_text',
        'total_pages',
        'download_count',
        'view_count',
        'category_id',
        'created_by',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scope untuk pencarian judul + full-text sederhana
    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        return $query->whereFullText(
            ['title', 'abstract', 'keywords', 'pdf_text'],
            $term
        );
    }
}
