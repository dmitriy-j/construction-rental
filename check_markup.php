<?php
// Проверка наценок для каталога
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\Equipment;
use App\Models\PlatformMarkup;
use App\Models\Company;

echo "=== Все наценки на оборудование ===\n";
$markups = PlatformMarkup::where('entity_type', 'order')->orderBy('priority', 'desc')->get();
foreach ($markups as $m) {
    $type = class_basename($m->markupable_type ?? 'null') . ':' . ($m->markupable_id ?? 'null');
    echo "ID={$m->id} priority={$m->priority} type={$m->type} value={$m->value} apply_to={$type} source={$m->getMarkupSource()}\n";
}

echo "\n=== Проверка для оборудования ID=51 (ПАЗ 32053) ===\n";
$eq = Equipment::with('rentalTerms')->find(51);
if (!$eq) { echo "Equipment 51 not found\n"; exit; }
$term = $eq->rentalTerms->first();
$basePrice = $term ? (float)$term->price_per_hour : 0;
echo "Equipment: {$eq->title} base_price={$basePrice}\n";

$calcService = app(\App\Services\MarkupCalculationService::class);
$result = $calcService->calculateMarkup($basePrice, 'order', 1, $eq->id, $eq->category_id, $eq->company_id, 2);
echo "Result: final_price={$result['final_price']} markup_type={$result['markup_type']} markup_value={$result['markup_value']} source={$result['calculation_details']['source']}\n";

echo "\nDone.\n";
