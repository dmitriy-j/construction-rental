<?php

namespace App\Console\Commands;

use App\Models\CompletionAct;
use App\Models\Upd; // Импортируйте модель Waybill
use App\Models\Waybill; // Импортируйте модель CompletionAct
use Illuminate\Console\Command; // Импортируйте модель Upd

class CheckUpdRelations extends Command
{
    // Название и сигнатура команды
    protected $signature = 'upd:check-relations {waybill_id}';

    // Описание команды
    protected $description = 'Проверка связей для УПД';

    // Метод выполнения команды
    public function handle()
    {
        // Получаем аргумент waybill_id
        $waybillId = $this->argument('waybill_id');

        // Проверяем наличие путевого листа
        $waybill = Waybill::find($waybillId);
        $this->info('Путевой лист: '.($waybill ? "найден (ID: {$waybill->id})" : 'не найден'));

        // Проверяем наличие акта выполненных работ
        $completionAct = CompletionAct::where('waybill_id', $waybillId)
            ->where('perspective', 'lessor')
            ->first();

        $this->info('Акт выполненных работ: '.($completionAct ? "найден (ID: {$completionAct->id})" : 'не найден'));

        // Проверяем наличие УПД
        $upd = Upd::where('waybill_id', $waybillId)->first();
        $this->info('УПД: '.($upd ? "найден (ID: {$upd->id})" : 'не найден'));
    }
}
