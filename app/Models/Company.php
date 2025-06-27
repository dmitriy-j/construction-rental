<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'legal_name', 'tax_system', 'inn', 'kpp', 'ogrn', 'okpo',
        'legal_address', 'actual_address', 'bank_name', 'bank_account', 'bik',
        'correspondent_account', 'director_name', 'phone', 'contacts', 'contact_email',
        'status', 'rejection_reason', 'verified_at'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
