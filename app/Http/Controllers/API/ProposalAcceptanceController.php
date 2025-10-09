<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RentalRequestResponse;
use App\Services\ProposalManagementService;
use Illuminate\Http\Request;

class ProposalAcceptanceController extends Controller
{
    public function __construct(private ProposalManagementService $proposalService)
    {
        $this->middleware(['auth:sanctum', 'company.verified']);
    }

    public function acceptProposal($proposalId)
    {
        try {
            $proposal = RentalRequestResponse::findOrFail($proposalId);

            // Проверяем, что пользователь является владельцем заявки
            if ($proposal->rentalRequest->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Недостаточно прав'
                ], 403);
            }

            $order = $this->proposalService->acceptProposalAndCreateOrder($proposal);

            return response()->json([
                'success' => true,
                'message' => 'Предложение принято, заказ создан',
                'data' => [
                    'order_id' => $order->id,
                    'proposal_id' => $proposal->id
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при принятии предложения: ' . $e->getMessage()
            ], 500);
        }
    }
}
