<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Operator;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $operators = Operator::where('company_id', $companyId)
            ->with('equipment')
            ->paginate(10);

        // Получаем оборудование без операторов с группировкой
        $equipmentWithoutOperators = Equipment::where('company_id', $companyId)
            ->with(['operators' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get()
            ->filter(function ($equipment) {
                $hasDay = $equipment->operators->contains('shift_type', Operator::SHIFT_DAY);
                $hasNight = $equipment->operators->contains('shift_type', Operator::SHIFT_NIGHT);

                return ! $hasDay || ! $hasNight;
            })
            ->unique('id'); // Убираем дубликаты

        return view('lessor.operators.index', [
            'operators' => $operators,
            'equipmentWithoutOperators' => $equipmentWithoutOperators,
        ]);
    }

    public function create()
    {
        $companyId = auth()->user()->company_id;
        $equipment = Equipment::where('company_id', $companyId)->get();

        return view('lessor.operators.create', compact('equipment'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'license_number' => 'required|string|max:50',
            'qualification' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'equipment_id' => 'nullable|exists:equipment,id',
            'shift_type' => 'required|in:day,night', // Добавить
        ]);

        $data['company_id'] = auth()->user()->company_id;
        $operator = Operator::create($data);

        // Обработка оборудования
        $this->processEquipmentAssignment($request, $operator);

        // Обновляем привязку оборудования
        if ($request->equipment_id) {
            Equipment::where('id', $request->equipment_id)
                ->update(['operator_id' => $operator->id]);
        }

        return redirect()->route('lessor.operators.index')
            ->with('success', 'Оператор успешно добавлен');
    }

    public function edit(Operator $operator)
    {
        $this->authorize('update', $operator);

        $companyId = auth()->user()->company_id;
        $equipment = Equipment::where('company_id', $companyId)->get();

        return view('lessor.operators.edit', compact('operator', 'equipment'));
    }

    public function update(Request $request, Operator $operator)
    {
        $this->authorize('update', $operator);

        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'license_number' => 'required|string|max:50',
            'qualification' => 'nullable|string|max:255',
            'equipment_id' => 'nullable|exists:equipment,id',
            'shift_type' => 'required|in:day,night',
        ]);

        // Определение статуса активности
        $data['is_active'] = $request->filled('is_active') ? 1 : $request->input('is_active_fallback', 0);

        // Сброс предыдущей привязки
        if ($operator->equipment_id) {
            Equipment::where('id', $operator->equipment_id)
                ->update(['operator_id' => null]);
        }

        $operator->update($data);

        // Обработка оборудования
        $this->processEquipmentAssignment($request, $operator);

        // Установка новой привязки
        if ($request->equipment_id) {
            Equipment::where('id', $request->equipment_id)
                ->update(['operator_id' => $operator->id]);
        }

        // Отладочный вывод
        \Log::debug('Final update data', [
            'is_active' => $data['is_active'],
            'request_is_active' => $request->input('is_active'),
            'request_fallback' => $request->input('is_active_fallback'),
        ]);

        return redirect()->route('lessor.operators.index')
            ->with('success', 'Оператор успешно обновлен');
    }

    protected function processEquipmentAssignment(Request $request, Operator $operator)
    {
        if ($operator->equipment_id && $operator->equipment_id != $request->equipment_id) {
            Equipment::where('id', $operator->equipment_id)->update(['operator_id' => null]);
        }

        if ($request->equipment_id && $operator->equipment_id != $request->equipment_id) {
            $currentOperator = Equipment::find($request->equipment_id)->operator_id;

            if ($currentOperator && $currentOperator != $operator->id) {
                throw new \Exception('Оборудование уже привязано к другому оператору');
            }

            Equipment::where('id', $request->equipment_id)->update(['operator_id' => $operator->id]);
        }

    }

    public function destroy(Operator $operator)
    {
        $this->authorize('delete', $operator);

        // Сброс привязки оборудования
        if ($operator->equipment_id) {
            Equipment::where('id', $operator->equipment_id)
                ->update(['operator_id' => null]);
        }

        $operator->delete();

        return back()->with('success', 'Оператор удален');
    }
}
