<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Operator;
use Illuminate\Http\Request;

class AdminOperatorController extends Controller
{
    public function index()
    {
        $operators = Operator::with('equipment')->orderBy('full_name')->paginate(25);
        return view('admin.operators.index', compact('operators'));
    }

    public function create()
    {
        $equipment = Equipment::where('is_platform_owned', true)->where('is_approved', true)->get();
        return view('admin.operators.create', compact('equipment'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'license_number' => 'nullable|string|max:100',
            'qualification' => 'nullable|string|max:255',
            'equipment_id' => 'nullable|exists:equipment,id',
            'shift_type' => 'required|in:day,night',
            'is_active' => 'nullable|boolean',
        ]);
        $data['company_id'] = null; // платформенный оператор
        $data['is_active'] = $request->boolean('is_active', true);
        Operator::create($data);

        return redirect()->route('admin.operators.index')->with('success', 'Оператор платформы добавлен');
    }

    public function edit(Operator $operator)
    {
        $equipment = Equipment::where('is_platform_owned', true)->where('is_approved', true)->get();
        return view('admin.operators.edit', compact('operator', 'equipment'));
    }

    public function update(Request $request, Operator $operator)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'license_number' => 'nullable|string|max:100',
            'qualification' => 'nullable|string|max:255',
            'equipment_id' => 'nullable|exists:equipment,id',
            'shift_type' => 'required|in:day,night',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $operator->update($data);

        return redirect()->route('admin.operators.index')->with('success', 'Оператор обновлен');
    }

    public function destroy(Operator $operator)
    {
        $operator->delete();
        return redirect()->route('admin.operators.index')->with('success', 'Оператор удален');
    }
}
