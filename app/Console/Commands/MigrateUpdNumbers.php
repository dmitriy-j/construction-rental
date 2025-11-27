<?php

namespace App\Console\Commands;

use App\Models\Upd;
use App\Services\UpdProcessingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateUpdNumbers extends Command
{
    protected $signature = 'upd:migrate-numbers';
    protected $description = 'Миграция старых номеров УПД на новый формат';

    public function handle(UpdProcessingService $updService)
    {
        $this->info('Начало миграции номеров УПД...');

        $upds = Upd::whereNot('number', 'like', date('Y') . '-%')->get();

        $this->info('Найдено УПД для миграции: ' . $upds->count());

        $migrated = 0;
        $errors = 0;

        foreach ($upds as $upd) {
            try {
                DB::transaction(function () use ($upd, $updService, &$migrated) {
                    $oldNumber = $upd->number;
                    $newNumber = $updService->generateUpdNumber();

                    $upd->number = $newNumber;
                    $upd->save();

                    $this->line("Мигрирован УПД {$upd->id}: {$oldNumber} -> {$newNumber}");
                    $migrated++;
                });
            } catch (\Exception $e) {
                $this->error("Ошибка миграции УПД {$upd->id}: " . $e->getMessage());
                $errors++;
                Log::error('Ошибка миграции номера УПД', [
                    'upd_id' => $upd->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("Миграция завершена. Успешно: {$migrated}, Ошибок: {$errors}");
    }
}
