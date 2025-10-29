<?php

namespace App\Services;

use App\Models\ProposalTemplate;
use App\Models\RentalRequest;
use App\Models\Equipment;
use Illuminate\Support\Facades\DB;

class RecommendationEngine
{
    /**
     * Генерация рекомендаций для заявки
     */
    public function generateRecommendations(RentalRequest $request, $userId, $limit = 3)
    {
        $templates = ProposalTemplate::where('user_id', $userId)
            ->where('is_active', true)
            ->with(['category', 'abTestStats'])
            ->get();

        $recommendations = [];

        foreach ($templates as $template) {
            $score = $this->calculateTemplateScore($template, $request);

            if ($score >= 60) { // Только релевантные шаблоны
                $recommendations[] = [
                    'template' => $template,
                    'score' => $score,
                    'confidence' => $this->getConfidenceLevel($score),
                    'confidence_level' => $this->getConfidenceLevelClass($score),
                    'reason' => $this->getRecommendationReason($template, $request, $score)
                ];
            }
        }

        // Сортировка по убыванию релевантности
        usort($recommendations, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Расчет релевантности шаблона для заявки
     */
    private function calculateTemplateScore(ProposalTemplate $template, RentalRequest $request): float
    {
        $score = 0;

        // 1. Соответствие категории (+40%)
        if ($this->hasMatchingCategory($template, $request)) {
            $score += 40;
        }

        // 2. Историческая успешность (+30%)
        $score += ($template->success_rate / 100) * 30;

        // 3. Соответствие бюджету (+20%)
        $budgetMatch = $this->calculateBudgetMatch($template, $request);
        $score += $budgetMatch * 20;

        // 4. Скорость ответа (+10%)
        if ($template->response_time <= 4) {
            $score += 10;
        }

        // 5. Бонус за A/B тестирование (+5%)
        if ($template->is_ab_test) {
            $score += 5;
        }

        return min(100, round($score));
    }

    /**
     * Проверка соответствия категорий
     */
    private function hasMatchingCategory(ProposalTemplate $template, RentalRequest $request): bool
    {
        $requestCategoryIds = $request->items->pluck('category_id')->toArray();
        return in_array($template->category_id, $requestCategoryIds);
    }

    /**
     * Расчет соответствия бюджету
     */
    private function calculateBudgetMatch(ProposalTemplate $template, RentalRequest $request): float
    {
        $requestBudget = $this->calculateRequestBudget($request);
        $templatePrice = $template->proposed_price;

        if ($requestBudget <= 0) {
            return 0.5; // Нейтральный балл если бюджет не указан
        }

        $difference = abs($templatePrice - $requestBudget) / $requestBudget;
        return max(0, 1 - $difference);
    }

    /**
     * Расчет бюджета заявки
     */
    private function calculateRequestBudget(RentalRequest $request): float
    {
        if ($request->total_budget) {
            return $request->total_budget / $this->calculateRentalDays($request);
        }

        // Расчет на основе категорий оборудования
        $total = 0;
        foreach ($request->items as $item) {
            $avgPrice = $this->getCategoryAveragePrice($item->category_id);
            $total += $avgPrice * $item->quantity;
        }

        return $total > 0 ? $total / count($request->items) : 1000;
    }

    /**
     * Расчет дней аренды
     */
    private function calculateRentalDays(RentalRequest $request): int
    {
        $start = \Carbon\Carbon::parse($request->rental_period_start);
        $end = \Carbon\Carbon::parse($request->rental_period_end);
        return $start->diffInDays($end) + 1;
    }

    /**
     * Получение средней цены по категории
     */
    private function getCategoryAveragePrice($categoryId): float
    {
        $avgPrice = Equipment::where('category_id', $categoryId)
            ->where('rental_price_per_hour', '>', 0)
            ->avg('rental_price_per_hour');

        return $avgPrice ?: 1000;
    }

    /**
     * Уровень уверенности рекомендации
     */
    private function getConfidenceLevel($score): string
    {
        if ($score >= 90) return 'Очень высокая';
        if ($score >= 80) return 'Высокая';
        if ($score >= 70) return 'Средняя';
        return 'Низкая';
    }

    /**
     * CSS класс для уровня уверенности
     */
    private function getConfidenceLevelClass($score): string
    {
        if ($score >= 90) return 'high';
        if ($score >= 80) return 'medium';
        if ($score >= 70) return 'low';
        return 'very-low';
    }

    /**
     * Обоснование рекомендации
     */
    private function getRecommendationReason(ProposalTemplate $template, RentalRequest $request, $score): string
    {
        $reasons = [];

        if ($this->hasMatchingCategory($template, $request)) {
            $reasons[] = 'соответствует категории заявки';
        }

        if ($template->success_rate >= 70) {
            $reasons[] = 'высокая историческая конверсия';
        }

        if ($template->response_time <= 4) {
            $reasons[] = 'быстрый ответ';
        }

        if ($template->is_ab_test) {
            $reasons[] = 'активный A/B тест';
        }

        if ($template->usage_count > 10) {
            $reasons[] = 'популярный шаблон';
        }

        if (empty($reasons)) {
            return 'базовая рекомендация';
        }

        return 'рекомендуем потому что: ' . implode(', ', $reasons);
    }

    /**
     * Быстрые рекомендации для списка заявок
     */
    public function getQuickRecommendations($requests, $userId, $perRequest = 2)
    {
        $quickRecommendations = [];

        foreach ($requests as $request) {
            $recommendations = $this->generateRecommendations($request, $userId, $perRequest);

            foreach ($recommendations as $rec) {
                $quickRecommendations[] = [
                    'request_id' => $request->id,
                    'template' => $rec['template'],
                    'confidence' => $rec['confidence'],
                    'color' => $this->getConfidenceColor($rec['score']),
                    'reason' => $rec['reason']
                ];
            }
        }

        return $quickRecommendations;
    }

    /**
     * Цвет для уровня уверенности
     */
    private function getConfidenceColor($score): string
    {
        if ($score >= 90) return 'success';
        if ($score >= 80) return 'info';
        if ($score >= 70) return 'warning';
        return 'secondary';
    }

    /**
     * Сохранение фидбека для ML модели
     */
    public function saveFeedback($data): void
    {
        DB::table('recommendation_feedback')->insert([
            'user_id' => $data['user_id'],
            'template_id' => $data['template_id'],
            'request_id' => $data['request_id'],
            'applied' => $data['applied'],
            'converted' => $data['converted'] ?? false,
            'score' => $data['score'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
