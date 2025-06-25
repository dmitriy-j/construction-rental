<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;


class Company extends Authenticatable implements AuthenticatableContract
{
    use HasFactory, Authenticatable;

    protected $fillable = [
        'type', 'legal_name', 'tax_system', 'inn', 'kpp', 'ogrn', 'okpo',
        'legal_address', 'actual_address', 'bank_name', 'bank_account', 'bik',
        'correspondent_account', 'director_name', 'phone', 'contacts', 'email', 'password',
        'status', 'rejection_reason', 'verified_at'
    ];

    protected $hidden = ['password', 'remember_token'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
