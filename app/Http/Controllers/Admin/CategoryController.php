<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // Daftar semua kategori + jumlah dokumen di tiap kategori
    public function index()
    {
        $categories = Category::withCount('documents')
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', [
            'categories' => $categories,
        ]);
    }

    // Simpan kategori baru (dipanggil dari form di atas tabel, halaman yang sama)
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $data['slug'] = $this->generateUniqueSlug($data['name']);

        $category = Category::create($data);

        AdminActivityLog::record('category.created', "Menambahkan kategori \"{$category->name}\"", $category);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori "' . $data['name'] . '" berhasil ditambahkan.');
    }

    // Update nama kategori (dipanggil dari tombol "Simpan" di baris tabel yang lagi diedit)
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        // Slug diupdate cuma kalau namanya berubah, biar URL /kategori lama nggak berubah sia-sia
        if ($data['name'] !== $category->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $category->id);
        }

        $oldName = $category->name;
        $category->update($data);

        AdminActivityLog::record('category.updated', "Mengubah kategori \"{$oldName}\" menjadi \"{$category->name}\"", $category);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    // Hapus kategori. Dokumen yang masih pakai kategori ini TIDAK ikut kehapus,
    // cuma category_id-nya jadi null (lihat foreign key di migration: onDelete('set null'))
    public function destroy(Category $category)
    {
        $documentCount = $category->documents()->count();

        AdminActivityLog::record('category.deleted', "Menghapus kategori \"{$category->name}\"", $category);

        $category->delete();

        $message = 'Kategori "' . $category->name . '" berhasil dihapus.';
        if ($documentCount > 0) {
            $message .= ' ' . $documentCount . ' dokumen yang tadinya pakai kategori ini sekarang jadi "Tanpa Kategori".';
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', $message);
    }

    // Bikin slug unik dari nama (kalau sudah ada, tambah angka di belakang)
    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (
            Category::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
