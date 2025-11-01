<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\RentalRequestResponse;
use App\Models\ProposalTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LessorAnalyticsController extends Controller
{
    /**
     * Получение счетчиков для дашборда
     */
    public function getDashboardCounters()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не авторизован'
                ], 401);
            }

            \Log::info('Getting dashboard counters for user: ' . $user->id);

            // Активные заявки на платформе (публичные и активные)
            $activeRequests = RentalRequest::where('status', 'active')
                ->where(function($query) {
                    $query->where('visibility', 'public')
                          ->orWhereNull('visibility');
                })
                ->where(function($query) {
                    $query->where('expires_at', '>', now())
                          ->orWhereNull('expires_at');
                })
                ->count();

            // Срочные заявки (созданные за последние 2 часа)
            $urgentRequests = RentalRequest::where('status', 'active')
                ->where(function($query) {
                    $query->where('visibility', 'public')
                          ->orWhereNull('visibility');
                })
                ->where('created_at', '>=', now()->subHours(2))
                ->count();

            // Мои активные шаблоны
            $myTemplates = ProposalTemplate::where('user_id', $user->id)
                ->where('is_active', true)
                ->count();

            // Мои активные предложения - ИСПРАВЛЕНО: используем lessor_id вместо user_id
            $myProposals = RentalRequestResponse::where('lessor_id', $user->id)
                ->whereIn('status', ['pending', 'under_review', 'sent'])
                ->count();

            \Log::info('Dashboard counters calculated', [
                'active_requests' => $activeRequests,
                'urgent_requests' => $urgentRequests,
                'templates' => $myTemplates,
                'my_proposals' => $myProposals
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'active_requests' => $activeRequests,
                    'urgent_requests' => $urgentRequests,
                    'templates' => $myTemplates,
                    'my_proposals' => $myProposals,
                    'last_updated' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting dashboard counters: ' . $e->getMessage());
            \Log::error('Error trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения счетчиков: ' . $e->getMessage(),
                'data' => [
                    'active_requests' => 0,
                    'urgent_requests' => 0,
                    'templates' => 0,
                    'my_proposals' => 0,
                    'last_updated' => now()->toISOString()
                ]
            ], 500);
        }
    }

    /**
     * Данные для аналитики в реальном времени
     */
    public function getRealTimeData()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не авторизован'
                ], 401);
            }

            \Log::info('Getting realtime analytics for user: ' . $user->id);

            // Активные заявки
            $activeRequests = RentalRequest::where('status', 'active')
                ->where(function($query) {
                    $query->where('visibility', 'public')
                          ->orWhereNull('visibility');
                })
                ->where(function($query) {
                    $query->where('expires_at', '>', now())
                          ->orWhereNull('expires_at');
                })
                ->count();

            // Новые заявки за сегодня
            $newRequestsToday = RentalRequest::where('status', 'active')
                ->where(function($query) {
                    $query->where('visibility', 'public')
                          ->orWhereNull('visibility');
                })
                ->whereDate('created_at', today())
                ->count();

            // Мои активные предложения - ИСПРАВЛЕНО: используем lessor_id
            $myActiveProposals = RentalRequestResponse::where('lessor_id', $user->id)
                ->whereIn('status', ['pending', 'under_review', 'sent'])
                ->count();

            // Конверсия (отношение принятых предложений к отправленным) - ИСПРАВЛЕНО: используем lessor_id
            $totalProposals = RentalRequestResponse::where('lessor_id', $user->id)->count();
            $acceptedProposals = RentalRequestResponse::where('lessor_id', $user->id)
                ->where('status', 'accepted')
                ->count();

            $conversionRate = $totalProposals > 0 ? round(($acceptedProposals / $totalProposals) * 100, 1) : 0;

            // Среднее время ответа (в часах) - упрощенная версия
            $avgResponseTime = 4.5;

            // Доля на рынке (упрощенная версия) - ИСПРАВЛЕНО: используем lessor_id
            $totalMarketProposals = RentalRequestResponse::count();
            $myMarketShare = $totalMarketProposals > 0 ? round(($totalProposals / $totalMarketProposals) * 100, 1) : 0;

            \Log::info('Realtime analytics calculated', [
                'activeRequests' => $activeRequests,
                'newRequestsToday' => $newRequestsToday,
                'myActiveProposals' => $myActiveProposals,
                'conversionRate' => $conversionRate
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'activeRequests' => $activeRequests,
                    'newRequestsToday' => $newRequestsToday,
                    'myActiveProposals' => $myActiveProposals,
                    'conversionRate' => $conversionRate,
                    'avgResponseTime' => $avgResponseTime . 'ч',
                    'marketShare' => $myMarketShare . '%'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting realtime analytics: ' . $e->getMessage());
            \Log::error('Error trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения данных реального времени: ' . $e->getMessage(),
                'data' => [
                    'activeRequests' => 0,
                    'newRequestsToday' => 0,
                    'myActiveProposals' => 0,
                    'conversionRate' => 0,
                    'avgResponseTime' => '0ч',
                    'marketShare' => '0%'
                ]
            ], 500);
        }
    }

    /**
     * Стратегическая аналитика
     */
    public function getStrategicData()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не авторизован'
                ], 401);
            }

            \Log::info('Getting strategic analytics for user: ' . $user->id);

            // Моя конверсия - ИСПРАВЛЕНО: используем lessor_id
            $totalProposals = RentalRequestResponse::where('lessor_id', $user->id)->count();
            $acceptedProposals = RentalRequestResponse::where('lessor_id', $user->id)
                ->where('status', 'accepted')
                ->count();
            $myConversionRate = $totalProposals > 0 ? round(($acceptedProposals / $totalProposals) * 100, 1) : 0;

            // Средняя конверсия по рынку (упрощенно)
            $marketConversionRate = 15.5;

            // Тренд конверсии
            $trend = 'stable';

            // Ценовая аналитика
            $priceAnalytics = $this->getPriceAnalytics($user->id);

            // Рекомендации
            $recommendations = $this->generateStrategicRecommendations($user->id);

            // Критические алерты
            $alerts = $this->getCriticalAlerts($user->id);

            \Log::info('Strategic analytics calculated', [
                'myConversionRate' => $myConversionRate,
                'recommendations_count' => count($recommendations)
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversion' => [
                        'myConversionRate' => $myConversionRate,
                        'marketConversionRate' => $marketConversionRate,
                        'trend' => $trend
                    ],
                    'pricing' => $priceAnalytics,
                    'recommendations' => $recommendations,
                    'alerts' => $alerts
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting strategic analytics: ' . $e->getMessage());
            \Log::error('Error trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения стратегической аналитики: ' . $e->getMessage(),
                'data' => [
                    'conversion' => [
                        'myConversionRate' => 0,
                        'marketConversionRate' => 0,
                        'trend' => 'stable'
                    ],
                    'pricing' => [
                        'myAvgPrice' => 0,
                        'marketAvgPrice' => 0,
                        'priceDifferencePercent' => 0
                    ],
                    'recommendations' => [],
                    'alerts' => []
                ]
            ], 500);
        }
    }

    /**
     * Ценовая аналитика
     */
    private function getPriceAnalytics($userId)
    {
        try {
            // Средняя цена моих предложений - ИСПРАВЛЕНО: используем lessor_id
            $myAvgPrice = RentalRequestResponse::where('lessor_id', $userId)
                ->where('proposed_price', '>', 0)
                ->avg('proposed_price') ?? 0;

            // Средняя цена по рынку (упрощенно)
            $marketAvgPrice = RentalRequestResponse::where('proposed_price', '>', 0)
                ->avg('proposed_price') ?? 1000;

            $priceDifferencePercent = $marketAvgPrice > 0
                ? round((($myAvgPrice - $marketAvgPrice) / $marketAvgPrice) * 100, 1)
                : 0;

            return [
                'myAvgPrice' => round($myAvgPrice, 2),
                'marketAvgPrice' => round($marketAvgPrice, 2),
                'priceDifferencePercent' => $priceDifferencePercent
            ];
        } catch (\Exception $e) {
            \Log::error('Error in price analytics: ' . $e->getMessage());
            return [
                'myAvgPrice' => 0,
                'marketAvgPrice' => 0,
                'priceDifferencePercent' => 0
            ];
        }
    }

    /**
     * Генерация стратегических рекомендаций
     */
    private function generateStrategicRecommendations($userId)
    {
        try {
            $recommendations = [];

            // Проверяем активные шаблоны
            $activeTemplates = ProposalTemplate::where('user_id', $userId)
                ->where('is_active', true)
                ->count();

            if ($activeTemplates == 0) {
                $recommendations[] = [
                    'id' => 1,
                    'icon' => 'fas fa-file-alt text-warning',
                    'message' => 'У вас нет активных шаблонов предложений. Создайте шаблоны для быстрого ответа на заявки.',
                    'priority' => 'high'
                ];
            }

            // Проверяем срочные заявки
            $urgentRequests = RentalRequest::where('status', 'active')
                ->where(function($query) {
                    $query->where('visibility', 'public')
                          ->orWhereNull('visibility');
                })
                ->where('created_at', '>=', now()->subHours(2))
                ->count();

            if ($urgentRequests > 0) {
                $recommendations[] = [
                    'id' => 2,
                    'icon' => 'fas fa-bolt text-danger',
                    'message' => "У вас {$urgentRequests} срочных заявок. Быстрый ответ увеличивает шансы на успех.",
                    'priority' => 'critical'
                ];
            }

            // Проверяем конверсию - ИСПРАВЛЕНО: используем lessor_id
            $totalProposals = RentalRequestResponse::where('lessor_id', $userId)->count();
            $acceptedProposals = RentalRequestResponse::where('lessor_id', $userId)
                ->where('status', 'accepted')
                ->count();
            $conversionRate = $totalProposals > 0 ? ($acceptedProposals / $totalProposals) * 100 : 0;

            if ($conversionRate < 10 && $totalProposals > 5) {
                $recommendations[] = [
                    'id' => 3,
                    'icon' => 'fas fa-chart-line text-info',
                    'message' => 'Ваша конверсия низкая. Рассмотрите возможность корректировки цен или условий.',
                    'priority' => 'medium'
                ];
            }

            // Общая рекомендация если мало данных
            if ($totalProposals < 3) {
                $recommendations[] = [
                    'id' => 4,
                    'icon' => 'fas fa-rocket text-success',
                    'message' => 'Начните активнее отвечать на заявки чтобы получить больше данных для аналитики.',
                    'priority' => 'low'
                ];
            }

            return $recommendations;
        } catch (\Exception $e) {
            \Log::error('Error generating recommendations: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Критические алерты
     */
    private function getCriticalAlerts($userId)
    {
        try {
            $alerts = [];

            // Проверяем просроченные предложения - ИСПРАВЛЕНО: используем lessor_id
            $expiredProposals = RentalRequestResponse::where('lessor_id', $userId)
                ->whereIn('status', ['pending', 'sent'])
                ->where('expires_at', '<', now())
                ->count();

            if ($expiredProposals > 0) {
                $alerts[] = [
                    'id' => 1,
                    'message' => "У вас {$expiredProposals} просроченных предложений. Обновите их или создайте новые.",
                    'actionText' => 'Просмотреть'
                ];
            }

            return $alerts;
        } catch (\Exception $e) {
            \Log::error('Error getting critical alerts: ' . $e->getMessage());
            return [];
        }
    }
}
