<?php

namespace App\Jobs;

use App\Models\Upd;
use App\Services\OneCIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExportUpdTo1C implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 60;

    protected $upd;

    public function __construct(Upd $upd)
    {
        $this->upd = $upd;
    }

    public function handle(OneCIntegrationService $oneCService)
    {
        $result = $oneCService->exportUpd($this->upd);

        if (! $result['success']) {
            Log::error('Не удалось экспортировать УПД в 1С', [
                'upd_id' => $this->upd->id,
                'error' => $result['error'],
            ]);

            throw new \Exception($result['error']);
        }

        Log::info('УПД успешно экспортирован в 1С', [
            'upd_id' => $this->upd->id,
            '1c_guid' => $this->upd->{'1c_guid'},
        ]);
    }

    public function failed(\Exception $exception)
    {
        Log::error('Job экспорта УПД в 1С завершился ошибкой', [
            'upd_id' => $this->upd->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
