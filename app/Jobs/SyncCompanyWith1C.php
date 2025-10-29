<?php
// app/Jobs/SyncCompanyWith1C.php

namespace App\Jobs;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncCompanyWith1C implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Company $company) {}

    public function handle(): void
    {
        // Заглушка для интеграции с 1С
        logger()->info('Синхронизация компании с 1С', [
            'company_id' => $this->company->id,
            'company_name' => $this->company->legal_name,
            'timestamp' => now()
        ]);

        // Реализация API-интеграции с 1С будет здесь
        // $this->syncWith1C();
    }

    private function syncWith1C(): void
    {
        // Будущая реализация
    }
}
