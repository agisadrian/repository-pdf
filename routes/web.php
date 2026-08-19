<?php

use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/kategori', [CategoryController::class, 'index'])->name('category.index');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/cari/suggest', [DocumentController::class, 'suggest'])->name('document.suggest')->middleware('throttle:30,1');
Route::get('/cari', [DocumentController::class, 'search'])->name('document.search')->middleware('throttle:60,1');
Route::get('/dokumen/{slug}', [DocumentController::class, 'show'])->name('document.show');
Route::get('/dokumen/{slug}/download', [DocumentController::class, 'download'])->name('document.download');
Route::get('/dokumen/{slug}/preview', [DocumentController::class, 'preview'])->name('document.preview');

// Login, Daftar & Logout
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Halaman khusus admin
Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/become-super-admin', [DashboardController::class, 'becomeSuperAdmin'])->name('becomeSuperAdmin');

    Route::get('/statistik', [StatisticsController::class, 'index'])->name('statistics');

    // CRUD Dokumen: otomatis bikin route index, create, store, edit, update, destroy
    Route::resource('documents', AdminDocumentController::class)->except(['show']);

    // Generate ulang sampul otomatis dari PDF yang sudah ada (buat dokumen lama)
    Route::post('/documents/{document}/generate-cover', [AdminDocumentController::class, 'generateCover'])
        ->name('documents.generateCover');

    // Upload banyak PDF sekaligus
    Route::get('/documents-bulk', [AdminDocumentController::class, 'bulkCreate'])->name('documents.bulkCreate');
    Route::post('/documents-bulk', [AdminDocumentController::class, 'bulkStore'])->name('documents.bulkStore');

    // Update Kategori/Bulan/Tahun untuk banyak dokumen sekaligus (bulk edit dari Kelola Dokumen)
    Route::post('/documents-bulk-update', [AdminDocumentController::class, 'bulkUpdate'])->name('documents.bulkUpdate');

    // Hapus banyak dokumen sekaligus (bulk delete dari Kelola Dokumen)
    Route::post('/documents-bulk-delete', [AdminDocumentController::class, 'bulkDestroy'])->name('documents.bulkDestroy');

    // Halaman khusus Super Admin: Kelola Kategori & Kelola Pengguna
    Route::middleware('superadmin')->group(function () {
        Route::resource('categories', AdminCategoryController::class)->except(['create', 'show', 'edit']);

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');

        // Permintaan jadi Admin: user daftar sendiri lewat halaman /register lalu
        // centang "Ajukan jadi Admin", Super Admin yang setujui/tolak di sini
        Route::get('/requests', [AdminRequestController::class, 'index'])->name('requests.index');
        Route::post('/requests/{user}/approve', [AdminRequestController::class, 'approve'])->name('requests.approve');
        Route::post('/requests/{user}/reject', [AdminRequestController::class, 'reject'])->name('requests.reject');
    });
});
