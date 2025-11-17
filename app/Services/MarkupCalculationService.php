<?php
// app/Services/MarkupCalculationService.php

namespace App\Services;

use App\Models\PlatformMarkup;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MarkupCalculationService
{
    /**
     * Универсальный метод расчета наценки
     */
    public function calculateMarkup(
        float $basePrice,
        string $entityType,
        int $workingHours = 1,
        ?int $equipmentId = null,
        ?int $categoryId = null,
        ?int $companyId = null,
        ?int $lesseeCompanyId = null
    ): array {
        $markup = $this->findApplicableMarkup(
            $entityType,
            $equipmentId,
            $categoryId,
            $companyId,
            $lesseeCompanyId
        );

        return $this->applyMarkup($basePrice, $markup, $workingHours, $entityType);
    }

    /**
     * Поиск применяемой наценки по приоритету
     */
     private function findApplicableMarkup(
        string $entityType,
        ?int $equipmentId,
        ?int $categoryId,
        ?int $companyId,
        ?int $lesseeCompanyId
    ): array {
        $cacheKey = $this->buildCacheKey($entityType, $equipmentId, $categoryId, $companyId, $lesseeCompanyId);

        return Cache::remember($cacheKey, 3600, function () use (
            $entityType, $equipmentId, $categoryId, $companyId, $lesseeCompanyId
        ) {
            // ИСПРАВЛЕНИЕ: Используем scope из модели вместо дублирования логики
            $markup = PlatformMarkup::forEntityType($entityType)
                ->active() // ← Используем scope из модели PlatformMarkup
                ->forContext($equipmentId, $categoryId, $companyId, $lesseeCompanyId)
                ->orderBy('priority', 'DESC')
                ->orderBy('created_at', 'DESC')
                ->first();

            if ($markup) {
                Log::debug("Found applicable markup", [
                    'entity_type' => $entityType,
                    'markup_id' => $markup->id,
                    'priority' => $markup->priority,
                    'source' => $markup->getMarkupSource()
                ]);

                return $this->formatMarkupResult($markup);
            }

            // Наценка по умолчанию
            Log::debug("Using default markup", ['entity_type' => $entityType]);
            return $this->getDefaultMarkup($entityType);
        });
    }

    /**
     * Форматирование результата наценки
     */
    private function formatMarkupResult(PlatformMarkup $markup): array
    {
        return [
            'type' => $markup->type,
            'value' => $markup->value,
            'calculation_type' => $markup->calculation_type,
            'rules' => $markup->rules ?? [],
            'source' => $markup->getMarkupSource(),
            'priority' => $markup->priority,
            'markup_id' => $markup->id
        ];
    }

    /**
     * Сброс кэша наценок при изменении
     */
    public function clearAffectedCache(PlatformMarkup $markup): void
    {
        // Определяем, какие ключи кэша затронуты этой наценкой
        $affectedKeys = $this->getAffectedCacheKeys($markup);

        foreach ($affectedKeys as $key) {
            Cache::forget($key);
            Log::debug("Cleared markup cache key: {$key}");
        }

        Log::info("Markup cache cleared for {$markup->id}", [
            'affected_keys' => count($affectedKeys),
            'markup_source' => $markup->getMarkupSource()
        ]);
    }

    /**
     * Получение всех затронутых ключей кэша
     */
    private function getAffectedCacheKeys(PlatformMarkup $markup): array
    {
        $keys = [];
        $entityType = $markup->entity_type;

        // В зависимости от типа сущности, определяем какие комбинации затронуты
        switch ($markup->markupable_type) {
            case Equipment::class:
                $equipment = $markup->markupable;
                if ($equipment) {
                    $keys[] = $this->buildCacheKey($entityType, $equipment->id, $equipment->category_id, null, null);
                    $keys[] = $this->buildCacheKey($entityType, $equipment->id, null, null, null);
                }
                break;

            case EquipmentCategory::class:
                $keys[] = $this->buildCacheKey($entityType, null, $markup->markupable_id, null, null);
                // Все оборудование этой категории
                $equipmentIds = Equipment::where('category_id', $markup->markupable_id)
                    ->pluck('id')
                    ->toArray();
                foreach ($equipmentIds as $equipmentId) {
                    $keys[] = $this->buildCacheKey($entityType, $equipmentId, $markup->markupable_id, null, null);
                }
                break;

            case Company::class:
                $keys[] = $this->buildCacheKey($entityType, null, null, null, $markup->markupable_id);
                break;

            default: // Общая наценка
                $keys[] = $this->buildCacheKey($entityType, null, null, null, null);
                break;
        }

        return array_unique($keys);
    }

    /**
     * Обновление кэша для конкретной наценки
     */
    public function warmUpCache(PlatformMarkup $markup): void
    {
        $affectedKeys = $this->getAffectedCacheKeys($markup);

        foreach ($affectedKeys as $key) {
            // Перестраиваем кэш асинхронно
            \App\Jobs\WarmUpMarkupCache::dispatch($key, $markup->entity_type);
        }
    }

    /**
     * Поиск наценки с наивысшим приоритетом
     */
    private function findHighestPriorityMarkup(
        string $entityType,
        ?int $equipmentId,
        ?int $categoryId,
        ?int $companyId,
        ?int $lesseeCompanyId
    ): ?PlatformMarkup {
        // Базовый запрос с учетом активности и временных рамок
        $query = PlatformMarkup::where('entity_type', $entityType)
            ->active() // Используем scope из модели вместо дублирования логики
            ->orderBy('priority', 'DESC')
            ->orderBy('created_at', 'DESC');

        // Создаем подзапросы для каждого уровня приоритета
        $markups = [];

        // 1. Наценка на конкретное оборудование (самый высокий приоритет)
        if ($equipmentId) {
            $equipmentMarkup = (clone $query)
                ->where('markupable_type', Equipment::class)
                ->where('markupable_id', $equipmentId)
                ->first();

            if ($equipmentMarkup) {
                return $equipmentMarkup;
            }
        }

        // 2. Наценка на категорию оборудования
        if ($categoryId) {
            $categoryMarkup = (clone $query)
                ->where('markupable_type', EquipmentCategory::class)
                ->where('markupable_id', $categoryId)
                ->first();

            if ($categoryMarkup) {
                return $categoryMarkup;
            }
        }

        // 3. Наценка на компанию арендатора
        if ($lesseeCompanyId) {
            $companyMarkup = (clone $query)
                ->where('markupable_type', Company::class)
                ->where('markupable_id', $lesseeCompanyId)
                ->first();

            if ($companyMarkup) {
                return $companyMarkup;
            }
        }

        // 4. Общая наценка платформы (самый низкий приоритет)
        return $query->whereNull('markupable_type')
            ->whereNull('markupable_id')
            ->first();
    }

    /**
     * Получение наценки по умолчанию
     */
    private function getDefaultMarkup(string $entityType): array
    {
        return [
            'type' => 'fixed',
            'value' => 100,
            'calculation_type' => 'addition',
            'rules' => [],
            'source' => 'default',
            'priority' => 0
        ];
    }

    /**
     * Сброс кэша наценок для конкретной сущности
     */
    public function clearMarkupCache(
        string $entityType,
        ?int $equipmentId = null,
        ?int $categoryId = null,
        ?int $companyId = null,
        ?int $lesseeCompanyId = null
    ): void {
        $cacheKey = $this->buildCacheKey($entityType, $equipmentId, $categoryId, $companyId, $lesseeCompanyId);
        Cache::forget($cacheKey);
        Log::info("Markup cache cleared for key: {$cacheKey}");
    }

    /**
     * Поиск наценки для конкретной сущности
     */
    private function findMarkup(string $markupableType, int $markupableId, string $entityType): ?array
    {
        $markup = PlatformMarkup::where('markupable_type', $markupableType)
            ->where('markupable_id', $markupableId)
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('valid_from')
                      ->orWhere('valid_from', '<=', now());
            })
            ->where(function($query) {
                $query->whereNull('valid_to')
                      ->orWhere('valid_to', '>=', now());
            })
            ->first();

        if (!$markup) {
            return null;
        }

        return [
            'type' => $markup->type,
            'value' => $markup->value,
            'calculation_type' => $markup->calculation_type,
            'rules' => $markup->rules ?? [],
            'source' => $markupableType.':'.$markupableId
        ];
    }

    /**
     * Получение общей наценки платформы
     */
    private function findGeneralMarkup(string $entityType): array
    {
        $markup = PlatformMarkup::whereNull('markupable_type')
            ->whereNull('markupable_id')
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->first();

        if ($markup) {
            return [
                'type' => $markup->type,
                'value' => $markup->value,
                'calculation_type' => $markup->calculation_type,
                'rules' => $markup->rules ?? [],
                'source' => 'platform_general'
            ];
        }

        // Наценка по умолчанию
        return [
            'type' => 'fixed',
            'value' => 100,
            'calculation_type' => 'addition',
            'rules' => [],
            'source' => 'default'
        ];
    }

    /**
     * Применение наценки к базовой цене
     */
    private function applyMarkup(float $basePrice, array $markup, int $workingHours, string $entityType): array
    {
        $originalValue = $markup['value'];
        $markupAmount = 0;
        $finalPrice = $basePrice;

        switch ($markup['type']) {
            case 'fixed':
                // Фиксированная наценка за час работы
                $markupAmount = $markup['value'] * $workingHours;
                $finalPrice = $basePrice + $markupAmount;
                break;

            case 'percent':
                // Процентная наценка от базовой цены
                $markupAmount = $basePrice * ($markup['value'] / 100);
                $finalPrice = $basePrice + $markupAmount;
                break;

            case 'tiered':
                // Ступенчатая наценка в зависимости от объема/срока
                $markupAmount = $this->calculateTieredMarkup($basePrice, $markup, $workingHours);
                $finalPrice = $basePrice + $markupAmount;
                break;

            case 'combined':
                // Комбинированная наценка (фиксированная + процентная)
                $fixedPart = $markup['rules']['fixed_value'] ?? 0;
                $percentPart = $basePrice * (($markup['rules']['percent_value'] ?? 0) / 100);
                $markupAmount = ($fixedPart * $workingHours) + $percentPart;
                $finalPrice = $basePrice + $markupAmount;
                break;

            case 'seasonal':
                // Сезонная наценка с коэффициентом
                $seasonCoefficient = $this->getSeasonalCoefficient($markup);
                $baseMarkup = $basePrice * ($markup['value'] / 100);
                $markupAmount = $baseMarkup * $seasonCoefficient;
                $finalPrice = $basePrice + $markupAmount;
                break;
        }

        Log::debug("Markup applied", [
            'base_price' => $basePrice,
            'markup_type' => $markup['type'],
            'markup_value' => $originalValue,
            'markup_amount' => $markupAmount,
            'final_price' => $finalPrice,
            'working_hours' => $workingHours,
            'entity_type' => $entityType,
            'source' => $markup['source']
        ]);

        return [
            'base_price' => $basePrice,
            'markup_type' => $markup['type'],
            'markup_value' => $originalValue,
            'markup_amount' => $markupAmount,
            'final_price' => $finalPrice,
            'working_hours' => $workingHours,
            'calculation_details' => [
                'source' => $markup['source'],
                'rules' => $markup['rules'],
                'calculation_type' => $markup['calculation_type']
            ]
        ];
    }

    /**
     * Расчет ступенчатой наценки
     */
    private function calculateTieredMarkup(float $basePrice, array $markup, int $workingHours): float
    {
        $rules = $markup['rules'];
        $tiers = $rules['tiers'] ?? [];

        foreach ($tiers as $tier) {
            $min = $tier['min'] ?? 0;
            $max = $tier['max'] ?? PHP_INT_MAX;

            if ($workingHours >= $min && $workingHours <= $max) {
                if ($tier['type'] === 'fixed') {
                    return $tier['value'] * $workingHours;
                } else {
                    return $basePrice * ($tier['value'] / 100);
                }
            }
        }

        return $markup['value'] * $workingHours; // fallback
    }

    /**
     * Получение сезонного коэффициента
     */
    private function getSeasonalCoefficient(array $markup): float
    {
        $month = (int) date('n');
        $rules = $markup['rules'];

        // Высокий сезон (май-сентябрь)
        if ($month >= 5 && $month <= 9) {
            return $rules['high_season_coefficient'] ?? 1.5;
        }

        // Низкий сезон (ноябрь-февраль)
        if ($month >= 11 || $month <= 2) {
            return $rules['low_season_coefficient'] ?? 0.7;
        }

        // Средний сезон
        return $rules['medium_season_coefficient'] ?? 1.0;
    }

    /**
     * Обратное применение наценки (из цены с наценкой получаем базовую цену)
     */
    public function reverseApplyMarkup(float $priceWithMarkup, array $markup, int $workingHours): float
    {
        switch ($markup['type']) {
            case 'fixed':
                return max(0, $priceWithMarkup - ($markup['value'] * $workingHours));

            case 'percent':
                return $priceWithMarkup / (1 + ($markup['value'] / 100));

            case 'combined':
                $fixedPart = $markup['rules']['fixed_value'] ?? 0;
                $percentValue = $markup['rules']['percent_value'] ?? 0;
                return ($priceWithMarkup - ($fixedPart * $workingHours)) / (1 + ($percentValue / 100));

            default:
                return $priceWithMarkup;
        }
    }

    /**
     * Построение ключа кэша
     */
    private function buildCacheKey(
        string $entityType,
        ?int $equipmentId,
        ?int $categoryId,
        ?int $companyId,
        ?int $lesseeCompanyId
    ): string {
        return sprintf(
            "markup_%s_%s_%s_%s_%s",
            $entityType,
            $equipmentId ?? 'null',
            $categoryId ?? 'null',
            $companyId ?? 'null',
            $lesseeCompanyId ?? 'null'
        );
    }

    /**
     * Сброс кэша наценок
     */
    public function clearCache(): void
    {
        Cache::flush();
        Log::info("Markup cache cleared");
    }
}
