<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Для API аутентификации

class Admin extends Authenticatable
{
     use HasFactory, HasRoles, HasApiTokens, Notifiable;


    protected $fillable = [
        'email', 'password', 'last_name', 'first_name', 'middle_name',
        'birth_date', 'address', 'phone', 'position'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
    public function getFullNameAttribute()
    {
        return trim($this->last_name . ' ' . $this->first_name . ' ' . $this->middle_name);
    }

}
