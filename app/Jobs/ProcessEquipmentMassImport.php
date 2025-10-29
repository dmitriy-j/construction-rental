<?php

namespace App\Jobs;

use App\Services\EquipmentMassImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEquipmentMassImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $filePath,
        private int $companyId,
        private int $importId
    ) {}

    public function handle(EquipmentMassImportService $importService)
    {
        $importService->processImport($this->filePath, $this->companyId);
    }
}
