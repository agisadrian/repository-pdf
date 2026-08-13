<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    // Sitemap di-cache 1 jam biar nggak query ulang tiap kali Google (atau siapa aja) buka /sitemap.xml.
    // Otomatis "basi" sendiri tiap ada dokumen baru karena cache-nya expire, jadi nggak perlu di-generate manual.
    private const CACHE_MINUTES = 60;

    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', self::CACHE_MINUTES * 60, function () {
            return $this->buildXml();
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    private function buildXml(): string
    {
        $urls = [];

        // Halaman utama & halaman cari (paling sering berubah/paling penting)
        $urls[] = ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0'];
        $urls[] = ['loc' => route('category.index'), 'changefreq' => 'weekly', 'priority' => '0.8'];
        $urls[] = ['loc' => route('document.search'), 'changefreq' => 'daily', 'priority' => '0.8'];

        // Setiap dokumen: prioritas standar, diambil lastmod dari updated_at
        Document::query()
            ->select(['slug', 'updated_at'])
            ->orderByDesc('updated_at')
            ->get()
            ->each(function ($document) use (&$urls) {
                $urls[] = [
                    'loc' => route('document.show', $document->slug),
                    'lastmod' => $document->updated_at?->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                ];
            });

        // Halaman cari per-kategori (?category=id), biar kategori juga ke-index sendiri-sendiri
        Category::query()
            ->select(['id'])
            ->orderBy('name')
            ->get()
            ->each(function ($category) use (&$urls) {
                $urls[] = [
                    'loc' => route('document.search', ['category' => $category->id]),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                ];
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . e($url['loc']) . '</loc>' . "\n";

            if (! empty($url['lastmod'])) {
                $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
            }

            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }
}
