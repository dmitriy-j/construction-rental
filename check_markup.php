<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$equip = \App\Models\Equipment::where('is_approved', true)->inRandomOrder()->first();
echo "Equipment: id={$equip->id}, company_id=" . ($equip->company_id ?? 'NULL') . "\n";

$companyMarkup = \App\Models\PlatformMarkup::where('markupable_type', 'App\Models\Company')->where('is_active', true)->get();
echo "Company markups: " . $companyMarkup->count() . "\n";
foreach ($companyMarkup as $m) echo "  - company_id={$m->markupable_id}, value={$m->value}, type={$m->type}\n";

$equipMarkup = \App\Models\PlatformMarkup::where('markupable_type', 'App\Models\Equipment')->where('is_active', true)->get();
echo "Equipment markups: " . $equipMarkup->count() . "\n";

$general = \App\Models\PlatformMarkup::whereNull('markupable_type')->where('is_active', true)->get();
echo "General markups: " . $general->count() . "\n";
foreach ($general as $m) echo "  - value={$m->value}, type={$m->type}\n";
