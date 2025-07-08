<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'birth_date',
        'address',
        'position',
        'status',
        'company_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Проверка ролей платформы
    public function isPlatformAdmin(): bool
    {
        return $this->hasRole([
            'platform_super', 
            'platform_admin',
            'platform_support'
        ]);
    }
    
    public function isCompanyAdmin(): bool
    {
        return $this->hasRole('company_admin');
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    protected static function booted(): void
    {
        static::created(function ($user) {
            $user->cart()->create();
        });
    }
    
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isLessee(): bool
    {
        return $this->company->is_lessee;
    }

    public function isLessor(): bool
    {
        return $this->company->is_lessor;
    }

    public function cartItemsCount(): int
    {
        return $this->cart->items->count() ?? 0;
    }

    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
                    ->orderBy('created_at', 'desc');
    }

}
