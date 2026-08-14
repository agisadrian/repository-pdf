<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'author' => fake()->name(),
            'publisher' => fake()->company(),
            'abstract' => fake()->paragraph(),
            'keywords' => implode(', ', fake()->words(3)),
            'year' => fake()->numberBetween(2015, (int) date('Y')),
            'month' => fake()->numberBetween(1, 12),
            // File dummy: cukup untuk memenuhi kolom `pdf_file` yang not-null,
            // bukan file PDF asli yang bisa dibuka.
            'pdf_file' => 'uploads/pdf/' . Str::random(20) . '.pdf',
            'pdf_text' => fake()->paragraphs(3, true),
            'total_pages' => fake()->numberBetween(1, 50),
            'download_count' => 0,
            'view_count' => 0,
            'category_id' => null,
            'created_by' => null,
        ];
    }
}
