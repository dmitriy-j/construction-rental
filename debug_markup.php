<?php
// Диагностика наценок - упрощённая версия без HTTP ядра
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Equipment;
use App\Models\PlatformMarkup;
use App\Services\PricingService;
use App\Services\MarkupCalculationService;

echo "=== ВСЕ НАЦЕНКИ С entity_type='order' ===\n";
$markups = PlatformMarkup::where('entity_type', 'order')->orderBy('priority', 'desc')->get();
foreach ($markups as $m) {
    $typeName = $m->markupable_type ? (new ReflectionClass($m->markupable_type))->getShortName() : 'GENERAL';
    echo "ID={$m->id} type={$m->type} value={$m->value} priority={$m->priority} {$typeName}:{$m->markupable_id} active={$m->is_active}\n";
}

$ids = [51, 52, 107, 108]; // ПАЗ 32053, ГАЗ 330232, Isuzu, ПАЗ 32054
foreach ($ids as $eqId) {
    echo "\n=== Оборудование ID={$eqId} ===\n";
    $eq = Equipment::with('rentalTerms')->find($eqId);
    if (!$eq) { echo "NOT FOUND\n"; continue; }
    $term = $eq->rentalTerms->first();
    if (!$term) { echo "NO TERMS\n"; continue; }
    $basePrice = (float)$term->price_per_hour;
    echo "{$eq->title} cat={$eq->category_id} company={$eq->company_id} base={$basePrice}\n";

    // PricingService
    $pricing = app(PricingService::class);
    $markup = $pricing->getPlatformMarkup($eq, null, 1);
    $amount = $pricing->applyMarkup($basePrice, $markup);
    echo "  PricingService: " . ($basePrice + $amount) . " (markup={$amount})\n";

    // MarkupCalculationService
    $calc = app(MarkupCalculationService::class);
    $r = $calc->calculateMarkup($basePrice, 'order', 1, $eqId, $eq->category_id, null, null);
    echo "  MarkupCalc: " . $r['final_price'] . " (val={$r['markup_value']}, src={$r['calculation_details']['source']})\n";
}

echo "\nDone.\n";
