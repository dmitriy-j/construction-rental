<?php
// Исправленный скрипт
use App\Models\RentalRequestResponse;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "=== Проверка rejection_reason ===\n";
foreach ([6, 8, 9, 10, 11] as $id) {
    $p = RentalRequestResponse::find($id);
    if ($p) {
        echo "ID {$id}: rejection_reason=\"" . ($p->rejection_reason ?? 'NULL') . "\" status={$p->status}\n";
    } else {
        echo "ID {$id}: not found\n";
    }
}
echo "\nDone.\n";
