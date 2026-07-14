<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Order;
use App\Models\Equipment;
use App\Models\Operator;
use App\Models\Contract;
use App\Models\Category;
use App\Models\Location;
use App\Models\Waybill;
use App\Models\CompletionAct;
use App\Models\Upd;
use App\Models\Invoice;
use App\Models\TransactionEntry;
use Illuminate\Support\Facades\DB;

$targetCompanyId = 8; // ООО "СЭМ"

DB::transaction(function () use ($targetCompanyId) {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    // Удаляем заказы арендатора
    $orderIds = Order::where('lessee_company_id', $targetCompanyId)->pluck('id');
    Order::whereIn('id', $orderIds)->delete();

    // Удаляем путевые листы
    Waybill::whereIn('order_id', $orderIds)->delete();

    // Удаляем УПД
    Upd::whereIn('order_id', $orderIds)->delete();

    // Удаляем счета
    Invoice::where('company_id', $targetCompanyId)->delete();

    // Удаляем транзакции
    TransactionEntry::where('company_id', $targetCompanyId)->delete();

    // Удаляем договоры
    Contract::where('counterparty_company_id', $targetCompanyId)
        ->orWhere('company_id', $targetCompanyId)
        ->delete();

    // Находим арендодателей, созданных при импорте (по ИНН)
    $lessorInns = ['772078870977', '690891492744'];
    $lessorIds = Company::whereIn('inn', $lessorInns)->where('is_platform', 0)->pluck('id');

    // Удаляем операторов этих арендодателей
    Operator::whereIn('company_id', $lessorIds)->delete();

    // Удаляем оборудование этих арендодателей
    Equipment::whereIn('company_id', $lessorIds)->delete();

    // Теперь удаляем сами компании
    Company::whereIn('id', $lessorIds)->delete();

    // Удаляем созданные категории/локации (опционально)
    Category::where('name', 'Автобус')->whereNotIn('id', [1,2,3])->delete();
    Location::where('name', 'Паркинг')->whereNotIn('id', [1,2,3,4,5,6,7,8])->delete();

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "✅ Очистка завершена.\n";
});