<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\ProposalTemplate;
use App\Models\RentalRequestResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LessorRecommendationController extends Controller
{
    public function getRecommendations($rentalRequestId)
    {
        try {
            $user = auth()->user();
            $rentalRequest = RentalRequest::with(['items.category', 'location'])->findOrFail($rentalRequestId);

            // Получаем активные шаблоны пользователя
            $templates = ProposalTemplate::where('user_id', $user->id)
                ->where('is_active', true)
                ->with('category')
                ->get();

            $recommendations = [];

            foreach ($templates as $template) {
                $score = $this->calculateTemplateScore($template, $rentalRequest);

                if ($score >= 50) { // Только шаблоны с достаточной релевантностью
                    $recommendations[] = [
                        'template' => $template,
                        'score' => $score,
                        'confidence_level' => $this->getConfidenceLevel($score),
                        'confidence' => $this->getConfidenceText($score),
                        'reason' => $this->getRecommendationReason($template, $rentalRequest, $score)
                    ];
                }
            }

            // Сортировка по убыванию релевантности и берем топ-3
            usort($recommendations, function($a, $b) {
                return $b['score'] - $a['score'];
            });

            return response()->json([
                'success' => true,
                'recommendations' => array_slice($recommendations, 0, 3)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting recommendations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения рекомендаций',
                'error' => $e->getMessage(),
                'recommendations' => []
            ], 500);
        }
    }

    public function getStats()
    {
        try {
            $user = auth()->user();

            // Базовая статистика рекомендаций
            $stats = [
                'total_recommendations' => 0,
                'applied_recommendations' => 0,
                'success_rate' => 0,
                'top_performing_templates' => []
            ];

            // Пытаемся получить реальную статистику из recommendation_feedback
            try {
                $feedbackStats = DB::table('recommendation_feedback')
                    ->where('user_id', $user->id)
                    ->select(
                        DB::raw('COUNT(*) as total_recommendations'),
                        DB::raw('SUM(CASE WHEN applied = true THEN 1 ELSE 0 END) as applied_count'),
                        DB::raw('SUM(CASE WHEN converted = true THEN 1 ELSE 0 END) as converted_count'),
                        DB::raw('AVG(score) as average_score')
                    )
                    ->first();

                if ($feedbackStats) {
                    $stats = [
                        'total_recommendations' => $feedbackStats->total_recommendations ?? 0,
                        'application_rate' => $feedbackStats->total_recommendations > 0
                            ? round(($feedbackStats->applied_count / $feedbackStats->total_recommendations) * 100, 1)
                            : 0,
                        'conversion_rate' => $feedbackStats->applied_count > 0
                            ? round(($feedbackStats->converted_count / $feedbackStats->applied_count) * 100, 1)
                            : 0,
                        'average_score' => round($feedbackStats->average_score ?? 0, 1)
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Recommendation feedback table might not exist: ' . $e->getMessage());
                // Используем базовую статистику если таблицы нет
            }

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting recommendation stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения статистики рекомендаций',
                'stats' => [
                    'total_recommendations' => 0,
                    'application_rate' => 0,
                    'conversion_rate' => 0,
                    'average_score' => 0
                ]
            ], 500);
        }
    }

    public function saveFeedback(Request $request)
    {
        try {
            $validated = $request->validate([
                'template_id' => 'required|exists:proposal_templates,id',
                'request_id' => 'required|exists:rental_requests,id',
                'applied' => 'required|boolean',
                'converted' => 'sometimes|boolean',
                'score' => 'required|numeric|min:0|max:100'
            ]);

            // Сохраняем фидбек в таблицу recommendation_feedback
            DB::table('recommendation_feedback')->insert([
                'user_id' => auth()->id(),
                'template_id' => $validated['template_id'],
                'request_id' => $validated['request_id'],
                'applied' => $validated['applied'],
                'converted' => $validated['converted'] ?? false,
                'score' => $validated['score'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Фидбек сохранен'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error saving recommendation feedback: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка сохранения фидбека'
            ], 500);
        }
    }

    private function calculateTemplateScore($template, $rentalRequest)
    {
        $score = 0;

        // 1. Проверка соответствия категории (40%)
        $categoryMatch = $this->checkCategoryMatch($template, $rentalRequest);
        $score += $categoryMatch * 40;

        // 2. Историческая успешность шаблона (30%)
        $successRate = $template->success_rate ?? 0;
        $score += ($successRate / 100) * 30;

        // 3. Соответствие бюджету (20%)
        $budgetMatch = $this->checkBudgetMatch($template, $rentalRequest);
        $score += $budgetMatch * 20;

        // 4. Скорость ответа (10%)
        $responseTime = $template->response_time ?? 24;
        $responseTimeScore = $responseTime <= 4 ? 10 : ($responseTime <= 12 ? 7 : 3);
        $score += $responseTimeScore;

        return min(100, $score);
    }

    private function checkCategoryMatch($template, $rentalRequest)
    {
        // Проверяем, есть ли в заявке позиции с категорией шаблона
        if (!$rentalRequest->items) {
            return 0.0;
        }

        foreach ($rentalRequest->items as $item) {
            if ($item->category_id == $template->category_id) {
                return 1.0;
            }
        }
        return 0.0;
    }

    private function checkBudgetMatch($template, $rentalRequest)
    {
        // Упрощенная проверка соответствия бюджета
        $totalItems = $rentalRequest->items->sum('quantity') ?: 1;
        $requestBudget = ($rentalRequest->total_budget ?? 0) / $totalItems;
        $templatePrice = $template->proposed_price ?? 0;

        if ($requestBudget == 0) return 0.5; // Если бюджет не указан

        $difference = abs($templatePrice - $requestBudget) / $requestBudget;
        return max(0, 1 - $difference);
    }

    private function getConfidenceLevel($score)
    {
        if ($score >= 80) return 'high';
        if ($score >= 60) return 'medium';
        if ($score >= 40) return 'low';
        return 'very-low';
    }

    private function getConfidenceText($score)
    {
        if ($score >= 80) return 'Высокая';
        if ($score >= 60) return 'Средняя';
        if ($score >= 40) return 'Низкая';
        return 'Очень низкая';
    }

    private function getRecommendationReason($template, $rentalRequest, $score)
    {
        if ($score >= 80) {
            return 'Высокое соответствие категории и бюджета';
        } elseif ($score >= 60) {
            return 'Хорошее соответствие основным параметрам';
        } elseif ($score >= 40) {
            return 'Базовое соответствие требованиям';
        } else {
            return 'Частичное соответствие';
        }
    }
}
