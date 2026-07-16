<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\RentalRequestResponse;
use App\Services\CartService;
use Illuminate\Http\Request;

class ProposalCartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index()
    {
        $cart = Cart::where('user_id', auth()->id())
            ->where('type', Cart::TYPE_PROPOSAL)
            ->with('items.proposal.equipment', 'items.proposal.lessor.company')
            ->first();

        if (!$cart) {
            return response()->json(['items' => [], 'count' => 0, 'total' => 0]);
        }

        return response()->json([
            'items' => $cart->items,
            'count' => $cart->items->count(),
            'total' => $cart->items->sum('total_price'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'proposal_id' => 'required|exists:rental_request_responses,id',
        ]);

        try {
            $proposal = RentalRequestResponse::with('equipment.rentalTerms')->findOrFail($validated['proposal_id']);
            $item = $this->cartService->addProposalItem($proposal->id);
            return response()->json(['success' => true, 'item' => $item]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        $cart = Cart::where('user_id', auth()->id())
            ->where('type', Cart::TYPE_PROPOSAL)
            ->first();

        if ($cart) {
            $item = $cart->items()->findOrFail($id);
            $this->cartService->releaseReservation($item);
            $item->delete();
        }

        return response()->json(['success' => true]);
    }
}
