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
            \Log::debug("🔍 Начало расчета рекомендаций", [
                'user_id' => $user->id,
                'rental_request_id' => $rentalRequestId
            ]);

            $rentalRequest = RentalRequest::with(['items.category', 'location'])->findOrFail($rentalRequestId);

            // Получаем активные шаблоны пользователя
            $templates = ProposalTemplate::where('user_id', $user->id)
                ->where('is_active', true)
                ->with('category')
                ->get();

            \Log::debug("📋 Найдено шаблонов", [
                'templates_count' => $templates->count(),
                'templates_ids' => $templates->pluck('id')
            ]);

            \Log::debug("📊 Данные заявки", [
                'request_items_count' => $rentalRequest->items->count(),
                'request_items_categories' => $rentalRequest->items->pluck('category_id'),
                'total_budget' => $rentalRequest->total_budget,
                'budget_from' => $rentalRequest->budget_from,
                'budget_to' => $rentalRequest->budget_to
            ]);

            $recommendations = [];

            foreach ($templates as $template) {
                $score = $this->calculateTemplateScore($template, $rentalRequest);

                // ДЕТАЛЬНОЕ ЛОГИРОВАНИЕ ДЛЯ КАЖДОГО ШАБЛОНА
                $categoryMatch = $this->checkCategoryMatch($template, $rentalRequest);
                $successRate = $template->success_rate ?? 0;
                $budgetMatch = $this->checkBudgetMatch($template, $rentalRequest);
                $responseTime = $template->response_time ?? 24;
                $responseTimeScore = $responseTime <= 4 ? 10 : ($responseTime <= 12 ? 7 : 3);

                \Log::debug("📐 Расчет баллов для шаблона {$template->id}: {$template->name}", [
                    'category_match' => $categoryMatch * 40,
                    'success_rate' => ($successRate / 100) * 30,
                    'budget_match' => $budgetMatch * 20,
                    'response_time' => $responseTimeScore,
                    'total_score' => $score,
                    'category_id' => $template->category_id,
                    'template_price' => $template->proposed_price,
                    'success_rate_value' => $successRate
                ]);

                if ($score >= 50) {
                    $recommendations[] = [
                        'template' => $template,
                        'score' => $score,
                        'confidence_level' => $this->getConfidenceLevel($score),
                        'confidence' => $this->getConfidenceText($score),
                        'reason' => $this->getRecommendationReason($template, $rentalRequest, $score)
                    ];
                } else {
                    \Log::debug("❌ Шаблон {$template->id} не прошел порог", ['score' => $score]);
                }
            }

            if (empty($recommendations)) {
                \Log::debug("🔄 Fallback: нет рекомендаций выше порога, ищем альтернативы");
                $recommendations = $this->getFallbackRecommendations($templates, $rentalRequest);
            }

            // ВРЕМЕННО: понижаем порог для тестирования
            if (empty($recommendations)) {
                \Log::debug("🔄 Попытка с пониженным порогом 30");
                foreach ($templates as $template) {
                    $score = $this->calculateTemplateScore($template, $rentalRequest);
                    if ($score >= 30) {
                        $recommendations[] = [
                            'template' => $template,
                            'score' => $score,
                            'confidence_level' => $this->getConfidenceLevel($score),
                            'confidence' => $this->getConfidenceText($score),
                            'reason' => $this->getRecommendationReason($template, $rentalRequest, $score) . ' (низкий порог)'
                        ];
                    }
                }
            }

            usort($recommendations, function($a, $b) {
                return $b['score'] - $a['score'];
            });

            \Log::debug("✅ Итоговые рекомендации", [
                'total_recommendations' => count($recommendations),
                'recommendations_scores' => collect($recommendations)->pluck('score')
            ]);

            return response()->json([
                'success' => true,
                'recommendations' => array_slice($recommendations, 0, 3),
                'debug' => [ // Добавляем отладочную информацию
                    'templates_checked' => $templates->count(),
                    'threshold_passed' => count($recommendations),
                    'rental_request_data' => [
                        'items_count' => $rentalRequest->items->count(),
                        'categories' => $rentalRequest->items->pluck('category_id'),
                        'total_budget' => $rentalRequest->total_budget
                    ]
                ]
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

    // Новый метод для fallback-рекомендаций
    private function getFallbackRecommendations($templates, $rentalRequest)
    {
        $fallbacks = [];

        foreach ($templates as $template) {
            $score = $this->calculateFallbackScore($template, $rentalRequest);

            if ($score >= 20) {
                $fallbacks[] = [
                    'template' => $template,
                    'score' => $score,
                    'confidence_level' => 'low',
                    'confidence' => 'Низкая',
                    'reason' => 'Резервная рекомендация: ' . $this->getFallbackReason($template, $rentalRequest),
                    'is_fallback' => true
                ];
            }
        }

        usort($fallbacks, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        return array_slice($fallbacks, 0, 2);
    }

    private function calculateFallbackScore($template, $rentalRequest)
    {
        $score = 0;

        // В fallback учитываем только базовые факторы
        $score += ($template->success_rate / 100) * 40;
        $score += (($template->usage_count ?? 0) > 0) ? 20 : 0;
        $score += ($template->response_time <= 8) ? 15 : 5;
        $score += ($template->is_active) ? 10 : 0;

        // Минимальный бонус за любую категорию
        $score += 15;

        return min(100, $score);
    }

    private function getFallbackReason($template, $rentalRequest)
    {
        $reasons = [];

        if ($template->success_rate > 50) {
            $reasons[] = 'высокая успешность';
        }

        if ($template->usage_count > 5) {
            $reasons[] = 'популярный шаблон';
        }

        if ($template->response_time <= 4) {
            $reasons[] = 'быстрый ответ';
        }

        return empty($reasons) ? 'базовый шаблон' : implode(', ', $reasons);
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

        // 1. Проверка соответствия категории (35% вместо 40%)
        $categoryMatch = $this->checkCategoryMatch($template, $rentalRequest);
        $score += $categoryMatch * 35;

        // 2. Историческая успешность (25% вместо 30%) + бонус для новых
        $successRate = $template->success_rate ?? 0;
        $successScore = ($successRate / 100) * 25;

        // Бонус для новых шаблонов
        if (($template->usage_count ?? 0) < 5) {
            $successScore += 10; // +10% бонус для новых шаблонов
        }

        $score += min(25, $successScore); // Не больше 25%

        // 3. Соответствие бюджету (25% вместо 20%) с гибкой шкалой
        $budgetMatch = $this->checkBudgetMatch($template, $rentalRequest);
        $score += $budgetMatch * 25;

        // 4. Скорость ответа (10%)
        $responseTime = $template->response_time ?? 24;
        $responseTimeScore = $this->calculateResponseTimeScore($responseTime);
        $score += $responseTimeScore;

        // 5. Бонус за популярность (5%)
        if (($template->usage_count ?? 0) > 10) {
            $score += 5;
        }

        return min(100, $score);
    }

    private function checkCategoryMatch($template, $rentalRequest)
    {
        // Шаблоны без категории получают базовый балл
        if (!$template->category_id) {
            return 0.3;
        }

        if (!$rentalRequest->items || $rentalRequest->items->isEmpty()) {
            return 0.0;
        }

        // Точное совпадение категории
        foreach ($rentalRequest->items as $item) {
            if ($item->category_id == $template->category_id) {
                return 1.0;
            }
        }

        // Частичное совпадение через родительские категории
        return $this->checkParentCategoryMatch($template, $rentalRequest);
    }

    private function checkParentCategoryMatch($template, $rentalRequest)
    {
        try {
            $templateCategory = \App\Models\Category::find($template->category_id);
            if (!$templateCategory) return 0.1;

            foreach ($rentalRequest->items as $item) {
                $itemCategory = \App\Models\Category::find($item->category_id);
                if ($itemCategory && $this->categoriesAreRelated($templateCategory, $itemCategory)) {
                    return 0.6; // Частичное совпадение
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Error checking parent categories: ' . $e->getMessage());
        }

        return 0.1; // Минимальный балл для разных категорий
    }

    private function categoriesAreRelated($cat1, $cat2)
    {
        // Проверяем общих родителей или похожие категории
        if ($cat1->parent_id && $cat1->parent_id == $cat2->parent_id) {
            return true;
        }

        // Можно добавить логику проверки по названию или тегам
        $similarKeywords = ['экскаватор', 'бульдозер', 'кран', 'погрузчик'];
        $name1 = strtolower($cat1->name);
        $name2 = strtolower($cat2->name);

        foreach ($similarKeywords as $keyword) {
            if (str_contains($name1, $keyword) && str_contains($name2, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function checkBudgetMatch($template, $rentalRequest)
    {
        $templatePrice = $template->proposed_price ?? 0;

        // Используем лучший доступный бюджет
        $requestBudget = $rentalRequest->total_budget ??
                        $rentalRequest->calculated_budget_to ??
                        $rentalRequest->budget_to ??
                        0;

        // Если бюджет не указан - возвращаем нейтральный высокий балл
        if ($requestBudget == 0) {
            return 0.7;
        }

        // Нормализуем цены для сравнения
        $totalItems = max(1, $rentalRequest->items->sum('quantity'));
        $normalizedRequestBudget = $requestBudget / $totalItems;
        $normalizedTemplatePrice = $templatePrice;

        $difference = abs($normalizedTemplatePrice - $normalizedRequestBudget) / $normalizedRequestBudget;

        // Гибкая шкала соответствия
        if ($difference <= 0.1) return 1.0;    // ±10% - отлично
        if ($difference <= 0.3) return 0.7;    // ±30% - хорошо
        if ($difference <= 0.5) return 0.4;    // ±50% - удовлетворительно
        if ($difference <= 1.0) return 0.2;    // ±100% - минимально
        return 0.1;                            // >100% - очень плохо
    }

    private function calculateResponseTimeScore($responseTime)
    {
        if ($responseTime <= 2) return 10;     // До 2 часов - отлично
        if ($responseTime <= 4) return 8;      // До 4 часов - хорошо
        if ($responseTime <= 8) return 6;      // До 8 часов - нормально
        if ($responseTime <= 12) return 4;     // До 12 часов - приемлемо
        if ($responseTime <= 24) return 2;     // До 24 часов - минимально
        return 0;                              // Более 24 часов - плохо
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

    /**
     * Быстрые рекомендации для списка заявок (используется в LessorRentalRequestList.vue)
     */
    public function getQuickRecommendations(Request $request) // ← ИЗМЕНИЛ НАЗВАНИЕ!
    {
        try {
            \Log::info('🚀 Запрос быстрых рекомендаций для списка заявок');

            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $requestIds = $request->input('request_ids', []);

            \Log::info('📋 ID заявок для быстрых рекомендаций:', ['request_ids' => $requestIds]);

            // Получаем активные шаблоны пользователя
            $templates = ProposalTemplate::where('user_id', $user->id)
                ->where('is_active', true)
                ->get();

            $allRecommendations = [];

            // Для каждой заявки находим подходящие шаблоны
            foreach ($requestIds as $requestId) {
                $rentalRequest = RentalRequest::with('items')->find($requestId);

                if (!$rentalRequest) {
                    \Log::warning("Заявка {$requestId} не найдена");
                    continue;
                }

                $requestRecommendations = [];

                foreach ($templates as $template) {
                    $score = $this->calculateTemplateScore($template, $rentalRequest);

                    if ($score >= 30) { // Только релевантные шаблоны
                        $confidenceLevel = $this->getConfidenceLevel($score);
                        $color = $this->getConfidenceColor($confidenceLevel);

                        $requestRecommendations[] = [
                            'request_id' => $requestId,
                            'template' => [
                                'id' => $template->id,
                                'name' => $template->name,
                                'proposed_price' => $template->proposed_price,
                                'response_time' => $template->response_time,
                                'message' => $template->message,
                                'additional_terms' => $template->additional_terms,
                                'category_id' => $template->category_id
                            ],
                            'score' => $score,
                            'confidence' => $this->getConfidenceText($score),
                            'color' => $color,
                            'reason' => $this->getQuickRecommendationReason($template, $rentalRequest, $score)
                        ];
                    }
                }

                // Сортируем по убыванию релевантности и берем топ-3
                usort($requestRecommendations, function($a, $b) {
                    return $b['score'] - $a['score'];
                });

                $allRecommendations = array_merge($allRecommendations, array_slice($requestRecommendations, 0, 3));
            }

            \Log::info('✅ Быстрые рекомендации сгенерированы:', [
                'total_requests' => count($requestIds),
                'total_recommendations' => count($allRecommendations)
            ]);

            return response()->json([
                'success' => true,
                'recommendations' => $allRecommendations
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ КРИТИЧЕСКАЯ ОШИБКА в getQuickRecommendations:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Возвращаем пустые рекомендации чтобы интерфейс не ломался
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения быстрых рекомендаций',
                'error' => $e->getMessage(),
                'recommendations' => []
            ], 500);
        }
    }
    /**
     * Получить цвет для уровня уверенности
     */
    private function getConfidenceColor($confidenceLevel)
    {
        $colors = [
            'high' => 'success',
            'medium' => 'warning',
            'low' => 'info',
            'very-low' => 'secondary'
        ];

        return $colors[$confidenceLevel] ?? 'secondary';
    }
    /**
     * Причина рекомендации для быстрого отображения
     */
    private function getQuickRecommendationReason($template, $rentalRequest, $score)
    {
        if ($score >= 80) {
            return 'Идеальное соответствие категории и бюджета';
        } elseif ($score >= 60) {
            return 'Хорошее соответствие требованиям';
        } elseif ($score >= 40) {
            return 'Базовое соответствие категории';
        } else {
            return 'Частичное соответствие параметрам';
        }
    }
    // 🔥 ДОБАВИТЬ ТЕСТОВЫЙ МЕТОД ДЛЯ ОТЛАДКИ
    public function testQuick()
    {
        return response()->json([
            'success' => true,
            'message' => 'Контроллер работает!',
            'test_data' => [
                'controller' => 'LessorRecommendationController',
                'method' => 'testQuick',
                'timestamp' => now()->toDateTimeString(),
                'requests' => ['request1' => [], 'request2' => []]
            ]
        ]);
    }
}
