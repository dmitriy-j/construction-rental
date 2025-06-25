<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Mail\CompanyRegisteredMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Company;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'role',
        'company_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function isStaff(): bool
    {
        return $this->type === 'staff';
    }

    public function isTenant(): bool
    {
        return $this->type === 'tenant';
    }

    public function isLandlord(): bool
    {
        return $this->type === 'landlord';
    }

    public function isAdmin(): bool
    {
        return $this->type === 'admin' &&
           in_array($this->role, [
               'platform_support',
               'platform_moder',
               'platform_manager',
               'platform_super'
           ]);
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }
}
