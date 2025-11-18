<?php
// app/Http/Controllers/Admin/MarkupController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformMarkup;
use App\Models\Equipment;
use App\Models\Category;
use App\Models\Company;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class MarkupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');


    }

    public function index(Request $request)
    {

        // ИСПРАВЛЕНИЕ: Добавляем eager loading для связанных моделей
        $query = PlatformMarkup::with([
            'markupable' => function ($query) {
                // Динамическая загрузка в зависимости от типа
                $query->when(true, function ($q) {
                    // Для оборудования
                    if ($q->getModel() instanceof \App\Models\Equipment) {
                        $q->select('id', 'title', 'category_id');
                    }
                    // Для категорий
                    elseif ($q->getModel() instanceof \App\Models\Category) {
                        $q->select('id', 'name');
                    }
                    // Для компаний
                    elseif ($q->getModel() instanceof \App\Models\Company) {
                        $q->select('id', 'name', 'is_lessee', 'is_lessor');
                    }
                });
            },
            'platform' => function ($query) {
                $query->select('id', 'name');
            },
            'audits' => function ($query) {
                $query->latest()->take(5)->with('user:id,name');
            }
        ]);

        // Фильтрация по типу сущности
        if ($request->filled('markupable_type')) {
            $query->where('markupable_type', $request->markupable_type);
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Фильтрация по статусу активности
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Фильтрация по приоритету
        if ($request->filled('priority_min')) {
            $query->where('priority', '>=', $request->priority_min);
        }

        if ($request->filled('priority_max')) {
            $query->where('priority', '<=', $request->priority_max);
        }

        $markups = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $markupableTypes = [
            Equipment::class => 'Оборудование',
            Category::class => 'Категория',
            Company::class => 'Компания',
            null => 'Общая наценка'
        ];

        $entityTypes = [
            'order' => 'Заказы',
            'rental_request' => 'Заявки',
            'proposal' => 'Предложения'
        ];

        $markupTypes = [
            'fixed' => 'Фиксированная',
            'percent' => 'Процентная',
            'tiered' => 'Ступенчатая',
            'combined' => 'Комбинированная',
            'seasonal' => 'Сезонная'
        ];

        // Статистика для отображения
        $stats = [
            'total' => PlatformMarkup::count(),
            'active' => PlatformMarkup::where('is_active', true)->count(),
            'expired' => PlatformMarkup::where('is_active', true)
                ->where(function($q) {
                    $q->whereNotNull('valid_to')
                    ->where('valid_to', '<', now());
                })->count(),
        ];

        return view('admin.markups.index', compact(
            'markups',
            'markupableTypes',
            'entityTypes',
            'markupTypes',
            'stats'
        ));
    }

    public function create()
    {
        $platforms = Platform::all(); // ДОБАВИТЬ ЭТУ СТРОКУ

        $companies = Company::where(function($q) {
            $q->where('is_lessee', true)->orWhere('is_lessor', true);
        })->get();

        $equipment = Equipment::where('is_approved', true)->get();
        $categories = Category::all();

        // ДИАГНОСТИКА
        \Log::debug('MarkupController create() - данные:', [
            'companies_count' => $companies->count(),
            'companies' => $companies->pluck('legal_name', 'id')->toArray(),
            'equipment_count' => $equipment->count(),
            'categories_count' => $categories->count(),
        ]);

        // Проверяем первые несколько записей
        if ($companies->count() > 0) {
            \Log::debug('Первые 3 компании:', $companies->take(3)->map(function($company) {
                return [
                    'id' => $company->id,
                    'legal_name' => $company->legal_name,
                    'is_lessee' => $company->is_lessee,
                    'is_lessor' => $company->is_lessor,
                    'status' => $company->status
                ];
            })->toArray());
        }

        return view('admin.markups.form', compact(
            'platforms', 'equipment', 'categories', 'companies'
        ));
    }

    public function store(Request $request)
    {
        // ПОДГОТОВКА ДАННЫХ ДЛЯ NULL ЗНАЧЕНИЙ
        $inputData = $request->all();

        // Если не выбрана конкретная сущность, устанавливаем NULL
        if (empty($inputData['markupable_type']) || $inputData['markupable_type'] === 'null') {
            $inputData['markupable_type'] = null;
            $inputData['markupable_id'] = null;
        }

        // Создаем новый Request с обработанными данными
        $processedRequest = new Request($inputData);

        $validated = $processedRequest->validate([
            'platform_id' => 'required|exists:platforms,id',
            'markupable_type' => 'nullable|in:App\Models\Equipment,App\Models\EquipmentCategory,App\Models\Company',
            'markupable_id' => 'nullable|integer',
            'entity_type' => 'required|in:order,rental_request,proposal',
            'type' => 'required|in:fixed,percent,tiered,combined,seasonal',
            'calculation_type' => 'required|in:addition,multiplication,complex',
            'value' => 'required|numeric|min:0',
            'rules' => 'nullable|array',
            'is_active' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after:valid_from',
            'priority' => 'integer|min:0',
        ]);

        // РУЧНАЯ ПРОВЕРКА: если указан markupable_type, то должен быть и markupable_id
        if (!empty($validated['markupable_type']) && empty($validated['markupable_id'])) {
            return back()->withInput()->with('error', 'При выборе типа сущности необходимо указать конкретную сущность.');
        }

        DB::beginTransaction();

        try {
            // Проверка уникальности с учетом NULL значений
            $existing = PlatformMarkup::where('platform_id', $validated['platform_id'])
                ->where('entity_type', $validated['entity_type'])
                ->where(function($query) use ($validated) {
                    if (!empty($validated['markupable_type']) && !empty($validated['markupable_id'])) {
                        // Для конкретной сущности
                        $query->where('markupable_type', $validated['markupable_type'])
                            ->where('markupable_id', $validated['markupable_id']);
                    } else {
                        // Для общих наценок (NULL)
                        $query->whereNull('markupable_type')
                            ->whereNull('markupable_id');
                    }
                })
                ->first();

            if ($existing) {
                return back()->withInput()->with('error',
                    'Наценка с такими параметрами уже существует. Отредактируйте существующую.');
            }

            $markup = PlatformMarkup::create($validated);
            $markup->logAudit('created', null, $markup->toArray(), 'Создание наценки');

            // 🔥 ПАТТЕРН-ОСНОВАННАЯ ОЧИСТКА: Очистка при создании
            $this->clearMarkupCache();
            Log::info('Markup cache cleared after creation', ['markup_id' => $markup->id]);

            DB::commit();

            return redirect()->route('markups.index')
                ->with('success', 'Наценка успешно создана');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating markup: ' . $e->getMessage());

            return back()->withInput()->with('error',
                'Ошибка при создании наценки: ' . $e->getMessage());
        }
    }

    /**
     * 🔥 ОБНОВЛЕННЫЙ МЕТОД ДЛЯ ОЧИСТКИ ВСЕХ КЕШЕЙ НАЦЕНОК
     */
    private function clearMarkupCache()
    {
        try {
            // Получаем текущий драйвер кеша
            $cacheDriver = config('cache.default');
            Log::info("Clearing markup cache for driver: {$cacheDriver}");

            // Паттерны для очистки
            $patterns = [
                'markup_*',
                'markups_*',
                'pricing_*',
                'price_calculation_*'
            ];

            // Разные стратегии для разных драйверов
            switch ($cacheDriver) {
                case 'redis':
                    $this->clearRedisCache($patterns);
                    break;

                case 'file':
                case 'database':
                case 'array':
                default:
                    // Для драйверов без поддержки тегов используем полную очистку
                    Cache::flush();
                    Log::info("Full cache flush for driver: {$cacheDriver}");
                    break;
            }

            // Дополнительно: очистка через Artisan
            Artisan::call('cache:clear');
            Log::info('Artisan cache:clear executed');

            Log::info('Markup cache clearance completed', [
                'driver' => $cacheDriver,
                'timestamp' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error clearing markup cache: ' . $e->getMessage());

            // Фолбэк: всегда работает
            Cache::flush();
            Log::info('Fallback: Full cache flush executed');
        }
    }

     /**
     * Очистка Redis кеша по паттернам
     */
    private function clearRedisCache(array $patterns)
    {
        try {
            $redis = Cache::getRedis();

            foreach ($patterns as $pattern) {
                $cursor = 0;
                do {
                    list($cursor, $chunk) = $redis->scan($cursor, 'MATCH', $pattern, 'COUNT', 100);
                    if (!empty($chunk)) {
                        $redis->del(...$chunk);
                        Log::debug("Cleared Redis keys for pattern: {$pattern}", ['count' => count($chunk)]);
                    }
                } while ($cursor != 0);
            }

            Log::info('Redis pattern clearance completed', ['patterns' => $patterns]);
        } catch (\Exception $e) {
            Log::error('Error clearing Redis cache: ' . $e->getMessage());
            throw $e;
        }
    }

    public function edit(PlatformMarkup $markup)
    {
        $platforms = Platform::all();
        $equipment = Equipment::where('is_approved', true)->get();
        $categories = Category::all();
        $companies = Company::where('status', 'verified')
        ->where(function($q) {
            $q->where('is_lessee', true)->orWhere('is_lessor', true);
        })
        ->get();

        return view('admin.markups.form', compact(
            'markup', 'platforms', 'equipment', 'categories', 'companies'
        ));
    }

    public function update(Request $request, PlatformMarkup $markup)
    {
        // ПОДГОТОВКА ДАННЫХ ДЛЯ NULL ЗНАЧЕНИЙ
        $inputData = $request->all();

        // Если не выбрана конкретная сущность, устанавливаем NULL
        if (empty($inputData['markupable_type']) || $inputData['markupable_type'] === 'null') {
            $inputData['markupable_type'] = null;
            $inputData['markupable_id'] = null;
        }

        // Создаем новый Request с обработанными данными
        $processedRequest = new Request($inputData);

        $validated = $processedRequest->validate([
            'platform_id' => 'required|exists:platforms,id',
            'markupable_type' => 'nullable|in:App\Models\Equipment,App\Models\EquipmentCategory,App\Models\Company',
            'markupable_id' => 'nullable|integer',
            'entity_type' => 'required|in:order,rental_request,proposal',
            'type' => 'required|in:fixed,percent,tiered,combined,seasonal',
            'calculation_type' => 'required|in:addition,multiplication,complex',
            'value' => 'required|numeric|min:0',
            'rules' => 'nullable|array',
            'is_active' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after:valid_from',
            'priority' => 'integer|min:0',
        ]);

        // РУЧНАЯ ПРОВЕРКА: если указан markupable_type, то должен быть и markupable_id
        if (!empty($validated['markupable_type']) && empty($validated['markupable_id'])) {
            return back()->withInput()->with('error', 'При выборе типа сущности необходимо указать конкретную сущность.');
        }

        DB::beginTransaction();

        try {
            // Проверка уникальности (исключая текущую запись)
            $existing = PlatformMarkup::where('platform_id', $validated['platform_id'])
                ->where('entity_type', $validated['entity_type'])
                ->where(function($query) use ($validated) {
                    if (!empty($validated['markupable_type']) && !empty($validated['markupable_id'])) {
                        // Для конкретной сущности
                        $query->where('markupable_type', $validated['markupable_type'])
                            ->where('markupable_id', $validated['markupable_id']);
                    } else {
                        // Для общих наценок (NULL)
                        $query->whereNull('markupable_type')
                            ->whereNull('markupable_id');
                    }
                })
                ->where('id', '!=', $markup->id)
                ->first();

            if ($existing) {
                return back()->withInput()->with('error',
                    'Наценка с такими параметрами уже существует.');
            }

            $oldValues = $markup->toArray();
            $markup->update($validated);
            $markup->logAudit('updated', $oldValues, $markup->toArray(), 'Обновление наценки');

            // 🔥 ПАТТЕРН-ОСНОВАННАЯ ОЧИСТКА: Очистка при обновлении
            $this->clearMarkupCache();
            Log::info('Markup cache cleared after update', ['markup_id' => $markup->id]);

            DB::commit();

            return redirect()->route('markups.index')
                ->with('success', 'Наценка успешно обновлена');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating markup: ' . $e->getMessage());

            return back()->withInput()->with('error',
                'Ошибка при обновлении наценки: ' . $e->getMessage());
        }
    }
    /**
     * Валидация правил наценки в зависимости от типа
     */
    private function validateRules(string $type, array $rules): void
    {
        $validator = \Validator::make(['rules' => $rules], [
            'rules' => $this->getRulesValidation($type)
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    /**
     * Получение правил валидации для конкретного типа наценки
     */
    private function getRulesValidation(string $type): array
    {
        return match($type) {
            'tiered' => [
                'rules.tiers' => 'required|array|min:1',
                'rules.tiers.*.min' => 'required|integer|min:0',
                'rules.tiers.*.max' => 'required|integer|min:1|gt:rules.tiers.*.min',
                'rules.tiers.*.type' => 'required|in:fixed,percent',
                'rules.tiers.*.value' => 'required|numeric|min:0',
            ],
            'combined' => [
                'rules.fixed_value' => 'required|numeric|min:0',
                'rules.percent_value' => 'required|numeric|min:0|max:100',
            ],
            'seasonal' => [
                'rules.high_season_coefficient' => 'required|numeric|min:0.1',
                'rules.medium_season_coefficient' => 'required|numeric|min:0.1',
                'rules.low_season_coefficient' => 'required|numeric|min:0.1',
            ],
            'fixed', 'percent' => [
                // Используем правильный синтаксис для nullable массива
                'rules' => 'nullable', // или 'sometimes|array'
            ],
            default => [
                'rules' => 'nullable', // или 'sometimes|array'
            ]
        };
    }

    public function destroy(PlatformMarkup $markup)
    {
        DB::beginTransaction();

        try {
            // КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Аудит перед удалением
            $markup->logAudit('deleted', $markup->toArray(), null, 'Удаление наценки');

            $markupId = $markup->id;
            $markupData = $markup->toArray();

            $markup->delete();

            // 🔥 ПАТТЕРН-ОСНОВАННАЯ ОЧИСТКА: Очистка при удалении
            $this->clearMarkupCache();
            Log::info('Markup cache cleared after deletion', ['markup_id' => $markupId]);

            DB::commit();

            return redirect()->route('admin.markups.index')
                ->with('success', 'Наценка успешно удалена');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting markup: ' . $e->getMessage());

            return back()->with('error',
                'Ошибка при удалении наценки: ' . $e->getMessage());
        }
    }

    public function testCalculation(Request $request)
    {
        $validated = $request->validate([
            'base_price' => 'required|numeric|min:0',
            'equipment_id' => 'nullable|exists:equipment,id',
            'category_id' => 'nullable|exists:equipment_categories,id',
            'company_id' => 'nullable|exists:companies,id',
            'entity_type' => 'required|in:order,rental_request,proposal',
            'working_hours' => 'required|integer|min:1'
        ]);

        try {
            $calculationService = app(\App\Services\MarkupCalculationService::class);

            $result = $calculationService->calculateMarkup(
                $validated['base_price'],
                $validated['entity_type'],
                $validated['working_hours'],
                $validated['equipment_id'] ?? null,
                $validated['category_id'] ?? null,
                $validated['company_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка расчета: ' . $e->getMessage()
            ], 500);
        }
    }
}
