<?php
// app/Http/Controllers/API/LessorProposalController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RentalRequestResponse;
use App\Models\RentalRequest;
use App\Models\ProposalTemplate;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LessorProposalController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company.verified', 'company.lessor']);
    }

    public function index(Request $request)
    {
        $proposals = RentalRequestResponse::with(['rentalRequest', 'equipment'])
            ->where('lessor_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $proposals
        ]);
    }

    /**
     * Быстрое применение шаблона к заявке
     */
    public function applyTemplate(Request $request, $rentalRequestId)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'template_id' => 'required|exists:proposal_templates,id',
                'customizations' => 'sometimes|array',
                'customizations.proposed_price' => 'sometimes|numeric|min:0',
                'customizations.response_time' => 'sometimes|integer|min:1|max:168',
                'check_equipment' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибки валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Получаем заявку
            $rentalRequest = RentalRequest::with('items')->findOrFail($rentalRequestId);

            // Получаем шаблон (только принадлежащий текущему пользователю)
            $template = ProposalTemplate::where('id', $request->template_id)
                ->where('lessor_id', auth()->id())
                ->where('is_active', true)
                ->firstOrFail();

            // 🔥 ПРОВЕРКА СОВМЕСТИМОСТИ КАТЕГОРИЙ
            $requestCategoryIds = $rentalRequest->items->pluck('category_id')->toArray();
            if (!in_array($template->category_id, $requestCategoryIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Шаблон не подходит для категорий этой заявки',
                    'template_category' => $template->category_id,
                    'request_categories' => $requestCategoryIds
                ], 422);
            }

            // 🔥 ПРОВЕРКА ДОСТУПНОСТИ ОБОРУДОВАНИЯ
            $equipmentCheck = $this->checkEquipmentAvailability($rentalRequest, $template);
            if (!$equipmentCheck['available'] && $request->get('check_equipment', true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Оборудование недоступно для данной заявки',
                    'unavailable_items' => $equipmentCheck['unavailable_items'],
                    'details' => $equipmentCheck
                ], 422);
            }

            // 🔥 ПРИМЕНЕНИЕ ШАБЛОНА - СОЗДАНИЕ ПРЕДЛОЖЕНИЯ
            $customizations = $request->get('customizations', []);

            $proposalData = [
                'rental_request_id' => $rentalRequest->id,
                'lessor_id' => auth()->id(),
                'proposed_price' => $customizations['proposed_price'] ?? $template->proposed_price,
                'response_time' => $customizations['response_time'] ?? $template->response_time,
                'message' => $customizations['message'] ?? $template->message,
                'additional_terms' => $customizations['additional_terms'] ?? $template->additional_terms,
                'applied_template_id' => $template->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Создаем предложение
            $proposalId = DB::table('rental_request_responses')->insertGetId($proposalData);

            // 🔥 ОБНОВЛЯЕМ СТАТИСТИКУ ИСПОЛЬЗОВАНИЯ ШАБЛОНА
            $template->increment('usage_count');
            $template->last_used_at = now();
            $template->save();

            DB::commit();

            // Загружаем созданное предложение с отношениями
            $proposal = RentalRequestResponse::with(['rentalRequest', 'equipment'])->find($proposalId);

            return response()->json([
                'success' => true,
                'message' => 'Шаблон успешно применен к заявке',
                'data' => [
                    'proposal' => $proposal,
                    'template_used' => $template->name,
                    'equipment_available' => $equipmentCheck['available'],
                    'customizations_applied' => $customizations
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Заявка или шаблон не найдены'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Ошибка применения шаблона: ' . $e->getMessage(), [
                'rental_request_id' => $rentalRequestId,
                'user_id' => auth()->id(),
                'template_id' => $request->template_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка применения шаблона: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Проверка доступности оборудования для заявки по шаблону
     */
    private function checkEquipmentAvailability(RentalRequest $rentalRequest, ProposalTemplate $template)
    {
        try {
            $unavailableItems = [];

            // 🔥 ПОЛУЧАЕМ ДОСТУПНОЕ ОБОРУДОВАНИЕ ПО КАТЕГОРИИ ШАБЛОНА
            $availableEquipment = Equipment::where('category_id', $template->category_id)
                ->where('lessor_id', auth()->id())
                ->where('is_active', true)
                ->where('is_available', true)
                ->get();

            if ($availableEquipment->isEmpty()) {
                return [
                    'available' => false,
                    'unavailable_items' => [],
                    'message' => 'Нет доступного оборудования в категории шаблона',
                    'category_id' => $template->category_id
                ];
            }

            // 🔥 ПРОВЕРКА НАЛИЧИЯ ОБОРУДОВАНИЯ НА ПЕРИОД АРЕНДЫ
            // Здесь можно добавить сложную логику проверки занятости оборудования
            // на период $rentalRequest->rental_period_start - $rentalRequest->rental_period_end

            $available = $availableEquipment->isNotEmpty();

            return [
                'available' => $available,
                'unavailable_items' => $unavailableItems,
                'available_equipment_count' => $availableEquipment->count(),
                'message' => $available ?
                    'Оборудование доступно (' . $availableEquipment->count() . ' ед.)' :
                    'Оборудование недоступно',
                'category_id' => $template->category_id,
                'equipment_list' => $availableEquipment->pluck('name')
            ];

        } catch (\Exception $e) {
            \Log::error('Ошибка проверки оборудования: ' . $e->getMessage());

            return [
                'available' => false,
                'unavailable_items' => [],
                'message' => 'Ошибка при проверке доступности оборудования',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Предварительный просмотр применения шаблона
     */
    public function previewApplyTemplate(Request $request, $rentalRequestId, $templateId)
    {
        try {
            $rentalRequest = RentalRequest::with('items')->findOrFail($rentalRequestId);
            $template = ProposalTemplate::where('id', $templateId)
                ->where('lessor_id', auth()->id())
                ->firstOrFail();

            // Проверка совместимости категорий
            $requestCategoryIds = $rentalRequest->items->pluck('category_id')->toArray();
            $categoryCompatible = in_array($template->category_id, $requestCategoryIds);

            // Проверка доступности оборудования
            $equipmentCheck = $this->checkEquipmentAvailability($rentalRequest, $template);

            return response()->json([
                'success' => true,
                'data' => [
                    'template' => $template,
                    'rental_request' => $rentalRequest,
                    'compatibility' => [
                        'category_compatible' => $categoryCompatible,
                        'equipment_available' => $equipmentCheck['available'],
                        'available_equipment_count' => $equipmentCheck['available_equipment_count'] ?? 0
                    ],
                    'preview' => [
                        'proposed_price' => $template->proposed_price,
                        'response_time' => $template->response_time,
                        'message' => $template->message,
                        'additional_terms' => $template->additional_terms
                    ],
                    'equipment_check' => $equipmentCheck
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка предварительного просмотра: ' . $e->getMessage()
            ], 500);
        }
    }
}
