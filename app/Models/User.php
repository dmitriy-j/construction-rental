<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

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
            'platform_support',
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

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_user', 'user_id', 'location_id');
        // Если промежуточная таблица следует соглашениям именования Laravel (location_user),
        // и внешние ключи называются 'user_id' и 'location_id', то сигнатура может быть упрощена до:
        // return $this->belongsToMany(Location::class);
    }

    public function equipment(): HasMany // Указываем тип возвращаемого значения
    {
        return $this->hasMany(Equipment::class);
        // Если внешний ключ называется не `user_id`, укажите его явно:
        // return $this->hasMany(Equipment::class, 'owner_id');
    }

    public function rentalRequests()
    {
        return $this->hasMany(RentalRequest::class);
    }
    public function requestResponses()
    {
        return $this->hasMany(RentalRequestResponse::class, 'lessor_id');
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
        return $this->company && $this->company->is_lessee;
    }

    public function isLessor(): bool
    {
        return $this->company && $this->company->is_lessor;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['platform_super', 'platform_admin']);
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

    public function hasAnyRole(array $roles): bool
    {
        return $this->hasRole($roles);
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification());
    }
}
