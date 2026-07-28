<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Очищаем кэш каталога
\App\Services\CatalogCacheService::clearCatalogCache();
// Очищаем общий кэш
\Illuminate\Support\Facades\Cache::flush();

echo "DONE\n";
