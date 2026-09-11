<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Генерирует public/sitemap.xml';

    public function handle(): int
    {
        $base = rtrim(config('app.url'), '/');
        $now = Carbon::now()->toAtomString();

        // Статические страницы
        $urls = [
            ['loc' => $base . '/',             'lastmod' => $now, 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => $base . '/news',         'lastmod' => $now, 'priority' => '0.7', 'changefreq' => 'daily'],
            ['loc' => $base . '/about',        'lastmod' => $now, 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => $base . '/contacts',     'lastmod' => $now, 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => $base . '/cooperation',  'lastmod' => $now, 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => $base . '/jobs',         'lastmod' => $now, 'priority' => '0.4', 'changefreq' => 'weekly'],
            ['loc' => $base . '/repair',       'lastmod' => $now, 'priority' => '0.4', 'changefreq' => 'monthly'],
        ];

        // Динамика: новости
        $news = News::published()->orderByDesc('published_at')->get();
        foreach ($news as $item) {
            if (empty($item->slug)) {
                continue;
            }
            $lastmod = $item->published_at ?? $item->updated_at ?? $item->created_at;
            $urls[] = [
                'loc'        => $base . '/news/' . $item->slug,
                'lastmod'    => $lastmod ? Carbon::parse($lastmod)->toAtomString() : $now,
                'priority'   => '0.6',
                'changefreq' => 'monthly',
            ];
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            $xml .= '    <lastmod>' . htmlspecialchars($u['lastmod'], ENT_XML1) . "</lastmod>\n";
            $xml .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
            $xml .= '    <priority>' . $u['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>' . "\n";

        $path = public_path('sitemap.xml');
        file_put_contents($path, $xml);

        $this->info('Sitemap: ' . $path);
        $this->info('URLs: ' . count($urls) . ', size: ' . strlen($xml) . ' bytes');
        return 0;
    }
}
