<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Skripsi',
            'Tesis',
            'Disertasi',
            'Jurnal',
            'Laporan Penelitian',
        ];

        foreach ($categories as $name) {
            Category::updateOrCreate(
                ['slug' => str($name)->slug()],
                ['name' => $name]
            );
        }
    }
}
