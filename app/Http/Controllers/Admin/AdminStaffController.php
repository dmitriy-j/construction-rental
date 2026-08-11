<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminStaffController extends Controller
{
    public function index()
    {
        $staff = User::with('roles')->orderBy('name')->paginate(25);
        $roles = \Spatie\Permission\Models\Role::whereIn('name', ['platform_super', 'platform_admin', 'company_admin'])->pluck('name', 'name');
        return view('admin.staff.index', compact('staff', 'roles'));
    }

    public function create()
    {
        $roles = \Spatie\Permission\Models\Role::whereIn('name', ['platform_super', 'platform_admin', 'company_admin'])->pluck('name', 'name');
        return view('admin.staff.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->syncRoles([$data['role']]);

        return redirect()->route('admin.staff.index')->with('success', 'Сотрудник создан');
    }

    public function edit(User $staff)
    {
        $roles = \Spatie\Permission\Models\Role::whereIn('name', ['platform_super', 'platform_admin', 'company_admin'])->pluck('name', 'name');
        return view('admin.staff.edit', compact('staff', 'roles'));
    }

    public function update(Request $request, User $staff)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $staff->id,
            'role' => 'required',
        ]);

        $staff->update(['name' => $data['name'], 'email' => $data['email']]);
        $staff->syncRoles([$data['role']]);

        return redirect()->route('admin.staff.index')->with('success', 'Сотрудник обновлен');
    }

    public function destroy(User $staff)
    {
        $staff->delete();
        return redirect()->route('admin.staff.index')->with('success', 'Сотрудник удален');
    }
}
