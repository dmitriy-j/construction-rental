<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $employees = Admin::with('roles')->get();
        return view('admin.employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new employee.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $roles = Role::where('guard_name', 'admin')->get();
        $permissions = Permission::where('guard_name', 'admin')->get();
        return view('admin.employees.create', compact('roles', 'permissions'));
    }

    /**
     * Store a newly created employee.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:admins',
            'password' => 'required|min:8|confirmed',
            'last_name' => 'required|string|max:50',
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'birth_date' => 'required|date|before:-18 years',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20|regex:/^\+?[0-9\s\-\(\)]+$/',
            'position' => 'required|string|max:100',
            'role' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ], [
            'birth_date.before' => 'Сотрудник должен быть старше 18 лет',
            'phone.regex' => 'Неверный формат телефона',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        // Создание сотрудника
        $employee = Admin::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'birth_date' => $data['birth_date'],
            'address' => $data['address'],
            'phone' => $data['phone'],
            'position' => $data['position'],
        ]);

        // Назначение роли
        $role = Role::findById($data['role'], 'admin');
        $employee->assignRole($role);

        // Назначение дополнительных прав
        if (!empty($data['permissions'])) {
            $permissions = Permission::whereIn('id', $data['permissions'])->get();
            $employee->givePermissionTo($permissions);
        }

        return redirect()->route('admin.employees.index')
            ->with('success', 'Сотрудник успешно создан');
    }

    /**
     * Show the form for editing the specified employee.
     *
     * @param  Admin  $employee
     * @return \Illuminate\View\View
     */
    public function edit(Admin $employee)
    {
        $roles = Role::where('guard_name', 'admin')->get();
        $permissions = Permission::where('guard_name', 'admin')->get();
        $employeePermissions = $employee->permissions->pluck('id')->toArray();

        return view('admin.employees.edit', compact('employee', 'roles', 'permissions', 'employeePermissions'));
    }

    /**
     * Update the specified employee.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Admin  $employee
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Admin $employee)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:admins,email,' . $employee->id,
            'password' => 'nullable|min:8|confirmed',
            'last_name' => 'required|string|max:50',
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'birth_date' => 'required|date|before:-18 years',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20|regex:/^\+?[0-9\s\-\(\)]+$/',
            'position' => 'required|string|max:100',
            'role' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ], [
            'birth_date.before' => 'Сотрудник должен быть старше 18 лет',
            'phone.regex' => 'Неверный формат телефона',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        // Обновление данных
        $updateData = [
            'email' => $data['email'],
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'birth_date' => $data['birth_date'],
            'address' => $data['address'],
            'phone' => $data['phone'],
            'position' => $data['position'],
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $employee->update($updateData);

        // Обновление роли
        $role = Role::findById($data['role'], 'admin');
        $employee->syncRoles([$role]);

        // Обновление прав
        $permissions = !empty($data['permissions'])
            ? Permission::whereIn('id', $data['permissions'])->get()
            : [];

        $employee->syncPermissions($permissions);

        return redirect()->route('admin.employees.index')
            ->with('success', 'Данные сотрудника обновлены');
    }

    /**
     * Remove the specified employee.
     *
     * @param  Admin  $employee
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Admin $employee)
    {
        // Запрет удаления самого себя
        if ($employee->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'Вы не можете удалить самого себя');
        }

        $employee->delete();
        return redirect()->route('admin.employees.index')
            ->with('success', 'Сотрудник удален');
    }
}
