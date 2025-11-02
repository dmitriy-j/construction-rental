<?php

namespace App\Http\Controllers\Lessee;

use App\Http\Controllers\Controller;
use App\Models\RentalRequestResponse;
use App\Services\ProposalManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RequestResponseController extends Controller
{
     public function __construct(private ProposalManagementService $proposalService)
    {
        $this->middleware('auth');
        $this->middleware('check.company.verified');
        $this->middleware('check.user.type:lessee');
    }

    /**
     * Список предложений по заявкам арендатора
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');
        $perPage = $request->get('per_page', 15);

        $proposals = RentalRequestResponse::with([
            'rentalRequest.category',
            'rentalRequest.location',
            'equipment.images',
            'lessor.company'
        ])
        ->whereHas('rentalRequest', function ($query) {
            $query->where('user_id', auth()->id());
        })
        ->when($status !== 'all', function ($query) use ($status) {
            $query->where('status', $status);
        })
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);

        return view('lessee.rental_responses.index', compact('proposals', 'status'));
    }

    /**
     * Детали предложения
     */
    public function show($id)
    {
        $proposal = RentalRequestResponse::with([
            'rentalRequest.category',
            'rentalRequest.location',
            'equipment.images',
            'equipment.specifications',
            'lessor.company',
            'lessor.equipment' => function ($query) {
                $query->where('is_active', true)->with('images');
            }
        ])->findOrFail($id);

        // Проверяем, что заявка принадлежит пользователю
        if ($proposal->rentalRequest->user_id !== auth()->id()) {
            abort(403);
        }

        // Расчет стоимости через CartService
        $cartService = app(\App\Services\CartService::class);
        $costAnalysis = $cartService->calculateProposalCost($proposal);

        return view('lessee.rental_responses.show', compact('proposal', 'costAnalysis'));
    }

    /**
     * Отклонение предложения
     */
    public function reject($id): JsonResponse
    {
        try {
            $proposal = RentalRequestResponse::findOrFail($id);

            // Проверка прав
            if ($proposal->rentalRequest->user_id !== auth()->id()) {
                throw new \Exception('Недостаточно прав для выполнения операции');
            }

            $proposal->update(['status' => 'rejected']);

            // Отправка уведомления арендодателю
            event(new \App\Events\ProposalRejected($proposal));

            return response()->json([
                'success' => true,
                'message' => 'Предложение отклонено'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Запрос встречного предложения
     */
    public function counterOffer(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'counter_price' => 'required|numeric|min:0',
            'message' => 'nullable|string|max:1000'
        ]);

        try {
            $proposal = RentalRequestResponse::findOrFail($id);

            if ($proposal->rentalRequest->user_id !== auth()->id()) {
                throw new \Exception('Недостаточно прав для выполнения операции');
            }

            $proposal->update([
                'status' => 'counter_offer',
                'counter_price' => $validated['counter_price'],
                'message' => $validated['message'] ?? $proposal->message
            ]);

            // Уведомление арендодателю о встречном предложении
            event(new \App\Events\CounterOfferMade($proposal));

            return response()->json([
                'success' => true,
                'message' => 'Встречное предложение отправлено'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }
}
