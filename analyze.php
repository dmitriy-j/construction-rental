<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$hostname = gethostname();
$output = [
    'hostname'      => $hostname,
    'environment'   => app()->environment(),
    'database'      => DB::connection()->getDatabaseName(),
    'generated_at'  => now()->toDateTimeString(),
];

// 1. Все таблицы и их структура
$tables = DB::select('SHOW TABLES');
$tableKey = 'Tables_in_' . $output['database'];
$allTables = array_column($tables, $tableKey);
$output['tables'] = $allTables;

$relevantTables = [
    'rental_conditions',
    'equipment_rental_terms',
    'equipment',
    'orders',
    'order_items',
    'waybills',
    'waybill_shifts',
    'completion_acts',
    'upds',
    'upd_items',
    'invoices',
    'invoice_items',
    'contracts',
    'operators',
    'categories',
    'locations',
    'users',
    'companies',
];

foreach ($relevantTables as $table) {
    if (Schema::hasTable($table)) {
        // Структура
        $columns = DB::select("DESCRIBE `$table`");
        $output['schema'][$table] = [];
        foreach ($columns as $col) {
            $output['schema'][$table][] = [
                'Field'   => $col->Field,
                'Type'    => $col->Type,
                'Null'    => $col->Null,
                'Key'     => $col->Key,
                'Default' => $col->Default,
                'Extra'   => $col->Extra,
            ];
        }
        // Внешние ключи (MySQL)
        $fkQuery = "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = '{$output['database']}'
                      AND TABLE_NAME = '{$table}'
                      AND REFERENCED_TABLE_NAME IS NOT NULL";
        $fks = DB::select($fkQuery);
        $output['foreign_keys'][$table] = $fks;
    }
}

// 2. Данные по арендатору (только локально)
if (app()->environment() !== 'production') {
    $lesseeEmail = 'zenit-zap@mail.ru';
    $lesseeUser = \App\Models\User::where('email', $lesseeEmail)->first();
    if ($lesseeUser) {
        $output['lessee'] = [
            'user_id'      => $lesseeUser->id,
            'user_email'   => $lesseeUser->email,
            'company_id'   => $lesseeUser->company_id,
            'company_name' => $lesseeUser->company->legal_name ?? null,
        ];
        $companyId = $lesseeUser->company_id;
        $orders = \App\Models\Order::where('lessee_company_id', $companyId)
            ->with(['items', 'lessorCompany', 'waybills'])
            ->get();
        $output['orders_summary'] = [
            'count'     => $orders->count(),
            'order_ids' => $orders->pluck('id')->toArray(),
        ];
        // Арендодатели
        $lessorIds = $orders->pluck('lessor_company_id')->unique()->filter();
        $lessorCompanies = \App\Models\Company::whereIn('id', $lessorIds)->get();
        $output['lessors'] = $lessorCompanies->map(function ($c) {
            return ['id' => $c->id, 'name' => $c->legal_name, 'inn' => $c->inn];
        })->toArray();
        // Подсчёт связанных записей
        $output['counts'] = [
            'equipment' => \App\Models\Equipment::whereIn('company_id', $lessorIds)->count(),
            'operators' => \App\Models\Operator::whereIn('company_id', $lessorIds)->count(),
            'waybills'  => $orders->pluck('waybills')->flatten()->count(),
        ];
    }
} else {
    // На сервере найдём арендатора ooo.sem@internet.ru
    $existingUser = \App\Models\User::where('email', 'ooo.sem@internet.ru')->first();
    if ($existingUser) {
        $output['target_lessee'] = [
            'user_id'    => $existingUser->id,
            'company_id' => $existingUser->company_id,
            'company_name' => $existingUser->company->legal_name ?? null,
        ];
    }
    // Платформа
    $platform = \App\Models\Company::where('is_platform', true)->first();
    if ($platform) {
        $output['platform'] = [
            'id'   => $platform->id,
            'name' => $platform->legal_name,
        ];
    }
}

// Сохраняем
$filename = storage_path('app/analyze_' . $hostname . '.json');
file_put_contents($filename, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Анализ завершён. Файл: {$filename}\n";
echo "Краткая сводка:\n";
echo "- Хост: {$hostname}\n";
echo "- Таблиц: " . count($allTables) . "\n";
if (isset($output['lessee'])) {
    echo "- Арендатор: {$output['lessee']['company_name']} (user {$output['lessee']['user_id']})\n";
    echo "- Заказов: {$output['orders_summary']['count']}\n";
}
if (isset($output['target_lessee'])) {
    echo "- Целевой арендатор: {$output['target_lessee']['company_name']} (company {$output['target_lessee']['company_id']})\n";
}
if (isset($output['platform'])) {
    echo "- Платформа: {$output['platform']['name']} (ID {$output['platform']['id']})\n";
}