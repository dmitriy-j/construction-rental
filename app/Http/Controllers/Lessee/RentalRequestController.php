<?php

namespace App\Http\Controllers\Lessee;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\Category;
use App\Models\Location;
use App\Services\RentalRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreRentalRequestRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RentalRequestController extends Controller
{
    public function __construct(private RentalRequestService $rentalRequestService)
    {
        $this->middleware('auth');
        $this->middleware('company.verified');
        $this->middleware('company.lessee');
    }

    public function create()
    {
        // Упрощенный запрос - только категории с детьми
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->get();

        $locations = Location::where('company_id', auth()->user()->company_id)->get();

        Log::debug('Rental request create page data:', [
            'categories_count' => $categories->count(),
            'locations_count' => $locations->count(),
            'categories' => $categories->pluck('name')->toArray()
        ]);

        return view('lessee.rental_requests.create', compact('categories', 'locations'));
    }

    public function store(StoreRentalRequestRequest $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // 🔥 ДЕТАЛЬНАЯ ПРОВЕРКА ДОСТАВКИ
            Log::debug('🚚 STORE METHOD - DELIVERY DATA', [
                'delivery_required' => $validated['delivery_required'] ?? 'not_set',
                'delivery_required_type' => isset($validated['delivery_required']) ? gettype($validated['delivery_required']) : 'not_set',
                'delivery_required_value' => $validated['delivery_required'] ?? null,
                'all_validated_keys' => array_keys($validated)
            ]);

            $rentalRequest = $this->rentalRequestService->createRentalRequest(
                $validated,
                auth()->user()
            );

            // 🔥 ПРОВЕРКА СОХРАНЕННЫХ ДАННЫХ
            Log::debug('✅ STORE METHOD - DELIVERY SAVED', [
                'request_id' => $rentalRequest->id,
                'delivery_required_saved' => $rentalRequest->delivery_required,
                'delivery_required_type' => gettype($rentalRequest->delivery_required),
                'delivery_required_in_db' => $rentalRequest->getRawOriginal('delivery_required')
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Заявка успешно создана',
                'redirect_url' => route('lessee.rental-requests.show', $rentalRequest->id),
                'request_id' => $rentalRequest->id,
                'data' => [
                    'delivery_required' => $rentalRequest->delivery_required
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ STORE METHOD - ERROR: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании заявки: ' . $e->getMessage()
            ], 500);
        }
    }

    // ⚠️ УДАЛЕНЫ МЕТОДЫ calculateBudget И calculateBudgetFromItems - они принадлежат модели или сервису

    public function index(Request $request)
    {
        $status = $request->get('status', 'active');
        $perPage = $request->get('per_page', 15);

        $requests = RentalRequest::with([
            'category' => function($query) {
                $query->withDefault([
                    'name' => 'Категория удалена'
                ]);
            },
            'location',
            'responses.equipment',
            'responses.lessor.company'
        ])
        ->where('user_id', auth()->id())
        ->when($status !== 'all', function ($query) use ($status) {
            $query->where('status', $status);
        })
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);

        // Добавляем подсчет количества позиций для каждой заявки
        $requests->getCollection()->transform(function ($rentalRequest) {
            $rentalRequest->items_count = $rentalRequest->items->count() ?? 1;

            if (!$rentalRequest->category) {
                $rentalRequest->setRelation('category', new \App\Models\Category([
                    'name' => 'Категория не указана'
                ]));
            }
            return $rentalRequest;
        });

        $stats = $this->getRequestStats(auth()->id());

        $totalItemsCount = $requests->sum('items_count');
        $totalProposalsCount = $requests->sum('responses_count');

        return view('lessee.rental_requests.index', compact(
            'requests',
            'status',
            'stats',
            'totalItemsCount',
            'totalProposalsCount'
        ));
    }

    /**
     * Просмотр конкретной заявки
     */
    public function show($id)
    {
        $rentalRequest = RentalRequest::with(['items.category', 'location'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        // Преобразуем спецификации в унифицированный формат для фронтенда
        $rentalRequest->items->each(function ($item) {
            $item->unified_specifications = $item->unified_specifications;
        });

        return view('lessee.rental_requests.show', compact('rentalRequest'));
    }

    public function edit($id)
    {
        Log::debug('=== EDIT METHOD with new structure ===', [
            'id' => $id,
            'user_id' => auth()->id()
        ]);

        try {
            $rentalRequest = RentalRequest::with(['items.category', 'location'])
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            // Преобразуем спецификации для фронтенда
            $rentalRequest->items->each(function ($item) {
                $item->specifications = $item->unified_specifications;
            });

            Log::debug('Rental request found for edit:', [
                'id' => $rentalRequest->id,
                'title' => $rentalRequest->title,
                'items_count' => $rentalRequest->items->count(),
                'first_item_specs' => $rentalRequest->items->first()->specifications ?? 'none',
                'delivery_required' => $rentalRequest->delivery_required // 🔥 ДОБАВЛЕНО
            ]);

            $categories = Category::with('children')
                ->whereNull('parent_id')
                ->get();

            $locations = Location::where('company_id', auth()->user()->company_id)->get();

            return view('lessee.rental_requests.edit-vue', compact(
                'rentalRequest',
                'categories',
                'locations'
            ));

        } catch (\Exception $e) {
            Log::error('Error in edit method:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(404, 'Заявка не найдена или у вас нет прав для ее редактирования');
        }
    }

     /**
     * Принятие предложения по заявке
     */
    public function acceptProposal(Request $request, $requestId, $proposalId): JsonResponse
    {
        try {
            $proposal = \App\Models\RentalRequestResponse::where('rental_request_id', $requestId)
                ->where('id', $proposalId)
                ->firstOrFail();

            $proposalService = app(\App\Services\ProposalManagementService::class);
            $proposalService->acceptProposal($proposal);

            return response()->json([
                'success' => true,
                'message' => 'Предложение принято. Заказ создан.',
                'order_id' => $proposal->fresh()->order_id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            // ✅ ДОБАВЛЕНА ПРЕДВАРИТЕЛЬНАЯ ОБРАБОТКА ДАННЫХ
            $processedRequest = $this->preprocessRequestData($request);

            Log::debug('=== UPDATE METHOD START with IMPROVED specs structure ===', [
                'id' => $id,
                'user_id' => auth()->id(),
                'items_count' => count($processedRequest->items ?? []),
                'first_item_full_data' => $processedRequest->items[0] ?? 'no items',
                'has_standard_specs' => !empty($processedRequest->items[0]['standard_specifications'] ?? []),
                'has_custom_specs' => !empty($processedRequest->items[0]['custom_specifications'] ?? []),
                'standard_specs_structure' => $processedRequest->items[0]['standard_specifications'] ?? [],
                'custom_specs_structure' => $processedRequest->items[0]['custom_specifications'] ?? [],
                'delivery_required' => $processedRequest->delivery_required ?? false // 🔥 ДОБАВЛЕНО
            ]);

            $rentalRequest = RentalRequest::where('user_id', auth()->id())
                ->findOrFail($id);

            // Валидация с поддержкой новой структуры
            $validated = $processedRequest->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'hourly_rate' => 'required|numeric|min:0',
                'rental_period_start' => 'required|date',
                'rental_period_end' => 'required|date|after_or_equal:rental_period_start',
                'location_id' => 'required|exists:locations,id',
                'rental_conditions' => 'sometimes|array',
                'delivery_required' => 'sometimes|boolean', // 🔥 ДОБАВЛЕНО
                'items' => 'required|array|min:1',
                'items.*.category_id' => 'required|exists:equipment_categories,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.hourly_rate' => 'sometimes|numeric|min:0',

                // ✅ УЛУЧШЕННАЯ СТРУКТУРА С NULLABLE UNIT
                'items.*.standard_specifications' => 'sometimes|array',
                'items.*.standard_specifications.*' => 'nullable',

                'items.*.custom_specifications' => 'sometimes|array',
                'items.*.custom_specifications.*' => 'sometimes|array',
                'items.*.custom_specifications.*.label' => 'required_with:items.*.custom_specifications.*|string|max:255',
                'items.*.custom_specifications.*.value' => 'required_with:items.*.custom_specifications.*',
                'items.*.custom_specifications.*.unit' => 'nullable|string|max:50', // ✅ ИЗМЕНЕНИЕ: nullable вместо sometimes
                'items.*.custom_specifications.*.dataType' => 'sometimes|in:string,number',

                'items.*.custom_specs_metadata' => 'sometimes|array',
                'items.*.use_individual_conditions' => 'sometimes|boolean',
                'items.*.individual_conditions' => 'sometimes|array'
            ]);

            Log::debug('✅ IMPROVED Validated data for update:', [
                'items_count' => count($validated['items']),
                'first_item_category' => $validated['items'][0]['category_id'] ?? 'unknown',
                'first_item_standard_specs' => $validated['items'][0]['standard_specifications'] ?? [],
                'first_item_custom_specs' => $validated['items'][0]['custom_specifications'] ?? [],
                'first_item_custom_specs_count' => count($validated['items'][0]['custom_specifications'] ?? []),
                'delivery_required' => $validated['delivery_required'] ?? false // 🔥 ДОБАВЛЕНО
            ]);

            // Обновление заявки через сервис
            $updatedRequest = $this->rentalRequestService->updateRentalRequest($rentalRequest, $validated);

            DB::commit();

            Log::debug('✅ IMPROVED Rental request updated successfully', [
                'request_id' => $updatedRequest->id,
                'items_count' => $updatedRequest->items->count(),
                'first_item_id' => $updatedRequest->items->first()->id ?? 'none',
                'first_item_standard_specs' => $updatedRequest->items->first()->standard_specifications ?? 'none',
                'first_item_custom_specs' => $updatedRequest->items->first()->custom_specifications ?? 'none',
                'first_item_legacy_specs' => $updatedRequest->items->first()->specifications ?? 'none',
                'delivery_required' => $updatedRequest->delivery_required // 🔥 ДОБАВЛЕНО
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Заявка успешно обновлена'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ IMPROVED Error updating rental request: ' . $e->getMessage(), [
                'request_id' => $id,
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'validation_errors' => $e instanceof \Illuminate\Validation\ValidationException ? $e->errors() : 'not validation error',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении заявки: ' . $e->getMessage(),
                'errors' => $e instanceof \Illuminate\Validation\ValidationException ? $e->errors() : []
            ], 500);
        }
    }

    /**
     * ✅ НОВЫЙ МЕТОД: Предварительная обработка данных запроса
     * Гарантирует что все unit поля будут строками (не null)
     */
     private function preprocessRequestData(Request $request): Request
    {
        $items = $request->input('items', []);

        $processedItems = [];
        foreach ($items as $itemIndex => $itemData) {
            $processedItem = $itemData;

            // Обработка кастомных спецификаций - гарантируем что unit всегда строка
            if (isset($processedItem['custom_specifications'])) {
                foreach ($processedItem['custom_specifications'] as $specKey => &$customSpec) {
                    if (is_array($customSpec) && array_key_exists('unit', $customSpec)) {
                        // Преобразуем null в пустую строку
                        if ($customSpec['unit'] === null) {
                            $customSpec['unit'] = '';
                            Log::debug("🔄 Преобразован null unit в пустую строку", [
                                'item_index' => $itemIndex,
                                'spec_key' => $specKey
                            ]);
                        }
                    }
                }
            }

            $processedItems[] = $processedItem;
        }

        // Создаем новый Request с обработанными данными
        $processedData = $request->all();
        $processedData['items'] = $processedItems;

        // 🔥 ОБЕСПЕЧИВАЕМ BOOLEAN ДЛЯ delivery_required
        if (array_key_exists('delivery_required', $processedData)) {
            $processedData['delivery_required'] = (bool)$processedData['delivery_required'];
        } else {
            $processedData['delivery_required'] = false;
        }

        // Создаем новый Request объект с обработанными данными
        $newRequest = new Request($processedData);
        $newRequest->setJson(new \Illuminate\Http\JsonResponse($processedData));

        return $newRequest;
    }

    /**
     * Статистика по заявкам
     */
    private function getRequestStats($userId): array
    {
        return [
            'total' => RentalRequest::where('user_id', $userId)->count(),
            'active' => RentalRequest::where('user_id', $userId)->where('status', 'active')->count(),
            'processing' => RentalRequest::where('user_id', $userId)->where('status', 'processing')->count(),
            'completed' => RentalRequest::where('user_id', $userId)->where('status', 'completed')->count(),
            'cancelled' => RentalRequest::where('user_id', $userId)->where('status', 'cancelled')->count(),
        ];
    }

    /**
     * Экспорт заявки в PDF
     */
    public function exportPDF($id)
    {
        try {
            Log::info('PDF Export Started', ['request_id' => $id, 'user_id' => auth()->id()]);

            // Загружаем данные с правильными отношениями
            $rentalRequest = RentalRequest::with([
                'items.category',
                'location',
                'user.company'
            ])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

            if ($rentalRequest->items->isEmpty()) {
                Log::warning('PDF Export: No items found', ['request_id' => $id]);
                return response()->json([
                    'error' => 'No items found for export'
                ], 404);
            }

            // 🔥 ДЕБАГ: Логируем данные заявки
            Log::debug('PDF Export - Rental Request Data', [
                'request_id' => $rentalRequest->id,
                'title' => $rentalRequest->title,
                'created_at' => $rentalRequest->created_at,
                'location' => $rentalRequest->location?->name,
                'items_count' => $rentalRequest->items->count(),
                'status_text' => $rentalRequest->status_text,
                'delivery_required' => $rentalRequest->delivery_required // 🔥 ДОБАВЛЕНО
            ]);

            // Используем сервис для форматирования спецификаций
            $rentalRequestService = app(\App\Services\RentalRequestService::class);

            // Форматируем спецификации для каждого item
            foreach ($rentalRequest->items as $item) {
                if (empty($item->formatted_specifications) && !empty($item->specifications)) {
                    Log::debug('🔧 PDF: Formatting specifications for item', [
                        'item_id' => $item->id,
                        'specifications_type' => gettype($item->specifications)
                    ]);

                    // Передаем метаданные для кастомных спецификаций
                    $item->formatted_specifications = $rentalRequestService->formatSpecifications(
                        $item->specifications,
                        $item->custom_specs_metadata ?? []
                    );

                    Log::debug('✅ PDF: Specifications formatted', [
                        'item_id' => $item->id,
                        'formatted_count' => count($item->formatted_specifications)
                    ]);
                }
            }

            // 🔥 ПРАВИЛЬНАЯ ПЕРЕДАЧА ДАННЫХ В ШАБЛОН
            $data = [
                'rentalRequest' => $rentalRequest,
                'items' => $rentalRequest->items,
                'user' => $rentalRequest->user ?? auth()->user(), // Используем user из заявки или текущего
                'exportDate' => now()->format('d.m.Y H:i'),
            ];

            // 🔥 ДЕБАГ: Проверяем что передаем в шаблон
            Log::debug('PDF Export - Template Data', [
                'rental_request_id' => $data['rentalRequest']->id,
                'rental_request_created_at' => $data['rentalRequest']->created_at?->format('d.m.Y'),
                'user_name' => $data['user']->name,
                'items_count' => $data['items']->count(),
                'delivery_required' => $data['rentalRequest']->delivery_required // 🔥 ДОБАВЛЕНО
            ]);

            // Генерируем PDF
            $pdf = PDF::loadView('lessee.rental_requests.pdf', $data);

            // Настройки PDF для поддержки кириллицы
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('enable_html5_parser', true);
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->setOption('defaultFont', 'DejaVu Sans');
            $pdf->setOption('dpi', 96);
            $pdf->setOption('isPhpEnabled', true);
            $pdf->setOption('isFontSubsettingEnabled', true);

            $pdfContent = $pdf->output();

            Log::info('PDF Generated Successfully', [
                'request_id' => $id,
                'file_size' => strlen($pdfContent)
            ]);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="rental-request-' . $id . '.pdf"',
                'Content-Length' => strlen($pdfContent),
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);

        } catch (\Exception $e) {
            Log::error('PDF Export Error', [
                'request_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'PDF generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔥 ОБНОВЛЕННЫЙ МЕТОД: Форматирование спецификаций для PDF с исправлением Weight
     */
    private function formatSpecificationsForPdf($specifications)
    {
        if (empty($specifications)) return [];

        $formatted = [];

        // 🔥 ОБНОВЛЕННЫЙ И РАСШИРЕННЫЙ СЛОВАРЬ ПЕРЕВОДОВ
        $labelMappings = [
            'body_volume' => '📦 Объем кузова',
            'load_capacity' => '⚖️ Грузоподъемность',
            'axle_configuration' => '🚛 Колесная формула',
            'bucket_volume' => '🪣 Объем ковша',
            'operating_weight' => '🏋️ Рабочий вес',
            'power' => '⚡ Мощность',
            'weight' => '⚖️ Вес', // 🔥 ИСПРАВЛЕНО С Weight на Вес
            'weigh' => '⚖️ Вес', // 🔥 ДОБАВЛЕНО для обработки опечаток
            'max_speed' => '🚀 Максимальная скорость',
            'max_digging_depth' => '⛏️ Макс. глубина копания',
            'engine_power' => '🔧 Мощность двигателя',
            'transport_length' => '📏 Длина транспортировки',
            'transport_width' => '📏 Ширина транспортировки',
            'transport_height' => '📏 Высота транспортировки',
            'engine_type' => '🔩 Тип двигателя',
            'fuel_tank_capacity' => '⛽ Емкость топливного бака',
            'bucket_capacity' => '🪣 Емкость ковша',
            'digging_depth' => '⛏️ Глубина копания',
            'blade_width' => '📏 Ширина отвала',
            'blade_height' => '📐 Высота отвала'
        ];

        $unitMappings = [
            'body_volume' => 'м³',
            'load_capacity' => 'т',
            'bucket_volume' => 'м³',
            'operating_weight' => 'т',
            'power' => 'л.с.',
            'weight' => 'т', // 🔥 ИСПРАВЛЕНО
            'weigh' => 'т', // 🔥 ДОБАВЛЕНО
            'max_speed' => 'км/ч',
            'max_digging_depth' => 'м',
            'engine_power' => 'кВт',
            'transport_length' => 'м',
            'transport_width' => 'м',
            'transport_height' => 'м',
            'fuel_tank_capacity' => 'л',
            'bucket_capacity' => 'м³',
            'digging_depth' => 'м',
            'blade_width' => 'м',
            'blade_height' => 'м'
        ];

        Log::debug('🔧 Formatting specifications for PDF', [
            'specifications_type' => gettype($specifications),
            'specifications_keys' => is_array($specifications) ? array_keys($specifications) : 'not_array'
        ]);

        // 🔥 УЛУЧШЕННАЯ ОБРАБОТКА: проверяем все возможные структуры
        $processedSpecs = [];

        // 1. Обработка новой структуры (standard_specifications + custom_specifications)
        if (isset($specifications['standard_specifications']) && is_array($specifications['standard_specifications'])) {
            foreach ($specifications['standard_specifications'] as $key => $value) {
                if ($value !== null && $value !== '' && $value !== 'null') {
                    $processedSpecs[$key] = [
                        'value' => $value,
                        'type' => 'standard',
                        'label' => $labelMappings[$key] ?? $key,
                        'unit' => $unitMappings[$key] ?? ''
                    ];
                }
            }
        }

        // 2. Обработка кастомных спецификаций
        if (isset($specifications['custom_specifications']) && is_array($specifications['custom_specifications'])) {
            foreach ($specifications['custom_specifications'] as $key => $customSpec) {
                if (is_array($customSpec) && isset($customSpec['value']) && $customSpec['value'] !== null && $customSpec['value'] !== '') {
                    $processedSpecs[$key] = [
                        'value' => $customSpec['value'],
                        'type' => 'custom',
                        'label' => '🎯 ' . ($customSpec['label'] ?? 'Дополнительный параметр'),
                        'unit' => $customSpec['unit'] ?? ''
                    ];
                }
            }
        }

        // 3. Обработка старого формата (прямой объект)
        if (empty($processedSpecs)) {
            foreach ($specifications as $key => $value) {
                if ($key === 'metadata' || is_array($value) || $value === null || $value === '') {
                    continue;
                }

                if (str_starts_with($key, 'custom_')) {
                    // Кастомные спецификации в старом формате
                    if (isset($specifications['metadata']) && isset($specifications['metadata'][$key])) {
                        $customSpec = $specifications['metadata'][$key];
                        $processedSpecs[$key] = [
                            'value' => $value,
                            'type' => 'custom',
                            'label' => '🎯 ' . ($customSpec['name'] ?? $key),
                            'unit' => $customSpec['unit'] ?? ''
                        ];
                    } else {
                        $processedSpecs[$key] = [
                            'value' => $value,
                            'type' => 'custom',
                            'label' => '🎯 ' . str_replace('custom_', '', $key),
                            'unit' => ''
                        ];
                    }
                } else {
                    // Стандартные спецификации в старом формате
                    $processedSpecs[$key] = [
                        'value' => $value,
                        'type' => 'standard',
                        'label' => $labelMappings[$key] ?? $key,
                        'unit' => $unitMappings[$key] ?? ''
                    ];
                }
            }
        }

        // 🔥 ФОРМИРУЕМ ОТФОРМАТИРОВАННЫЙ РЕЗУЛЬТАТ
        foreach ($processedSpecs as $spec) {
            $displayValue = $spec['value'] . ($spec['unit'] ? ' ' . $spec['unit'] : '');

            $formatted[] = [
                'formatted' => $spec['label'] . ': ' . $displayValue,
                'value' => $spec['value'],
                'label' => $spec['label'],
                'type' => $spec['type']
            ];
        }

        Log::debug('✅ PDF Specifications formatted', [
            'original_count' => is_array($specifications) ? count($specifications) : 0,
            'formatted_count' => count($formatted),
            'weight_found' => in_array('weight', array_keys($processedSpecs)),
            'weigh_found' => in_array('weigh', array_keys($processedSpecs)),
            'formatted_specs_sample' => array_slice($formatted, 0, 3)
        ]);

        return $formatted;
    }
}
