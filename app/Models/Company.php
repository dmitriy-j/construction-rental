<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\User;

class Company extends Authenticatable
{
    use HasFactory, Notifiable; // Добавлен HasFactory

    protected $fillable = [
        'email',
        'password',
        'name',
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
        'manager'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
