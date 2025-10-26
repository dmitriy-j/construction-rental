<?php

namespace App\Console\Commands;

use App\Models\CompletionAct;
use App\Models\Waybill;
use Illuminate\Console\Command;

class CheckCompletionActs extends Command
{
    protected $signature = 'app:check-completion-acts {waybill_id}';

    protected $description = 'Проверка наличия акта для указанного путевого листа';

    public function handle()
    {
        $waybillId = $this->argument('waybill_id');

        $completionAct = CompletionAct::where('waybill_id', $waybillId)
            ->where('perspective', 'lessor')
            ->first();

        $this->info("Поиск акта для waybill_id: {$waybillId}");
        $this->info('Акт найден: '.($completionAct ? 'Да' : 'Нет'));

        if ($completionAct) {
            $this->info("ID акта: {$completionAct->id}");
            $this->info("Перспектива: {$completionAct->perspective}");
            $this->info("ID заказа: {$completionAct->order_id}");
        }

        // Проверка существования путевого листа
        $waybill = Waybill::find($waybillId);
        $this->info('Путевой лист найден: '.($waybill ? 'Да' : 'Нет'));

        if ($waybill) {
            $this->info("ID заказа путевого листа: {$waybill->order_id}");
            $this->info("ID УПД путевого листа: {$waybill->upd_id}");
        }
    }
}
