<?php

namespace App\Jobs;

use App\Models\Upd;
use App\Services\BalanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessUpdAcceptance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $upd;

    public $tries = 3;

    public $timeout = 120;

    public function __construct(Upd $upd)
    {
        $this->upd = $upd;
    }

    public function handle(BalanceService $balanceService)
    {
        try {
            // Проверяем, что УПД еще не обработан
            if ($this->upd->status !== Upd::STATUS_PENDING) {
                Log::warning('УПД уже обработан', ['upd_id' => $this->upd->id]);

                return;
            }

            // Создаем проводку в балансе
            $balanceService->commitTransaction(
                $this->upd->lessorCompany,
                $this->upd->total_amount,
                'credit',
                'upd_accepted',
                $this->upd,
                "Принят УПД №{$this->upd->number} от {$this->upd->issue_date->format('d.m.Y')}",
                'upd_accept_'.$this->upd->id
            );

            // Обновляем статус УПД
            $this->upd->status = Upd::STATUS_ACCEPTED;
            $this->upd->accepted_at = now();
            $this->upd->save();

            Log::info('УПД успешно принят через очередь', ['upd_id' => $this->upd->id]);
        } catch (\Exception $e) {
            Log::error('Ошибка обработки УПД в очереди', [
                'upd_id' => $this->upd->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Exception $exception)
    {
        Log::error('Job обработки УПД завершился ошибкой', [
            'upd_id' => $this->upd->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
