<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Waybill;
use App\Models\Operator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDF;
use Illuminate\Support\Facades\Validator;
use App\Models\WaybillShift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\CompletionAct;
use App\Services\WaybillCreationService;

class WaybillController extends Controller
{
   public function index(Order $order = null)
{
     $query = Waybill::with(['equipment.mainImage', 'operator'])
        ->whereHas('order', function($q) {
            $q->where('lessor_company_id', auth()->user()->company_id);
        })
        ->orderBy('created_at', 'desc'); // Добавляем сортировк

    // Фильтр по заказу
    if ($order && $order->exists) {
        $query->where('order_id', $order->id);
        $viewOrder = $order;
    } else {
        $viewOrder = null;
    }

    // Фильтр по статусу
    if ($status = request('status')) {
        $query->where('status', $status);
    }

    // Фильтр по типу смены
    if ($shiftType = request('shift_type')) {
        $query->where('shift_type', $shiftType);
    }

    // Сортировка
    $sort = request('sort', 'newest');
    switch ($sort) {
        case 'oldest':
            $query->orderBy('created_at', 'asc');
            break;
        case 'period':
            $query->orderBy('start_date', 'desc');
            break;
        default:
            $query->orderBy('created_at', 'desc');
    }

    $waybills = $query->paginate(10);

    return view('lessor.documents.waybills.index', [
        'order' => $viewOrder,
        'waybills' => $waybills
    ]);
}
   public function show(Waybill $waybill, Request $request)
    {
        if ($waybill->order->lessor_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        $waybill->load([
            'order.items',
            'orderItem',
            'equipment.mainImage',
            'operator',
            'rentalCondition',
            'shifts.operator',
            'completionAct', // Добавляем загрузку связанного акта
            'completionActs' // Добавляем загрузку всех связанных актов
        ]);

        $operators = Operator::where('company_id', auth()->user()->company_id)->get();

        // Выбор текущей смены
        $selectedShift = null;
        if ($request->has('shift_id')) {
            $selectedShift = $waybill->shifts->find($request->shift_id);
        }

        if (!$selectedShift) {
            // Находим первую незаполненную смену
            $selectedShift = $waybill->shifts->firstWhere('hours_worked', 0) ?? $waybill->shifts->first();
        }

         // Гарантируем наличие orderItem или используем fallback
        if (!$waybill->orderItem) {
            Log::warning('Waybill without orderItem', ['waybill_id' => $waybill->id]);
            $waybill->load('order');
        }

        // Рассчитываем общее количество часов
        $totalHours = $waybill->shifts->sum('hours_worked');

        // Используем фиксированные цены вместо расчетных
        $baseHourlyRate = $waybill->orderItem->fixed_lessor_price
            ?? $waybill->hourly_rate;

        $totalAmount = $totalHours * $baseHourlyRate;

        // Рассчитываем дополнительные показатели
        $totalShifts = $waybill->shifts->count();
        $filledShifts = $waybill->shifts->where('hours_worked', '>', 0)->count();

        return view('lessor.documents.waybills.show', [
            'waybill' => $waybill,
            'operators' => $operators,
            'selectedShift' => $selectedShift,
            'filledShifts' => $filledShifts,
            'totalShifts' => $totalShifts,
            'totalHours' => $totalHours,
            'totalAmount' => $totalAmount,
            'baseHourlyRate' => $baseHourlyRate,
        ]);
    }

    public function update(Waybill $waybill, Request $request)
    {

        // Логирование входящих данных
        \Log::debug('Waybill update request', [
            'license_plate' => $request->license_plate,
            'operator_id' => $request->operator_id
        ]);

        $validated = $request->validate([
            'license_plate' => 'required|string|max:20',
            'operator_id' => 'required|exists:operators,id',
        ]);

        try {
            $waybill->update($validated);

            // Логирование успешного обновления
            \Log::info('Waybill updated successfully', [
                'waybill_id' => $waybill->id,
                'changes' => $validated
            ]);

            return response()->json([
                'success' => true,
                'license_plate' => $waybill->license_plate
            ]);

        } catch (\Exception $e) {
            \Log::error('Waybill update failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка сервера: ' . $e->getMessage(),
                'errors' => $e->errors() ?? []
            ], 500);
        }
    }

    public function sign(Request $request, Waybill $waybill)
    {
        // Проверка прав доступа
        if ($waybill->order->lessor_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        $request->validate([
            'signature' => 'required|string'
        ]);

        $signaturePath = $this->saveSignature($request->signature);

        $waybill->update([
            'customer_signature_path' => $signaturePath,
            'status' => Waybill::STATUS_COMPLETED,
            'completed_at' => now()
        ]);

        return response()->json(['status' => 'success']);
    }

    public function download(Waybill $waybill)
    {
        // Проверка прав доступа
        if ($waybill->order->lessor_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        $waybill->load([
            'order.lesseeCompany',
            'order.lessorCompany',
            'equipment',
            'operator',
            'rentalCondition'
        ]);

        $pdf = PDF::loadView('lessor.documents.waybills.pdf', compact('waybill'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Путевой-лист-ЭСМ-2-{$waybill->id}.pdf");
    }

    private function saveSignature(string $svg): string
    {
        $filename = 'signatures/' . Str::uuid() . '.svg';
        Storage::disk('public')->put($filename, $svg);
        return $filename;
    }

   public function close(Waybill $waybill)
    {
        if ($waybill->order->lessor_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        if ($waybill->status !== Waybill::STATUS_ACTIVE) {
            return back()->withErrors('Можно закрыть только активные путевые листы');
        }

        $unfilledShifts = $waybill->shifts()
            ->where(function($query) {
                $query->whereNull('hours_worked')
                    ->orWhere('hours_worked', '<=', 0);
            })
            ->count();

        if ($unfilledShifts > 0) {
            return back()->withErrors("Осталось $unfilledShifts незаполненных смен!");
        }

        try {
            $act = null;

            DB::transaction(function () use ($waybill, &$act) {
                // 1. Закрываем путевой лист
                $waybill->update(['status' => Waybill::STATUS_COMPLETED]);

                // 2. Создаем акт
                $act = CompletionAct::create([
                    'order_id' => $waybill->order_id,
                    'waybill_id' => $waybill->id,
                    'act_date' => now(),
                    'service_start_date' => $waybill->start_date,
                    'service_end_date' => $waybill->end_date,
                    'total_hours' => $waybill->shifts->sum('hours_worked'),
                    'total_downtime' => $waybill->shifts->sum('downtime_hours'),
                    'hourly_rate' => $waybill->lessor_hourly_rate,
                    'total_amount' => $waybill->shifts->sum(function($shift) use ($waybill) {
                        return $shift->hours_worked * $waybill->lessor_hourly_rate;
                    }),
                    'status' => 'generated'
                ]);

                // 3. Создаем следующий путевой лист
                $nextWaybill = app(\App\Services\WaybillCreationService::class)
                    ->createNextWaybill($waybill);

                // 4. Исправленный вызов - используем публичный метод
                if ($nextWaybill) {
                    app(WaybillCreationService::class)->createShiftsForWaybill($nextWaybill);
                }

                Log::info('Waybill closed', [
                    'waybill_id' => $waybill->id,
                    'next_waybill_id' => $nextWaybill?->id,
                    'completion_act_id' => $act->id
                ]);
            });

            return back()->with('success', [
                'message' => 'Путевой лист закрыт. Акт №' . $act->id . ' создан',
                'act_id' => $act->id
            ]);

        } catch (\Exception $e) {
            Log::error('Waybill closing failed: ' . $e->getMessage());
            return back()->withErrors('Ошибка: ' . $e->getMessage());
        }
    }

    private function createNextWaybill(Waybill $waybill): ?Waybill
    {
        $nextStart = $waybill->end_date->copy()->addDay();

        // Используем связь вместо прямого обращения
        $orderItem = $waybill->orderItem()->with('order')->first();

        if (!$orderItem || !$orderItem->order) {
            Log::error('Order item or parent order missing', ['waybill_id' => $waybill->id]);
            return null;
        }

        // Используем end_date из родительского заказа
        if ($nextStart >= $nextEnd) {
            Log::info('No need for next waybill - rental period ending', [
                'waybill_id' => $currentWaybill->id,
                'next_start' => $nextStart,
                'next_end' => $nextEnd
            ]);
            return null;
        }

        return Waybill::create([
            'order_id' => $waybill->order_id,
            'order_item_id' => $waybill->order_item_id,
            'equipment_id' => $waybill->equipment_id,
            'operator_id' => $waybill->operator_id,
            'shift_type' => $waybill->shift_type,
            'start_date' => $nextStart,
            'end_date' => $nextStart->copy()->addDays(9),
            'status' => Waybill::STATUS_FUTURE,
            'hourly_rate' => $waybill->hourly_rate,
            'lessor_hourly_rate' => $waybill->lessor_hourly_rate,
            'notes' => 'Автоматически создан'
        ]);
    }

    private function calculatePenalty(Waybill $waybill): float
    {
        // Логика расчета штрафов
        $downtimeHours = $waybill->shifts->sum('downtime_hours');
        $hourlyRate = $waybill->hourly_rate;

        return $downtimeHours * $hourlyRate * 0.2; // Пример: 20% от ставки за простой
    }

    public function addShift(Waybill $waybill, Request $request)
    {
        Log::info('Начало добавления смены', [
            'waybill_id' => $waybill->id,
            'user_id' => auth()->id(),
            'request_data' => $request->all()
        ]);

        // Проверка прав доступа
        if ($waybill->order->lessor_company_id !== auth()->user()->company_id) {
            Log::warning('Попытка доступа к чужому путевому листу', [
                'waybill_id' => $waybill->id,
                'user_company' => auth()->user()->company_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен'
            ], 403);
        }

        // Проверка статуса (разрешаем FUTURE и ACTIVE)
        $allowedStatuses = [Waybill::STATUS_ACTIVE, Waybill::STATUS_FUTURE];

        if (!in_array($waybill->status, $allowedStatuses)) {
            Log::warning('Попытка добавить смену в неактивный путевой лист', [
                'waybill_id' => $waybill->id,
                'current_status' => $waybill->status
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Путевой лист имеет статус "' . $waybill->status_text . '". Добавлять смены можно только в активные или будущие путевые листы.'
            ], 400);
        }

        // Валидация даты
        $validator = Validator::make($request->all(), [
            'shift_date' => 'required|date|after_or_equal:' . $waybill->start_date->format('Y-m-d') .
                            '|before_or_equal:' . $waybill->end_date->format('Y-m-d')
        ], [
            'shift_date.after_or_equal' => 'Дата смены не может быть раньше :date',
            'shift_date.before_or_equal' => 'Дата смены не может быть позже :date'
        ]);

        if ($validator->fails()) {
            Log::error('Ошибка валидации даты смены', [
                'errors' => $validator->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации даты',
                'errors' => $validator->errors()
            ], 422);
        }

        // Проверка оператора
        if (!$waybill->operator_id) {
            Log::error('Оператор не назначен для путевого листа', [
                'waybill_id' => $waybill->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не назначен оператор для путевого листа'
            ], 400);
        }

        // Проверка лимита смен
        if ($waybill->shifts()->count() >= 10) {
            Log::warning('Достигнут лимит смен в путевом листе', [
                'waybill_id' => $waybill->id,
                'current_shifts' => $waybill->shifts()->count()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Достигнут лимит в 10 смен для одного путевого листа'
            ], 400);
        }

        try {
            $shiftDate = Carbon::parse($request->shift_date);
        } catch (\Exception $e) {
            Log::error('Ошибка парсинга даты смены', [
                'shift_date' => $request->shift_date,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Некорректный формат даты'
            ], 400);
        }

        // Проверка уникальности смены на дату
        $existingShift = WaybillShift::where('waybill_id', $waybill->id)
            ->whereDate('shift_date', $shiftDate->format('Y-m-d'))
            ->exists();

        if ($existingShift) {
            Log::warning('Попытка добавить дублирующую смену', [
                'waybill_id' => $waybill->id,
                'shift_date' => $shiftDate->format('Y-m-d')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Смена на эту дату уже существует'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Создание смены
            $shift = WaybillShift::create([
                'waybill_id' => $waybill->id,
                'shift_date' => $shiftDate,
                'operator_id' => $waybill->operator_id,
                'hourly_rate' => $waybill->lessor_hourly_rate,
                'work_start_time' => null,
                'work_end_time' => null
            ]);

            // Автоматическая активация FUTURE waybill
            if ($waybill->status === Waybill::STATUS_FUTURE) {
                $waybill->update(['status' => Waybill::STATUS_ACTIVE]);
                Log::info('Путевой лист активирован', ['waybill_id' => $waybill->id]);
            }

            DB::commit();

            Log::info('Смена успешно добавлена', [
                'shift_id' => $shift->id,
                'waybill_id' => $waybill->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Смена добавлена',
                'shift_id' => $shift->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Критическая ошибка при создании смены', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Внутренняя ошибка сервера: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getShifts(Waybill $waybill)
    {
        if ($waybill->order->lessor_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        // Загружаем смены с операторами
        $waybill->load(['shifts' => function($query) {
            $query->with('operator');
        }]);

        // Рассчитываем показатели
        $filledShifts = $waybill->shifts->where('hours_worked', '>', 0)->count();
        $totalShifts = $waybill->shifts->count();
        $totalHours = $waybill->shifts->sum('hours_worked');
        $baseHourlyRate = $waybill->base_hourly_rate;

        return view('lessor.documents.waybills.partials.shifts_table', [
            'waybill' => $waybill,
            'filledShifts' => $filledShifts,
            'totalShifts' => $totalShifts,
            'totalHours' => $totalHours,
            'baseHourlyRate' => $baseHourlyRate
        ]);
    }

}
