<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CompanyEmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:company_admin');
    }

    public function index()
    {
        $company = Auth::user()->company;
        $employees = $company->users()->where('id', '!=', Auth::id())->get();

        return view('company.employees.index', compact('employees'));
    }

    public function create()
    {
        $roles = Role::where('name', '!=', 'company_admin')
            ->where('name', 'NOT LIKE', 'platform_%')
            ->pluck('name', 'name');

        return view('company.employees.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'position' => 'required|in:manager,dispatcher,accountant',
        ]);

        $password = Str::random(12);

        $employee = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($password),
            'company_id' => Auth::user()->company_id,
            'position' => $request->position,
            'type' => 'staff',
        ]);

        $employee->assignRole($request->position);

        // Здесь должна быть отправка приглашения с паролем
        // Mail::to($employee->email)->send(new EmployeeInvitationMail($employee, $password));

        return redirect()->route('company.employees.index')
            ->with('success', 'Сотрудник успешно добавлен');
    }

    public function edit(User $employee)
    {
        // Проверка что сотрудник принадлежит компании
        if ($employee->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $roles = Role::where('name', '!=', 'company_admin')
            ->where('name', 'NOT LIKE', 'platform_%')
            ->pluck('name', 'name');

        return view('company.employees.edit', compact('employee', 'roles'));
    }

    public function update(Request $request, User $employee)
    {
        // Проверка что сотрудник принадлежит компании
        if ($employee->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$employee->id,
            'phone' => 'required|string|max:20',
            'position' => 'required|in:manager,dispatcher,accountant',
        ]);

        $employee->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'position' => $request->position,
        ]);

        // Обновление ролей
        $employee->syncRoles([$request->position]);

        return redirect()->route('company.employees.index')
            ->with('success', 'Данные сотрудника обновлены');
    }

    public function destroy(User $employee)
    {
        // Проверка что сотрудник принадлежит компании
        if ($employee->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $employee->delete();

        return redirect()->route('company.employees.index')
            ->with('success', 'Сотрудник удален');
    }
}
