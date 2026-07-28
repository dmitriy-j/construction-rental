<?php
// Очистка кэша каталога
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

\App\Services\CatalogCacheService::clearCatalogCache();
echo "Catalog cache cleared\n";

// Получаем все наценки для entity_type='order'
$markups = \App\Models\PlatformMarkup::where('entity_type', 'order')
    ->orderBy('priority', 'desc')
    ->get();
echo "\nMarkups for 'order':\n";
foreach ($markups as $m) {
    $type = $m->markupable_type ? (new \ReflectionClass($m->markupable_type))->getShortName() : 'null';
    echo "  id={$m->id} type={$m->type} value={$m->value} priority={$m->priority} apply_to={$type}:{$m->markupable_id}\n";
}
echo "Done\n";
