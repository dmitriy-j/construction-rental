<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Http\Controllers\CheckoutController;
use App\Services\EquipmentAvailabilityService;
use App\Models\RentalRequestResponse;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

class ProposalCartController extends Controller
{
    protected $cartService;
    protected $checkoutController;

    public function __construct(
        CartService $cartService,
        CheckoutController $checkoutController
    ) {
        $this->cartService = $cartService;
        $this->checkoutController = $checkoutController;
        $this->middleware(['auth:sanctum']);

        // 🔥 ГАРАНТИРУЕМ JSON ОТВЕТ ДЛЯ ВСЕХ API МЕТОДОВ
        $this->middleware(function ($request, $next) {
            // Устанавливаем заголовок Accept на application/json, если не установлен
            if (!$request->wantsJson()) {
                $request->headers->set('Accept', 'application/json');
            }
            return $next($request);
        });
    }

    /**
     * Получение корзины с предложениями
     */
    public function getProposalCart(): JsonResponse
    {
        try {
            // 🔥 ИСПОЛЬЗУЕМ НОВЫЙ МЕТОД С ДОСТАВКОЙ
            $cart = $this->cartService->getProposalCartWithDelivery();

            $progress = $cart->rental_request_id
                ? $this->cartService->getRequestProgress($cart->rental_request_id)
                : null;

            // 🔥 ИСПРАВЛЕНО: Возвращаем корректные данные
            return response()->json([
                'success' => true,
                'data' => [
                    'cart' => $cart,
                    'total_rental' => $cart->total_base_amount,
                    'total_delivery' => $cart->items->sum('delivery_cost'),
                    'total_platform_fee' => $cart->total_platform_fee,
                    'grand_total' => $cart->total_base_amount + $cart->items->sum('delivery_cost'),
                    'reserved_until' => $cart->reserved_until,
                    'progress' => $progress
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Добавление подтвержденного предложения в корзину
     */
    public function addToCart(Request $request): JsonResponse
    {
        $request->validate([
            'proposal_id' => 'required|exists:rental_request_responses,id'
        ]);

        try {
            $proposal = RentalRequestResponse::with(['equipment.rentalTerms', 'rentalRequest'])
                ->findOrFail($request->proposal_id);

            // 🔥 АВТОМАТИЧЕСКИ МЕНЯЕМ СТАТУС НА ACCEPTED ПРИ ДОБАВЛЕНИИ В КОРЗИНУ
            if ($proposal->status !== 'accepted') {
                $proposal->update(['status' => 'accepted']);
            }

            // Остальная логика добавления в корзину...
            $cartItem = $this->cartService->addProposalItem($proposal->id);

            return response()->json([
                'success' => true,
                'message' => 'Предложение принято и добавлено в корзину',
                'data' => [
                    'cart_item' => $cartItem->load('proposal.equipment'),
                    'reserved_until' => $cartItem->cart->reserved_until
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Удаление выбранных позиций из корзины предложений
     */
    public function removeSelected(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'items' => 'required|array',
                'items.*' => 'exists:cart_items,id'
            ]);

            $deletedCount = 0;
            foreach ($request->items as $itemId) {
                $this->cartService->removeItem($itemId);
                $deletedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Удалено {$deletedCount} позиций",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * 🔥 НОВЫЙ МЕТОД: Массовое удаление выбранных предложений
     */
    public function removeSelectedItems(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'selected_items' => 'required|array',
                'selected_items.*' => 'exists:cart_items,id'
            ]);

            $deletedCount = 0;
            foreach ($request->selected_items as $itemId) {
                $this->cartService->removeItem($itemId);
                $deletedCount++;
            }

            // Получаем обновленную корзину
            $cart = $this->cartService->getProposalCartWithDelivery();

            return response()->json([
                'success' => true,
                'message' => "Удалено {$deletedCount} предложений",
                'deleted_count' => $deletedCount,
                'cart' => $cart,
                'cart_count' => $cart->items->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * 🔥 Оформление выбранных предложений - теперь используем существующий CheckoutController
     */
    public function checkoutSelected(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'selected_items' => 'required|array',
                'selected_items.*' => 'exists:cart_items,id'
            ]);

            \Log::info('[PROPOSAL_CHECKOUT_API] Starting API checkout with enhanced availability', [
                'user_id' => auth()->id(),
                'selected_items' => $request->selected_items
            ]);

            // Создаем запрос для CheckoutController
            $checkoutRequest = new Request();
            $checkoutRequest->setMethod('POST');
            $checkoutRequest->request->add([
                'selected_items' => json_encode($request->selected_items)
            ]);

            // 🔥 ВАЖНО: Устанавливаем заголовок для JSON и пользователя
            $checkoutRequest->headers->set('Accept', 'application/json');
            $checkoutRequest->setUserResolver(function () {
                return auth()->user();
            });

            // Вызываем метод оформления заказа
            $response = $this->checkoutController->processProposalCheckout($checkoutRequest);

            // Если ответ уже JSON, возвращаем его
            if ($response instanceof JsonResponse) {
                return $response;
            }

            throw new \Exception('Неизвестный ответ от сервера оформления заказа');

        } catch (\Exception $e) {
            \Log::error('[PROPOSAL_CHECKOUT_API] API checkout failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка оформления заказа: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Продление резервирования
     */
    public function extendReservation(): JsonResponse
    {
        try {
            $extended = $this->cartService->extendProposalReservation();

            return response()->json([
                'success' => $extended,
                'message' => $extended
                    ? 'Резервирование продлено на 24 часа'
                    : 'Не удалось продлить резервирование',
                'reserved_until' => $extended ? $this->cartService->getCart(Cart::TYPE_PROPOSAL)->reserved_until : null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Получение прогресса заявки
     */
    public function getRequestProgress($requestId): JsonResponse
    {
        try {
            $progress = $this->cartService->getRequestProgress($requestId);

            return response()->json([
                'success' => true,
                'data' => $progress
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * 🔥 ОБНОВЛЕННЫЙ МЕТОД С ГАРАНТИЕЙ JSON ОТВЕТА
     */
   public function updateRentalPeriod(Request $request): JsonResponse
    {
        \Log::info('[PROPOSAL_CART_UPDATE] START - Updating rental period with enhanced availability check', [
            'user_id' => auth()->id(),
            'request_data' => $request->all()
        ]);

        try {
            $input = $request->all();

            // Декодируем selected_items если пришла как строка
            if (isset($input['selected_items']) && is_string($input['selected_items'])) {
                $input['selected_items'] = json_decode($input['selected_items'], true);
            }

            $validator = Validator::make($input, [
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'selected_items' => 'required|array',
                'selected_items.*' => 'exists:cart_items,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации: ' . $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            $selectedItems = $input['selected_items'];
            $startDate = $input['start_date'];
            $endDate = $input['end_date'];

            $cart = $this->cartService->getCart(Cart::TYPE_PROPOSAL);

            DB::beginTransaction();

            try {
                $updatedItems = [];
                $availabilityService = app(EquipmentAvailabilityService::class);

                foreach ($selectedItems as $itemId) {
                    $cartItem = CartItem::with(['proposal.rentalRequest', 'rentalCondition', 'proposal.equipment'])
                        ->where('id', $itemId)
                        ->where('cart_id', $cart->id)
                        ->first();

                    if (!$cartItem) {
                        \Log::warning('[PROPOSAL_CART_UPDATE] Cart item not found', ['item_id' => $itemId]);
                        continue;
                    }

                    // 🔥 УЛУЧШЕННАЯ ПРОВЕРКА ДОСТУПНОСТИ С УЧЕТОМ ТЕКУЩЕГО РЕЗЕРВА
                    $isAvailable = $availabilityService->isAvailableForCart(
                        $cartItem->proposal->equipment,
                        $startDate,
                        $endDate,
                        $cart->reservation_token // Передаем токен текущей корзины
                    );

                    if (!$isAvailable) {
                        // 🔥 УЛУЧШЕННОЕ СООБЩЕНИЕ ОБ ОШИБКЕ
                        $nextAvailable = $availabilityService->calculateNextAvailableDate($cartItem->proposal->equipment->id);
                        $errorMsg = $nextAvailable
                            ? "Оборудование '{$cartItem->proposal->equipment->title}' недоступно на выбранные даты. Ближайшая доступная дата: " . $nextAvailable->format('d.m.Y')
                            : "Оборудование '{$cartItem->proposal->equipment->title}' недоступно на выбранные даты. Пожалуйста, выберите другие даты.";

                        throw new \Exception($errorMsg);
                    }

                    // 🔥 ОБНОВЛЯЕМ ВРЕМЕННОЕ РЕЗЕРВИРОВАНИЕ
                    $this->updateEquipmentReservation($cartItem, $startDate, $endDate, $cart);

                    // ✅ ИСПРАВЛЕНО: Используем публичный метод модели CartItem
                    $workingHours = $cartItem->calculateWorkingHoursForCartItem();

                    $customerPricePerHour = $cartItem->fixed_customer_price;
                    $lessorPricePerHour = $cartItem->fixed_lessor_price;
                    $platformFeePerHour = $cartItem->platform_fee;

                    $updateData = [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'period_count' => $workingHours,
                        'base_price' => $customerPricePerHour,
                        'fixed_customer_price' => $customerPricePerHour,
                        'fixed_lessor_price' => $lessorPricePerHour,
                        'platform_fee' => $platformFeePerHour,
                        'actual_working_hours' => $workingHours, // ✅ Сохраняем фактические рабочие часы
                    ];

                    $cartItem->update($updateData);
                    $cartItem->refresh();

                    $updatedItems[] = $cartItem;
                }

                DB::commit();

                // 🔥 ОБНОВЛЯЕМ РЕЗЕРВИРОВАНИЕ КОРЗИНЫ
                $cart->update([
                    'reserved_until' => now()->addHours(24),
                ]);

                $this->cartService->recalculateTotals($cart);
                $updatedCart = $this->cartService->getProposalCartWithDelivery();

                return response()->json([
                    'success' => true,
                    'message' => 'Даты аренды и стоимость успешно обновлены',
                    'data' => [
                        'cart' => $updatedCart,
                        'reserved_until' => $cart->reserved_until,
                        'updated_items_count' => count($updatedItems)
                    ]
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            \Log::error('[PROPOSAL_CART_UPDATE] Update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * 🔥 FALLBACK РАСЧЕТ РАБОЧИХ ЧАСОВ
     */
    private function calculateFallbackWorkingHours(CartItem $cartItem, string $startDate, string $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = $start->diffInDays($end) + 1;

        $shiftHours = $cartItem->rentalCondition->shift_hours ?? 8;
        $shiftsPerDay = $cartItem->rentalCondition->shifts_per_day ?? 1;

        return $days * $shiftHours * $shiftsPerDay;
    }

    private function updateEquipmentReservation(CartItem $cartItem, string $startDate, string $endDate, Cart $cart): void
    {
        $availabilityService = app(EquipmentAvailabilityService::class);

        // Освобождаем старое резервирование
        $availabilityService->releaseEquipmentReservation(
            $cartItem->proposal->equipment,
            $cartItem->start_date,
            $cartItem->end_date,
            $cart->reservation_token
        );

        // Создаем новое резервирование
        $availabilityService->bookEquipment(
            $cartItem->proposal->equipment,
            $startDate,
            $endDate,
            null,
            EquipmentAvailabilityService::STATUS_TEMP_RESERVE,
            $cart->reservation_token
        );
    }

    /**
     * Удаление элемента из корзины
     */
    public function removeItem($itemId): JsonResponse
    {
        try {
            $this->cartService->removeItem($itemId);

            return response()->json([
                'success' => true,
                'message' => 'Позиция удалена из корзины'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    //тест, удалить
    public function testApi(Request $request): JsonResponse
    {
        \Log::info('[API_TEST] Test endpoint called', [
            'user_id' => auth()->id(),
            'request_data' => $request->all()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API работает корректно',
            'data' => [
                'user_id' => auth()->id(),
                'timestamp' => now()->toDateTimeString(),
                'test_array' => [1, 2, 3],
                'request_data' => $request->all()
            ]
        ]);
    }
}
