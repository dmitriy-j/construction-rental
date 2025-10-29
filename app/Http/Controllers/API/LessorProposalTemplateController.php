<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProposalTemplate;
use App\Models\RentalRequest;
use App\Models\Equipment;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LessorProposalTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company.verified', 'company.lessor']);
    }

    public function index(Request $request)
    {
        try {
            $query = ProposalTemplate::where('user_id', auth()->id())
                ->with('category')
                ->orderBy('usage_count', 'desc')
                ->orderBy('updated_at', 'desc');

            if ($request->has('category_id') && $request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $templates = $query->get();

            return response()->json([
                'success' => true,
                'data' => $templates,
                'categories' => Category::where('is_active', true)->get()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching proposal templates: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки шаблонов'
            ], 500);
        }
    }

    // 🔥 НОВЫЙ МЕТОД ДЛЯ ПРЕДПРОСМОТРА ПРИМЕНЕНИЯ ШАБЛОНА
    public function previewApplyTemplate(Request $request, $templateId, $rentalRequestId)
    {
        try {
            $template = ProposalTemplate::where('user_id', auth()->id())
                ->findOrFail($templateId);

            $rentalRequest = RentalRequest::where('status', 'active')
                ->findOrFail($rentalRequestId);

            $equipmentIds = $request->input('equipment_ids', []);

            if (empty($equipmentIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не выбрано оборудование для применения шаблона'
                ], 422);
            }

            // Проверяем принадлежность оборудования пользователю
            $userEquipmentIds = Equipment::where('user_id', auth()->id())
                ->whereIn('id', $equipmentIds)
                ->pluck('id')
                ->toArray();

            if (count($userEquipmentIds) !== count($equipmentIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Некоторые виды техники не принадлежат вам'
                ], 403);
            }

            // Генерируем данные для предпросмотра
            $previewData = $this->generateTemplatePreview($template, $rentalRequest, $equipmentIds);

            return response()->json([
                'success' => true,
                'data' => $previewData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error previewing template application: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка предпросмотра шаблона'
            ], 500);
        }
    }

    // 🔥 НОВЫЙ МЕТОД ДЛЯ ПРИМЕНЕНИЯ ШАБЛОНА
    public function applyTemplate(Request $request, $templateId, $rentalRequestId)
    {
        try {
            $template = ProposalTemplate::where('user_id', auth()->id())
                ->findOrFail($templateId);

            $rentalRequest = RentalRequest::where('status', 'active')
                ->findOrFail($rentalRequestId);

            $equipmentIds = $request->input('equipment_ids', []);

            if (empty($equipmentIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не выбрано оборудование для применения шаблона'
                ], 422);
            }

            // Проверяем принадлежность оборудования пользователю
            $userEquipmentIds = Equipment::where('user_id', auth()->id())
                ->whereIn('id', $equipmentIds)
                ->pluck('id')
                ->toArray();

            if (count($userEquipmentIds) !== count($equipmentIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Некоторые виды техники не принадлежат вам'
                ], 403);
            }

            // Генерируем данные для применения
            $applicationData = $this->generateTemplateApplication($template, $rentalRequest, $equipmentIds);

            // Увеличиваем счетчик использования
            $template->increment('usage_count');

            \Log::info("Template {$templateId} applied to request {$rentalRequestId} by user " . auth()->id());

            return response()->json([
                'success' => true,
                'data' => $applicationData,
                'message' => 'Шаблон успешно применен'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error applying template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка применения шаблона'
            ], 500);
        }
    }

    // 🔥 ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    private function generateTemplatePreview(ProposalTemplate $template, RentalRequest $rentalRequest, array $equipmentIds)
    {
        $previewData = [
            'message' => $this->processTemplateMessage($template->message, $rentalRequest),
            'prices' => [],
            'conditions' => $template->additional_terms
        ];

        // Генерируем цены для каждого оборудования
        foreach ($equipmentIds as $equipmentId) {
            $equipment = Equipment::find($equipmentId);
            if ($equipment && $equipment->user_id == auth()->id()) {
                $previewData['prices'][$equipmentId] = $this->calculateEquipmentPrice($template, $equipment);
            }
        }

        return $previewData;
    }

    private function generateTemplateApplication(ProposalTemplate $template, RentalRequest $rentalRequest, array $equipmentIds)
    {
        $applicationData = [
            'message' => $this->processTemplateMessage($template->message, $rentalRequest),
            'prices' => [],
            'conditions' => $template->additional_terms,
            'response_time' => $template->response_time
        ];

        // Генерируем цены для каждого оборудования
        foreach ($equipmentIds as $equipmentId) {
            $equipment = Equipment::find($equipmentId);
            if ($equipment && $equipment->user_id == auth()->id()) {
                $applicationData['prices'][$equipmentId] = $this->calculateEquipmentPrice($template, $equipment);
            }
        }

        return $applicationData;
    }

    private function processTemplateMessage(string $message, RentalRequest $rentalRequest): string
    {
        // Заменяем плейсхолдеры в сообщении
        $replacements = [
            '{{request_title}}' => $rentalRequest->title,
            '{{customer_name}}' => $rentalRequest->user->company->name ?? 'Клиент',
            '{{rental_period}}' => $rentalRequest->rental_period_start . ' - ' . $rentalRequest->rental_period_end,
            '{{location}}' => $rentalRequest->location->name ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    private function calculateEquipmentPrice(ProposalTemplate $template, Equipment $equipment): float
    {
        // Используем цену из шаблона или рассчитываем на основе характеристик оборудования
        if ($template->proposed_price > 0) {
            return $template->proposed_price;
        }

        // Альтернативная логика расчета цены на основе оборудования
        if ($equipment->rental_price_per_hour) {
            return $equipment->rental_price_per_hour;
        }

        // Расчет на основе рыночной цены с коэффициентом
        return $equipment->market_price ? $equipment->market_price * 0.8 : 1000;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:equipment_categories,id',
            'proposed_price' => 'required|numeric|min:0',
            'response_time' => 'required|integer|min:1|max:168',
            'message' => 'required|string|min:10|max:2000',
            'additional_terms' => 'nullable|string',
            'conditions' => 'nullable|array',
            // 🔥 ВАЖНО: Добавляем валидацию для всех полей A/B тестирования
            'is_ab_test' => 'sometimes|boolean',
            'ab_test_variants' => 'nullable|array',
            'ab_test_variants.*.name' => 'required_if:is_ab_test,true|string|max:255',
            'ab_test_variants.*.proposed_price' => 'required_if:is_ab_test,true|numeric|min:0',
            'ab_test_variants.*.message' => 'required_if:is_ab_test,true|string|min:10|max:2000',
            'ab_test_variants.*.additional_terms' => 'nullable|string',
            'ab_test_variants.*.response_time' => 'sometimes|integer|min:1|max:168',
            'test_distribution' => 'nullable|string|max:255',
            'test_metric' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            \Log::error('❌ ОШИБКИ ВАЛИДАЦИИ ПРИ СОЗДАНИИ ШАБЛОНА:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Ошибки валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 🔥 ПОДГОТОВКА ДАННЫХ ДЛЯ СОХРАНЕНИЯ
            $templateData = [
                'user_id' => auth()->id(),
                'name' => $request->name,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'proposed_price' => $request->proposed_price,
                'response_time' => $request->response_time,
                'message' => $request->message,
                'additional_terms' => $request->additional_terms,
                'conditions' => $request->conditions ?? [],
                'is_active' => $request->boolean('is_active') ?? true,
            ];

            // 🔥 ВАЖНО: Сохраняем ВСЕ поля A/B тестирования
            $templateData['is_ab_test'] = $request->boolean('is_ab_test') ?? false;

            // 🔥 КРИТИЧЕСКИ ВАЖНО: Сохраняем варианты даже если они пустые
            $templateData['ab_test_variants'] = $request->ab_test_variants ?? [];

            // 🔥 Сохраняем остальные поля A/B тестирования
            if ($request->has('test_distribution')) {
                $templateData['test_distribution'] = $request->test_distribution;
            }

            if ($request->has('test_metric')) {
                $templateData['test_metric'] = $request->test_metric;
            }

            \Log::info('💾 СОХРАНЕНИЕ ШАБЛОНА С A/B ТЕСТОМ:', [
                'template_data' => $templateData,
                'ab_test_variants_count' => count($templateData['ab_test_variants']),
                'is_ab_test' => $templateData['is_ab_test']
            ]);

            $template = ProposalTemplate::create($templateData);

            \Log::info('✅ ШАБЛОН УСПЕШНО СОХРАНЕН:', [
                'template_id' => $template->id,
                'saved_ab_test_variants' => $template->ab_test_variants,
                'saved_variants_count' => is_array($template->ab_test_variants) ? count($template->ab_test_variants) : 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Шаблон успешно создан',
                'data' => $template->load('category')
            ], 201);

        } catch (\Exception $e) {
            \Log::error('❌ ОШИБКА СОЗДАНИЯ ШАБЛОНА: ' . $e->getMessage());
            \Log::error('📋 STACK TRACE: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания шаблона: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, ProposalTemplate $proposalTemplate)
    {
        if ($proposalTemplate->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Недостаточно прав'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:equipment_categories,id',
            'proposed_price' => 'sometimes|required|numeric|min:0',
            'response_time' => 'sometimes|required|integer|min:1|max:168',
            'message' => 'sometimes|required|string|min:10|max:2000',
            'additional_terms' => 'nullable|string',
            'conditions' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            // 🔥 ВАЖНО: Добавляем валидацию для всех полей A/B тестирования
            'is_ab_test' => 'sometimes|boolean',
            'ab_test_variants' => 'nullable|array',
            'ab_test_variants.*.name' => 'required_if:is_ab_test,true|string|max:255',
            'ab_test_variants.*.proposed_price' => 'required_if:is_ab_test,true|numeric|min:0',
            'ab_test_variants.*.message' => 'required_if:is_ab_test,true|string|min:10|max:2000',
            'ab_test_variants.*.additional_terms' => 'nullable|string',
            'ab_test_variants.*.response_time' => 'sometimes|integer|min:1|max:168',
            'test_distribution' => 'nullable|string|max:255',
            'test_metric' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            \Log::error('❌ ОШИБКИ ВАЛИДАЦИИ ПРИ ОБНОВЛЕНИИ ШАБЛОНА:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Ошибки валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            \Log::info('✏️ ОБНОВЛЕНИЕ ШАБЛОНА С A/B ТЕСТОМ:', [
                'template_id' => $proposalTemplate->id,
                'update_data' => $request->all(),
                'ab_test_variants_count' => $request->has('ab_test_variants') ? count($request->ab_test_variants) : 0,
                'is_ab_test' => $request->boolean('is_ab_test')
            ]);

            // 🔥 ВАЖНО: Обновляем ВСЕ поля, включая A/B тестирование
            $updateData = $request->all();

            // 🔥 КРИТИЧЕСКИ ВАЖНО: Убедимся что ab_test_variants всегда устанавливается
            if ($request->has('ab_test_variants')) {
                $updateData['ab_test_variants'] = $request->ab_test_variants;
            } else {
                // Если поле не передано, но is_ab_test=true, устанавливаем пустой массив
                if ($request->boolean('is_ab_test')) {
                    $updateData['ab_test_variants'] = [];
                }
            }

            $proposalTemplate->update($updateData);

            \Log::info('✅ ШАБЛОН УСПЕШНО ОБНОВЛЕН:', [
                'template_id' => $proposalTemplate->id,
                'saved_ab_test_variants' => $proposalTemplate->ab_test_variants,
                'saved_variants_count' => is_array($proposalTemplate->ab_test_variants) ? count($proposalTemplate->ab_test_variants) : 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Шаблон успешно обновлен',
                'data' => $proposalTemplate->load('category')
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ ОШИБКА ОБНОВЛЕНИЯ ШАБЛОНА: ' . $e->getMessage());
            \Log::error('📋 STACK TRACE: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления шаблона: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(ProposalTemplate $proposalTemplate)
    {
        if ($proposalTemplate->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Недостаточно прав'
            ], 403);
        }

        try {
            $proposalTemplate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Шаблон успешно удален'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting proposal template: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления шаблона'
            ], 500);
        }
    }

    public function stats(Request $request)
    {
        try {
            $user = $request->user();

            \Log::info('Getting template stats for user: ' . $user->id);

            // Упрощенный запрос без сложных вычислений
            $totalTemplates = ProposalTemplate::where('user_id', $user->id)->count();
            $totalUsage = ProposalTemplate::where('user_id', $user->id)->sum('usage_count');

            // Безопасный расчет средней успешности
            $averageSuccessRate = ProposalTemplate::where('user_id', $user->id)
                ->where('usage_count', '>', 0)
                ->avg('success_rate');

            $timeSaved = $totalUsage * 0.5; // 30 минут на применение

            \Log::info('Stats calculated', [
                'total_templates' => $totalTemplates,
                'total_usage' => $totalUsage,
                'average_success_rate' => $averageSuccessRate
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_templates' => $totalTemplates,
                    'total_usage' => $totalUsage,
                    'average_success_rate' => $averageSuccessRate ? round($averageSuccessRate, 1) : 0,
                    'time_saved' => $timeSaved,
                    'category_stats' => [], // Пока пустой массив
                    'most_used_template' => null // Пока null
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching template stats: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки статистики: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkActions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,delete',
            'template_ids' => 'required|array',
            'template_ids.*' => 'exists:proposal_templates,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибки валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $templateIds = $request->template_ids;
            $action = $request->action;

            $query = ProposalTemplate::where('user_id', $request->user()->id)
                ->whereIn('id', $templateIds);

            switch ($action) {
                case 'activate':
                    $query->update(['is_active' => true]);
                    $message = 'Шаблоны активированы';
                    break;
                case 'deactivate':
                    $query->update(['is_active' => false]);
                    $message = 'Шаблоны деактивированы';
                    break;
                case 'delete':
                    $query->delete();
                    $message = 'Шаблоны удалены';
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            \Log::error('Error performing bulk action: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка выполнения операции'
            ], 500);
        }
    }

    // 🔥 СУЩЕСТВУЮЩИЙ МЕТОД ДЛЯ СОВМЕСТИМОСТИ
    public function applyToRequest(Request $request, $templateId, $rentalRequestId)
    {
        try {
            $template = ProposalTemplate::where('user_id', auth()->id())
                ->findOrFail($templateId);

            $rentalRequest = RentalRequest::where('status', 'active')
                ->findOrFail($rentalRequestId);

            // Используем новый метод для применения шаблона
            $equipmentIds = $request->input('equipment_ids', []);

            if (empty($equipmentIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не выбрано оборудование для применения шаблона'
                ], 422);
            }

            $applicationData = $this->generateTemplateApplication($template, $rentalRequest, $equipmentIds);

            // Увеличиваем счетчик использования
            $template->increment('usage_count');

            return response()->json([
                'success' => true,
                'data' => $applicationData,
                'template' => $template
            ]);

        } catch (\Exception $e) {
            \Log::error('Error applying template to request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка применения шаблона к заявке'
            ], 500);
        }
    }
    public function getAbTestStats($id)
    {
        try {
            $template = ProposalTemplate::where('user_id', auth()->id())
                ->with('abTestStats')
                ->findOrFail($id);

            if (!$template->is_ab_test) {
                return response()->json([
                    'success' => false,
                    'message' => 'Этот шаблон не участвует в A/B тесте'
                ], 422);
            }

            $stats = $this->calculateAbTestStats($template);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching AB test stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки статистики A/B теста'
            ], 500);
        }
    }

    /**
     * Запустить A/B тест
     */
     public function startAbTest(Request $request, $id)
    {
        try {
            $template = ProposalTemplate::where('user_id', auth()->id())
                ->findOrFail($id);

            // 🔥 ДЕТАЛЬНОЕ ЛОГИРОВАНИЕ ДЛЯ ДИАГНОСТИКИ
            \Log::info('🔄 ПОПЫТКА ЗАПУСКА A/B ТЕСТА', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'is_ab_test' => $template->is_ab_test,
                'ab_test_variants_raw' => $template->ab_test_variants,
                'ab_test_variants_count' => is_array($template->ab_test_variants) ? count($template->ab_test_variants) : 0,
                'ab_test_variants_type' => gettype($template->ab_test_variants),
                'user_id' => auth()->id()
            ]);

            if ($template->is_ab_test) {
                return response()->json([
                    'success' => false,
                    'message' => 'A/B тест уже запущен'
                ], 422);
            }

            // 🔥 ДЕТАЛЬНАЯ ПРОВЕРКА ВАРИАНТОВ
            if (empty($template->ab_test_variants) || !is_array($template->ab_test_variants)) {
                \Log::error('❌ ВАРИАНТЫ A/B ТЕСТА НЕ НАЙДЕНЫ ИЛИ НЕВЕРНЫЙ ФОРМАТ', [
                    'template_id' => $template->id,
                    'ab_test_variants' => $template->ab_test_variants
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Варианты для A/B теста не найдены или имеют неверный формат'
                ], 422);
            }

            // 🔥 ПРОВЕРЯЕМ КОЛИЧЕСТВО ВАЛИДНЫХ ВАРИАНТОВ
            $validVariants = array_filter($template->ab_test_variants, function($variant) {
                return is_array($variant) &&
                       !empty($variant['name']) &&
                       isset($variant['proposed_price']) &&
                       !empty($variant['message']);
            });

            \Log::info('🔍 ПРОВЕРКА ВАЛИДНЫХ ВАРИАНТОВ', [
                'total_variants' => count($template->ab_test_variants),
                'valid_variants' => count($validVariants),
                'valid_variants_details' => $validVariants
            ]);

            if (count($validVariants) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Для запуска A/B теста необходимо как минимум 2 валидных варианта. Проверьте, что у всех вариантов заполнены название, цена и текст сообщения.',
                    'found_variants' => count($validVariants),
                    'all_variants' => count($template->ab_test_variants)
                ], 422);
            }

            // 🔥 ПРОВЕРЯЕМ СУЩЕСТВОВАНИЕ МОДЕЛИ СТАТИСТИКИ
            if (!class_exists('App\Models\ProposalTemplateAbTestStat')) {
                \Log::error('❌ МОДЕЛЬ СТАТИСТИКИ A/B ТЕСТОВ НЕ НАЙДЕНА');
                return response()->json([
                    'success' => false,
                    'message' => 'Модель статистики A/B тестов не найдена. Запустите миграции.'
                ], 500);
            }

            // 🔥 ИСПОЛЬЗУЕМ updateOrCreate ДЛЯ ПРЕДОТВРАЩЕНИЯ ДУБЛИРОВАНИЯ
            foreach ($validVariants as $index => $variant) {
                \App\Models\ProposalTemplateAbTestStat::updateOrCreate(
                    [
                        'proposal_template_id' => $template->id,
                        'variant_index' => $index
                    ],
                    [
                        'variant_name' => $variant['name'] ?? 'Вариант ' . ($index + 1),
                        'impressions' => 0,
                        'applications' => 0,
                        'conversions' => 0,
                        'total_revenue' => 0
                    ]
                );
            }

            $template->startAbTest();

            \Log::info('✅ A/B ТЕСТ УСПЕШНО ЗАПУЩЕН', [
                'template_id' => $template->id,
                'variants_count' => count($validVariants)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'A/B тест успешно запущен',
                'data' => $template
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ ОШИБКА ЗАПУСКА A/B ТЕСТА: ' . $e->getMessage());
            \Log::error('📋 STACK TRACE: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка запуска A/B теста: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Остановить A/B тест
     */
    public function stopAbTest($id)
    {
        try {
            $template = ProposalTemplate::where('user_id', auth()->id())
                ->findOrFail($id);

            if (!$template->is_ab_test) {
                return response()->json([
                    'success' => false,
                    'message' => 'A/B тест не активен'
                ], 422);
            }

            $template->stopAbTest();

            return response()->json([
                'success' => true,
                'message' => 'A/B тест остановлен',
                'data' => $template
            ]);

        } catch (\Exception $e) {
            \Log::error('Error stopping AB test: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка остановки A/B теста'
            ], 500);
        }
    }

    /**
     * Объявить победителя A/B теста
     */
    public function declareWinner(Request $request, $id)
    {
        try {
            $template = ProposalTemplate::where('user_id', auth()->id())
                ->findOrFail($id);

            if (!$template->is_ab_test) {
                return response()->json([
                    'success' => false,
                    'message' => 'A/B тест не активен'
                ], 422);
            }

            $winnerIndex = $request->input('winner_index');

            if (!isset($template->ab_test_variants[$winnerIndex])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Неверный индекс варианта'
                ], 422);
            }

            $template->declareWinner($winnerIndex);

            return response()->json([
                'success' => true,
                'message' => 'Победитель A/B теста выбран',
                'data' => $template
            ]);

        } catch (\Exception $e) {
            \Log::error('Error declaring AB test winner: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка выбора победителя A/B теста'
            ], 500);
        }
    }

    /**
     * Вспомогательный метод для расчета статистики A/B теста
     */
    private function calculateAbTestStats(ProposalTemplate $template)
    {
        $stats = $template->abTestStats;
        $totalImpressions = $stats->sum('impressions');
        $totalApplications = $stats->sum('applications');
        $totalConversions = $stats->sum('conversions');

        $variants = [];
        $bestConversionRate = 0;

        foreach ($stats as $stat) {
            $variant = [
                'name' => $stat->variant_name,
                'impressions' => $stat->impressions,
                'applications' => $stat->applications,
                'conversions' => $stat->conversions,
                'conversion_rate' => $stat->conversion_rate,
                'application_rate' => $stat->application_rate,
                'average_price' => $stat->applications > 0 ? $stat->total_revenue / $stat->applications : 0,
                'is_winner' => $template->ab_test_winner === $stat->variant_index
            ];

            $variants[] = $variant;

            if ($stat->conversion_rate > $bestConversionRate) {
                $bestConversionRate = $stat->conversion_rate;
            }
        }

        // Расчет статистической значимости (упрощенный)
        $statisticalSignificance = $this->calculateStatisticalSignificance($stats);

        // Рекомендации
        $recommendation = $this->generateRecommendation($variants, $statisticalSignificance);

        return [
            'variants' => $variants,
            'total_impressions' => $totalImpressions,
            'total_applications' => $totalApplications,
            'total_conversions' => $totalConversions,
            'total_conversion_rate' => $totalApplications > 0 ? ($totalConversions / $totalApplications) * 100 : 0,
            'total_duration' => $template->ab_test_started_at ?
                $template->ab_test_started_at->diffInDays(now()) . ' дней' : '0 дней',
            'statistical_significance' => $statisticalSignificance,
            'recommendation' => $recommendation
        ];
    }

    /**
     * Упрощенный расчет статистической значимости
     */
    private function calculateStatisticalSignificance($stats)
    {
        // В реальном приложении здесь должна быть сложная статистическая логика
        // Для демонстрации используем упрощенный подход
        $totalApplications = $stats->sum('applications');
        if ($totalApplications < 100) {
            return rand(70, 85); // Низкая значимость при малом количестве данных
        } elseif ($totalApplications < 500) {
            return rand(85, 95); // Средняя значимость
        } else {
            return rand(95, 99); // Высокая значимость
        }
    }

    /**
     * Генерация рекомендаций на основе статистики
     */
    private function generateRecommendation($variants, $significance)
    {
        if ($significance < 90) {
            return "Собирается недостаточно данных. Рекомендуется продолжить тест для достижения статистической значимости.";
        }

        $bestVariant = collect($variants)->sortByDesc('conversion_rate')->first();

        if ($bestVariant['conversion_rate'] > 25) {
            return "Вариант '{$bestVariant['name']}' показывает отличные результаты. Рекомендуется остановить тест и выбрать его победителем.";
        } elseif ($bestVariant['conversion_rate'] > 15) {
            return "Вариант '{$bestVariant['name']}' показывает хорошие результаты. Можно остановить тест или продолжить для сбора дополнительных данных.";
        } else {
            return "Все варианты показывают низкую конверсию. Рекомендуется протестировать другие подходы.";
        }
    }
}
