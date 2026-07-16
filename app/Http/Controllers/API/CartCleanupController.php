<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartCleanupController extends Controller
{
    /**
     * Очистить старые позиции в корзине (у которых delivery_cost > 0 но нет address)
     */
    public function cleanup()
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['error' => 'Не авторизован'], 401);
        }

        // Удаляем позиции с delivery_cost > 0 и пустым address
        $deleted = CartItem::whereHas('cart', function ($q) use ($userId) {
                $q->where('user_id', $userId)->where('type', Cart::TYPE_REGULAR);
            })
            ->where('delivery_cost', '>', 0)
            ->where(function ($q) {
                $q->whereNull('address')
                  ->orWhere('address', '')
                  ->orWhere('address', ' ');
            })
            ->delete();

        return response()->json([
            'success' => true,
            'deleted_count' => $deleted,
            'message' => "Удалено {$deleted} позиций с битой доставкой"
        ]);
    }
}
