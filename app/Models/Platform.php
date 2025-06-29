<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Platform extends Model
{
    protected $fillable = [
        'name',
        'short_name',
        'inn',
        'kpp',
        'ogrn',
        'okpo',
        'okved',
        'okato',
        'certificate_number',
        'ceo_basis',
        'legal_address',
        'physical_address',
        'post_address',
        'bank_name',
        'bank_city',
        'bic',
        'correspondent_account',
        'settlement_account',
        'website',
        'email',
        'phone',
        'additional_phones',
        'ceo_name',
        'ceo_position',
        'accountant_name',
        'accountant_position',
        'notes',
        'signature_image_path',
        'stamp_image_path',
    ];

    protected $casts = [
        'additional_phones' => 'array',
    ];

    /*protected function settlementAccount(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => encrypt($value),
            get: fn ($value) => decrypt($value),
        );
    }*/

    /*protected function correspondentAccount(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => encrypt($value),
            get: fn ($value) => decrypt($value),
        );
    }*/

    public function getCleanPhoneAttribute(): string
    {
        return preg_replace('/[^0-9]/', '', $this->phone);
    }

    public static function getMain(): self
    {
        return static::firstOrFail();
    }

    /**
     * Получить форматированные реквизиты для документов
     */
    public function getLegalDetailsAttribute(): array
    {
        return [
            'name' => $this->name,
            'short_name' => $this->short_name,
            'inn' => $this->inn,
            'kpp' => $this->kpp,
            'ogrn' => $this->ogrn,
            'okpo' => $this->okpo,
            'okved' => $this->okved,
            'okato' => $this->okato,
            'legal_address' => $this->legal_address,
            'bank_details' => [
                'name' => $this->bank_name,
                'city' => $this->bank_city,
                'bic' => $this->bic,
                'correspondent' => $this->correspondent_account,
                'settlement' => $this->settlement_account
            ],
            'management' => [
                'ceo' => [
                    'name' => $this->ceo_name,
                    'position' => $this->ceo_position,
                    'basis' => $this->ceo_basis
                ],
                'accountant' => [
                    'name' => $this->accountant_name,
                    'position' => $this->accountant_position
                ]
            ],
            'contacts' => [
                'phone' => $this->phone,
                'email' => $this->email,
                'website' => $this->website
            ],
            'certificate' => $this->certificate_number
        ];
    }
}
