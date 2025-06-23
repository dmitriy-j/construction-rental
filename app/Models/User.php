<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Mail\CompanyRegisteredMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Company;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'type',
        'company_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Методы проверки типа пользователя и ролей
    public function isStaff(): bool
    {
        return $this->type === 'staff';
    }

    public function isCustomer(): bool
    {
        return $this->type === 'customer';
    }

    public function isAdmin(): bool
    {
        return $this->isStaff() && $this->role === 'admin';
    }

    public function hasRole(string $role): bool
    {
        return $this->isStaff() && $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->isStaff() && in_array($this->role, $roles);
    }
}
