<?php
// app/Services/ProposalTemplateService.php

namespace App\Services;

use App\Models\ProposalTemplate;
use App\Models\RentalRequest;
use App\Models\Equipment;
use Illuminate\Support\Facades\Log;

class ProposalTemplateService
{
    public function applyTemplateToRequest(ProposalTemplate $template, RentalRequest $rentalRequest): array
    {
        try {
            // Генерация данных предложения на основе шаблона и заявки
            $proposalData = $template->generateProposalData($rentalRequest);

            // Обновление статистики использования
            $template->incrementUsage();

            // Логирование применения
            Log::info('Template applied to request', [
                'template_id' => $template->id,
                'request_id' => $rentalRequest->id,
                'user_id' => auth()->id()
            ]);

            return array_merge($proposalData, [
                'template_applied' => true,
                'template_id' => $template->id,
                'template_name' => $template->name
            ]);

        } catch (\Exception $e) {
            Log::error('Error applying template to request: ' . $e->getMessage());
            throw $e;
        }
    }

    public function generateQuickProposalData(ProposalTemplate $template): array
    {
        return [
            'proposed_price' => $template->proposed_price,
            'message' => $template->message,
            'response_time_hours' => $template->response_time,
            'additional_terms' => $template->additional_terms,
            'price_breakdown' => $template->price_breakdown,
            'template_id' => $template->id
        ];
    }

    public function calculateTemplateEffectiveness(ProposalTemplate $template): array
    {
        $totalUses = $template->usage_count;
        $successRate = $template->success_rate;

        return [
            'effectiveness_score' => $this->calculateEffectivenessScore($totalUses, $successRate),
            'recommendation' => $this->generateRecommendation($totalUses, $successRate),
            'performance_trend' => $this->analyzePerformanceTrend($template)
        ];
    }

    private function calculateEffectivenessScore(int $usageCount, float $successRate): float
    {
        if ($usageCount === 0) return 0;

        // Вес использования и успешности
        $usageWeight = min($usageCount / 10, 1); // Максимальный вес при 10+ использованиях
        $successWeight = $successRate / 100;

        return ($usageWeight * 0.3 + $successWeight * 0.7) * 100;
    }

    private function generateRecommendation(int $usageCount, float $successRate): string
    {
        if ($usageCount === 0) {
            return 'Шаблон еще не использовался. Попробуйте применить его к подходящей заявке.';
        }

        if ($successRate > 70) {
            return 'Высокая эффективность! Продолжайте использовать этот шаблон для похожих заявок.';
        }

        if ($successRate > 40) {
            return 'Средняя эффективность. Рассмотрите возможность оптимизации цены или условий.';
        }

        return 'Низкая эффективность. Рекомендуется пересмотреть параметры шаблона.';
    }

    private function analyzePerformanceTrend(ProposalTemplate $template): string
    {
        // Здесь можно добавить анализ тренда эффективности со временем
        return 'stable'; // stable, improving, declining
    }
}
