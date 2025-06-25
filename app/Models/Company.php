<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type', // Добавлено: тип компании (landlord/tenant)
        'vat',
        'inn',
        'kpp',
        'ogrn',
        'okpo',
        'legal_address',
        'actual_address',
        'same_address',
        'bank_name',
        'bank_account',
        'bik',
        'correspondent_account',
        'director',
        'phone',
        'manager',
        'status', // Добавлено: статус верификации
        'rejection_reason', // Добавлено: причина отклонения
        'verified_at' // Добавлено: дата верификации
    ];

    // Убраны аутентификационные поля
    // Убрано наследование от Authenticatable
    // Убраны скрытые поля (password, remember_token)

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
