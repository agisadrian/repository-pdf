# Repository PDF

Aplikasi web repositori dokumen PDF (skripsi, jurnal, laporan, dsb) berbasis **Laravel 12**. Pengunjung bisa mencari dan membaca/mengunduh dokumen tanpa login; admin punya panel khusus untuk mengelola dokumen (termasuk upload massal).

## Daftar Isi

- [Fitur](#fitur)
- [Tech Stack](#tech-stack)
- [Requirement Server](#requirement-server)
- [Instalasi (Lokal)](#instalasi-lokal)
- [Konfigurasi `.env`](#konfigurasi-env)
- [Membuat Akun Admin](#membuat-akun-admin)
- [Struktur Data (Model)](#struktur-data-model)
- [Rute Utama](#rute-utama)
- [Cara Kerja Pencarian](#cara-kerja-pencarian)
- [Testing](#testing)
- [Deploy ke Shared Hosting](#deploy-ke-shared-hosting)
- [Struktur Folder](#struktur-folder)
- [Catatan & Batasan yang Diketahui](#catatan--batasan-yang-diketahui)

## Fitur

**Untuk pengunjung (publik, tanpa login):**
- Beranda menampilkan 8 dokumen terbaru + statistik total dokumen, total dilihat, dan total diunduh.
- Halaman `/kategori` — daftar semua kategori dengan jumlah dokumen dan cover representatif.
- Pencarian dokumen (`/cari`) berdasarkan judul, abstrak, kata kunci, **dan isi teks PDF**, dengan filter kategori/tahun/bulan dan pilihan urutan (terbaru, judul, terpopuler).
- Autocomplete pencarian (`/cari/suggest`).
- Halaman detail dokumen dengan dokumen terkait (kategori yang sama) dan cuplikan hasil pencarian yang di-highlight (mirip snippet Google).
- Baca PDF langsung di browser (preview inline) atau download.
- Sitemap XML otomatis (`/sitemap.xml`), di-cache 1 jam.

**Untuk admin (login diperlukan, prefix `/admin`):**
- Dashboard admin.
- CRUD dokumen lengkap: tambah, edit, hapus, dengan upload file PDF + cover (manual atau auto-generate dari halaman pertama PDF).
- Ekstraksi teks PDF otomatis saat upload (untuk full-text search) menggunakan `smalot/pdfparser`.
- Upload banyak PDF sekaligus (bulk upload) — judul otomatis diambil dari nama file.
- Bulk edit: ubah kategori/bulan untuk banyak dokumen sekaligus.
- Bulk delete: hapus banyak dokumen sekaligus (otomatis hapus file PDF & cover dari storage).
- Generate ulang cover otomatis dari PDF yang sudah ada (untuk dokumen lama).

**Keamanan:**
- Rate limiting login (maksimal 5 percobaan gagal per kombinasi email + IP, lockout progresif).
- Middleware khusus admin (`EnsureUserIsAdmin`) — mengecek login *dan* role.
- Validasi upload ketat: tipe file (`mimes:pdf` / `image`), ukuran maksimal (100MB untuk PDF, 5MB untuk cover).
- `.htaccess` tambahan di root project untuk hosting yang men-serve seluruh folder aplikasi langsung (bukan hanya folder `public/`), seperti InfinityFree — memblokir akses ke `.env`, `.sqlite`, `app/`, `config/`, dll dari browser.

## Tech Stack

| Komponen | Teknologi |
|---|---|
| Framework | Laravel 12 (PHP 8.2+) |
| Database | MySQL / MariaDB (**wajib** untuk fitur pencarian full-text — lihat [Catatan & Batasan](#catatan--batasan-yang-diketahui)) |
| Ekstraksi teks PDF | [smalot/pdfparser](https://github.com/smalot/pdfparser) |
| Testing | PHPUnit 11 (via `php artisan test`) |
| Frontend | Blade + asset di-build lewat Vite (`npm run dev` / `npm run build`) |

## Requirement Server

- PHP >= 8.2 dengan ekstensi standar Laravel (`pdo`, `mbstring`, `fileinfo`, dll).
- **MySQL atau MariaDB** — pencarian dokumen memakai index `FULLTEXT` (`MATCH ... AGAINST`) yang **tidak didukung SQLite**. Jangan pakai SQLite untuk environment production/staging.
- Composer 2.x
- Node.js + npm (hanya untuk build asset frontend, tidak wajib di server production kalau asset sudah di-build sebelum deploy)
- Ekstensi PHP `zip`/`gd` atau setara untuk pemrosesan gambar cover (mengikuti kebutuhan `smalot/pdfparser` & manipulasi gambar bawaan Laravel).

## Instalasi (Lokal)

```bash
git clone <url-repo-ini>
cd repository-pdf

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Edit `.env`, minimal set koneksi database ke MySQL (lihat [Konfigurasi `.env`](#konfigurasi-env) di bawah), lalu:

```bash
php artisan migrate

# opsional: buat folder storage/uploads bisa diakses publik
php artisan storage:link

npm run build
php artisan serve
```

Aplikasi bisa diakses di `http://localhost:8000`.

Untuk development dengan hot-reload asset + queue worker + log viewer sekaligus, project ini sudah menyediakan script gabungan:

```bash
composer run dev
```

## Konfigurasi `.env`

Variabel penting yang perlu disesuaikan dari `.env.example`:

```env
APP_NAME="Repository PDF"
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=repository_pdf
DB_USERNAME=root
DB_PASSWORD=
```

> **Penting:** `.env.example` bawaan menyebut `DB_CONNECTION=sqlite` sebagai contoh default Laravel — **abaikan/ganti ini ke `mysql`**. Fitur pencarian (full-text) hanya berfungsi di MySQL/MariaDB/PostgreSQL. Kalau tetap pakai SQLite, halaman pencarian akan error saat menerima keyword 3 karakter atau lebih.

Variabel tambahan yang dipakai khusus di project ini (lihat `config/filesystems.php`):

```env
# Opsional — override lokasi & URL folder upload publik.
# Berguna kalau di-deploy ke shared hosting dengan struktur folder berbeda dari default.
PUBLIC_UPLOADS_DIR=
PUBLIC_UPLOADS_URL=
```

Kalau dikosongkan, upload disimpan di `storage/app/public` seperti Laravel pada umumnya, diakses lewat `/storage/...` (butuh `php artisan storage:link`).

## Membuat Akun Admin

Belum ada halaman registrasi admin — akun harus dibuat manual. Cara paling gampang lewat Tinker:

```bash
php artisan tinker
```

```php
App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('password-yang-aman'),
    'role' => 'admin',
]);
```

Login lewat `/login`, lalu akses panel admin di `/admin/dashboard`.

## Struktur Data (Model)

**`Document`**
| Kolom | Keterangan |
|---|---|
| `title`, `slug` | Judul & slug unik (dipakai di URL `/dokumen/{slug}`) |
| `author`, `publisher` | Opsional |
| `abstract` | Opsional — kalau kosong, otomatis diambil 2–3 kalimat pertama dari `pdf_text` sebagai ringkasan (ditandai "Ringkasan otomatis") |
| `keywords` | Opsional, dipisah spasi/koma |
| `year`, `month` | Opsional, dipakai untuk filter & pengelompokan periode terbit |
| `cover` | Path gambar sampul (upload manual atau auto-generate dari halaman pertama PDF) |
| `pdf_file` | Path file PDF asli di storage |
| `pdf_text` | Hasil ekstraksi teks PDF (diisi otomatis saat upload), dipakai untuk full-text search |
| `total_pages` | Diisi otomatis dari hasil parsing PDF |
| `download_count`, `view_count` | Counter, bertambah otomatis |
| `category_id`, `created_by` | Relasi ke `Category` dan `User` |

**`Category`** — `name`, `slug`, punya banyak `Document`.

**`User`** — tambahan kolom `role` (`admin` / `user`) dari kolom bawaan Laravel. Method `isAdmin()` dipakai middleware `admin`.

## Rute Utama

| Method | URL | Keterangan |
|---|---|---|
| GET | `/` | Beranda |
| GET | `/kategori` | Daftar kategori |
| GET | `/cari` | Pencarian dokumen |
| GET | `/cari/suggest` | Autocomplete (JSON) |
| GET | `/dokumen/{slug}` | Detail dokumen |
| GET | `/dokumen/{slug}/preview` | Baca PDF inline di browser |
| GET | `/dokumen/{slug}/download` | Download PDF |
| GET / POST | `/login`, POST `/logout` | Auth |
| GET | `/admin/dashboard` | Dashboard admin *(butuh login + role admin)* |
| Resource | `/admin/documents` | CRUD dokumen (index, create, store, edit, update, destroy) |
| GET / POST | `/admin/documents-bulk` | Upload banyak dokumen sekaligus |
| POST | `/admin/documents-bulk-update` | Bulk edit kategori/bulan |
| POST | `/admin/documents-bulk-delete` | Bulk hapus dokumen |
| POST | `/admin/documents/{document}/generate-cover` | Generate ulang cover dari PDF |

Daftar lengkap & terkini selalu bisa dicek lewat:

```bash
php artisan route:list
```

## Cara Kerja Pencarian

Pencarian memakai MySQL **FULLTEXT boolean mode** (`MATCH ... AGAINST`) di kolom `title`, `abstract`, `keywords`, dan `pdf_text` sekaligus — jadi isi PDF ikut kecari, bukan cuma metadata.

- Keyword **3 karakter atau lebih** → pakai FULLTEXT (index-based, cepat walau data banyak). Tiap kata di-boolean-query-kan dengan wildcard (`+kata*`), jadi cocok juga walau kata belum selesai diketik.
- Keyword **kurang dari 3 karakter** → fallback ke `LIKE` biasa (FULLTEXT MySQL/InnoDB butuh minimal 3 karakter per kata: `innodb_ft_min_token_size`).
- Hasil pencarian menampilkan cuplikan (snippet) teks yang memuat kata kunci ter-highlight, diambil dari `pdf_text` → `abstract` → `keywords` (prioritas berurutan), mirip snippet hasil Google Search.

## Testing

Jalankan seluruh test:

```bash
php artisan test
```

Test yang tersedia (di `tests/Feature/`):
- `DocumentUploadTest` — upload dokumen oleh admin, validasi file, otorisasi.
- `DocumentSearchTest` — pencarian & autocomplete.
- `DocumentDownloadTest` — halaman detail, download, dan preview PDF.

> **Catatan penting:** setup testing default (`phpunit.xml`) memakai **SQLite in-memory** demi kecepatan. Karena SQLite tidak mendukung FULLTEXT, migration `documents` otomatis melewati pembuatan index full-text kalau koneksinya bukan MySQL/MariaDB/PostgreSQL — dan 2 test yang spesifik menguji pencarian full-text (`test_pencarian_fulltext_...`) otomatis **di-skip** kalau dijalankan bukan di MySQL.
>
> Untuk benar-benar memvalidasi fitur pencarian full-text, jalankan test dengan koneksi MySQL, misalnya lewat file `.env.testing` terpisah:
> ```env
> DB_CONNECTION=mysql
> DB_DATABASE=repository_pdf_testing
> ```
> lalu jalankan `php artisan test` seperti biasa (Laravel otomatis memakai `.env.testing` kalau ada, `APP_ENV=testing`).

## Deploy ke Shared Hosting

Project ini sudah menyertakan `.htaccess` khusus di **root folder** (bukan hanya di `public/`) untuk hosting yang tidak bisa diarahkan document root-nya ke folder `public/` (contoh: InfinityFree). File ini:
- Memblokir akses langsung ke file yang diawali titik (`.env`, dll) dan file sensitif (`.sql`, `.lock`, `.log`, dll) dari browser.
- Memblokir akses langsung ke folder `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `storage/`, `tests/`, `vendor/`.

Kalau hosting kamu **bisa** diarahkan document root ke folder `public/` (VPS, cPanel dengan subdomain terpisah, dll), pengaturan ini tetap aman dipakai sebagai lapisan proteksi tambahan, tidak mengganggu.

Langkah umum deploy:
1. Upload seluruh project (atau `git pull` di server).
2. `composer install --no-dev --optimize-autoloader`
3. Set `.env` production (`APP_ENV=production`, `APP_DEBUG=false`, koneksi MySQL production).
4. `php artisan key:generate` (kalau belum ada `APP_KEY`)
5. `php artisan migrate --force`
6. `php artisan storage:link` (atau set `PUBLIC_UPLOADS_DIR`/`PUBLIC_UPLOADS_URL` kalau strukturnya beda)
7. `npm run build`
8. `php artisan config:cache && php artisan route:cache && php artisan view:cache`

## Struktur Folder

```
app/
├── Http/Controllers/
│   ├── Admin/              # DashboardController, DocumentController (CRUD + bulk)
│   ├── AuthController.php
│   ├── CategoryController.php
│   ├── DocumentController.php   # show, search, suggest, download, preview
│   ├── HomeController.php
│   └── SitemapController.php
├── Http/Middleware/EnsureUserIsAdmin.php
├── Models/                 # Document, Category, User
└── Providers/

database/
├── factories/               # CategoryFactory, DocumentFactory, UserFactory
├── migrations/
└── seeders/

resources/views/
├── admin/                   # dashboard, documents (index/create/edit/bulk-create)
├── auth/                    # login
├── category/, document/     # halaman publik
├── components/, layouts/
└── errors/

tests/Feature/                # DocumentUploadTest, DocumentSearchTest, DocumentDownloadTest, ExampleTest
```

## Catatan & Batasan yang Diketahui

- **Wajib MySQL/MariaDB/PostgreSQL untuk pencarian.** Jangan deploy production dengan `DB_CONNECTION=sqlite` — fitur pencarian akan gagal total.
- **Belum ada halaman registrasi/reset password admin** — akun admin harus dibuat manual lewat `php artisan tinker` atau seeder.
- **Belum ada test untuk `Admin\DocumentController`** (CRUD, bulk upload/edit/delete) — test yang ada baru mencakup sisi publik (upload dasar, search, download/preview). Kontribusi test tambahan untuk fitur admin sangat disarankan.
- **Ekstraksi teks PDF bisa gagal diam-diam** untuk PDF yang rusak, terenkripsi, atau hasil scan tanpa OCR (`smalot/pdfparser` tidak melakukan OCR) — dokumen tetap tersimpan, tapi `pdf_text` kosong dan tidak akan muncul di pencarian isi PDF.
