<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\RentalRequestResponse;
use App\Models\ProposalTemplate;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LessorDashboardController extends Controller
{
    public function getCounters(Request $request)
    {
        try {
            $userId = auth()->id();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не авторизован',
                    'data' => $this->getEmptyCounters()
                ], 401);
            }

            Log::info('Загрузка счетчиков дашборда для пользователя', ['user_id' => $userId]);

            // 🔥 СРОЧНЫЕ ЗАЯВКИ (созданные менее 2 часов назад)
            $urgentRequests = RentalRequest::where('status', 'active')
                ->where('visibility', 'public')
                ->where('expires_at', '>', now())
                ->where('created_at', '>=', Carbon::now()->subHours(2))
                ->count();

            // 🔥 АКТИВНЫЕ ШАБЛОНЫ (используем user_id как основное поле)
            $templates = ProposalTemplate::where('user_id', $userId)
                ->where('is_active', true)
                ->count();

            // 🔥 МОИ АКТИВНЫЕ ПРЕДЛОЖЕНИЯ
            $myProposals = RentalRequestResponse::where('lessor_id', $userId)
                ->where('status', 'pending')
                ->count();

            // 🔥 ОБЩЕЕ КОЛИЧЕСТВО АКТИВНЫХ ЗАЯВОК
            $activeRequests = RentalRequest::where('status', 'active')
                ->where('visibility', 'public')
                ->where('expires_at', '>', now())
                ->count();

            $counters = [
                'urgent_requests' => $urgentRequests,
                'templates' => $templates,
                'my_proposals' => $myProposals,
                'active_requests' => $activeRequests,
                'last_updated' => now()->toISOString()
            ];

            Log::info('Счетчики дашборда загружены', [
                'user_id' => $userId,
                'counters' => $counters
            ]);

            return response()->json([
                'success' => true,
                'data' => $counters
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка получения счетчиков дашборда: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки данных',
                'data' => $this->getEmptyCounters()
            ], 500);
        }
    }

    private function getEmptyCounters()
    {
        return [
            'urgent_requests' => 0,
            'templates' => 0,
            'my_proposals' => 0,
            'active_requests' => 0,
            'last_updated' => now()->toISOString()
        ];
    }
}
