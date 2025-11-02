<?php

namespace App\Http\Controllers\Lessee;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\Category;
use App\Models\Location;
use App\Models\Equipment;
use App\Services\RentalRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreRentalRequestRequest;
use Barryvdh\DomPDF\Facade\Pdf; // ⚠️ ДОБАВИТЬ ИМПОРТ
use Illuminate\Support\Facades\Log; // ⚠️ ДОБАВИТЬ ИМПОРТ

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

        // Логируем данные для отладки
        \Log::debug('Rental request create page data:', [
            'categories_count' => $categories->count(),
            'locations_count' => $locations->count(),
            'categories' => $categories->pluck('name')->toArray()
        ]);

        return view('lessee.rental_requests.create', compact('categories', 'locations'));
    }

    public function store(StoreRentalRequestRequest $request)
    {
        try {
            $validated = $request->validated();

            $rentalRequest = $this->rentalRequestService->createRentalRequest(
                $validated,
                auth()->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Заявка успешно создана',
                'redirect_url' => route('lessee.rental-requests.show', $rentalRequest->id),
                'request_id' => $rentalRequest->id
            ]);

        } catch (\Exception $e) {
            \Log::error('Rental request creation error: ' . $e->getMessage());
            \Log::error('Request data: ', $request->all());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании заявки: ' . $e->getMessage()
            ], 500);
        }
    }

    // Новый метод для расчета бюджета в реальном времени
    public function calculateBudget(): void
    {
        if (!$this->hourly_rate || !$this->rental_period_start || !$this->rental_period_end) {
            return;
        }

        $conditions = array_merge($this->getDefaultRentalConditions(), $this->rental_conditions ?? []);

        $start = Carbon::parse($this->rental_period_start);
        $end = Carbon::parse($this->rental_period_end);
        $totalDays = $start->diffInDays($end) + 1;

        // Точный расчет по вашей формуле
        $hoursPerShift = $conditions['hours_per_shift'] ?? 8;
        $shiftsPerDay = $conditions['shifts_per_day'] ?? 1;

        // Расчет для одной единицы техники
        $costPerShift = $this->hourly_rate * $hoursPerShift; // стоимость за смену (1 единица)
        $costPerDay = $costPerShift * $shiftsPerDay;         // стоимость за день (1 единица)
        $costPerUnit = $costPerDay * $totalDays;             // стоимость за период (1 единица)

        // Общая стоимость для всего количества
        $totalBudget = $costPerUnit * $this->equipment_quantity;

        // Для заявок с несколькими позициями - суммируем
        if ($this->items->isNotEmpty()) {
            $totalBudget = $this->calculateBudgetFromItems();
        }

        // Точечный бюджет (без диапазона)
        $this->calculated_budget_from = $totalBudget;
        $this->calculated_budget_to = $totalBudget;
    }

    private function calculateBudgetFromItems(): float
    {
        $totalBudget = 0;
        $conditions = array_merge($this->getDefaultRentalConditions(), $this->rental_conditions ?? []);

        $start = Carbon::parse($this->rental_period_start);
        $end = Carbon::parse($this->rental_period_end);
        $totalDays = $start->diffInDays($end) + 1;

        $hoursPerShift = $conditions['hours_per_shift'] ?? 8;
        $shiftsPerDay = $conditions['shifts_per_day'] ?? 1;

        foreach ($this->items as $item) {
            $costPerUnit = $this->hourly_rate * $hoursPerShift * $shiftsPerDay * $totalDays;
            $totalBudget += $costPerUnit * $item->quantity;
        }

        return $totalBudget;
    }

   public function index(Request $request)
{
    $status = $request->get('status', 'active');
    $perPage = $request->get('per_page', 15);

    $requests = RentalRequest::with([
        'category' => function($query) {
            $query->withDefault([ // Добавляем withDefault для избежания ошибок
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
        // Добавляем items_count для отображения в таблице
        $rentalRequest->items_count = $rentalRequest->items->count() ?? 1;

        // Обеспечиваем наличие категории
        if (!$rentalRequest->category) {
            $rentalRequest->setRelation('category', new \App\Models\Category([
                'name' => 'Категория не указана'
            ]));
        }
        return $rentalRequest;
    });

    $stats = $this->getRequestStats(auth()->id());

    // Добавляем общую статистику по позициям и предложениям
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

        // ВАЖНО: Вызываем расчет бюджета если он не установлен
        if (!$rentalRequest->total_budget || $rentalRequest->total_budget == 0) {
            \Log::info('Lessee Controller: Бюджет не установлен, вычисляем...', ['request_id' => $rentalRequest->id]);
            $rentalRequest->calculateBudget();
            $rentalRequest->refresh();
        }

        // Для Vue-компонента просто возвращаем шаблон
        return view('lessee.rental_requests.show', compact('rentalRequest'));
    }

    public function edit($id)
    {
        \Log::debug('=== EDIT METHOD START ===', [
            'id' => $id,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email
        ]);

        try {
            $rentalRequest = RentalRequest::with(['items.category', 'location'])
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            \Log::debug('Rental request found:', [
                'id' => $rentalRequest->id,
                'title' => $rentalRequest->title,
                'items_count' => $rentalRequest->items->count()
            ]);

            $categories = Category::with('children')
                ->whereNull('parent_id')
                ->get();

            $locations = Location::where('company_id', auth()->user()->company_id)->get();

            \Log::debug('Data loaded:', [
                'categories_count' => $categories->count(),
                'locations_count' => $locations->count()
            ]);

            return view('lessee.rental_requests.edit-vue', compact(
                'rentalRequest',
                'categories',
                'locations'
            ));

        } catch (\Exception $e) {
            \Log::error('Error in edit method:', [
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
            $proposal = RentalRequestResponse::where('rental_request_id', $requestId)
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
        try {
            \Log::debug('=== UPDATE METHOD START ===', [
                'id' => $id,
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            $rentalRequest = RentalRequest::where('user_id', auth()->id())
                ->findOrFail($id);

            // Валидация данных
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'hourly_rate' => 'required|numeric|min:0',
                'rental_period_start' => 'required|date',
                'rental_period_end' => 'required|date|after_or_equal:rental_period_start',
                'location_id' => 'required|exists:locations,id',
                'rental_conditions' => 'sometimes|array',
                'items' => 'required|array|min:1',
                'items.*.category_id' => 'required|exists:equipment_categories,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.hourly_rate' => 'sometimes|numeric|min:0',
                'items.*.specifications' => 'sometimes|array',
                'items.*.use_individual_conditions' => 'sometimes|boolean',
                'items.*.individual_conditions' => 'sometimes|array'
            ]);

            \Log::debug('Validated data:', $validated);

            // Обновление заявки через сервис
            $updatedRequest = $this->rentalRequestService->updateRentalRequest($rentalRequest, $validated);

            \Log::debug('Rental request updated successfully', [
                'request_id' => $updatedRequest->id,
                'items_count' => $updatedRequest->items->count()
            ]);

            // ⚠️ ИСПРАВЛЕНИЕ: Просто возвращаем успех без данных
            return response()->json([
                'success' => true,
                'message' => 'Заявка успешно обновлена'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating rental request: ' . $e->getMessage(), [
                'request_id' => $id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении заявки: ' . $e->getMessage()
            ], 500);
        }
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

            // Загружаем данные
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

            $data = [
                'rentalRequest' => $rentalRequest,
                'items' => $rentalRequest->items,
                'user' => auth()->user(),
                'exportDate' => now()->format('d.m.Y H:i'),
            ];

            // Генерируем PDF
            $pdf = PDF::loadView('lessee.rental_requests.pdf', $data);

            // Настройки PDF
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('enable_html5_parser', true);
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->setOption('defaultFont', 'DejaVu Sans');

            $pdfContent = $pdf->output();

            Log::info('PDF Generated Successfully', [
                'request_id' => $id,
                'file_size' => strlen($pdfContent)
            ]);

            // ✅ ИСПРАВЛЕНИЕ: Возвращаем response с правильными заголовками
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
}
