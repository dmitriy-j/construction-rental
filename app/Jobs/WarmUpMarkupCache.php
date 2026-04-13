<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Services\MarkupCalculationService;

class WarmUpMarkupCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $cacheKey,
        public string $entityType
    ) {}

    public function handle(MarkupCalculationService $markupService): void
    {
        // Кэш автоматически перестроится при следующем запросе
        // Эта job просто гарантирует, что ключ существует
        Cache::remember($this->cacheKey, 3600, function () {
            return []; // Пустое значение, будет перезаписано при реальном запросе
        });
    }
}
