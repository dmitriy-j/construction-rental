<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

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
        'company_id',
    ];

    protected $casts = [
        'additional_phones' => 'array',
    ];

    public function getCleanPhoneAttribute(): string
    {
        return preg_replace('/[^0-9]/', '', $this->phone ?? '');
    }

    public static function getMain()
    {
        $platform = static::with('company')->first();

        if (!$platform) {
            // Создаем минимальный корректный экземпляр с безопасными значениями
            $platform = new static([
                'name' => 'Федеральная Арендная Платформа',
                'short_name' => 'ФАП',
                'legal_address' => 'г. Москва',
                'phone' => '8 (800) 123-45-67',
                'email' => 'info@fap24.ru',
                'website' => 'https://fap24.ru',
                'ceo_name' => 'Директор',
                'ceo_position' => 'Генеральный директор',
            ]);
        }

        return $platform;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Получить форматированные реквизиты для документов
     */
    public function getLegalDetailsAttribute(): array
    {
        return [
            'name' => $this->name ?? 'Федеральная Арендная Платформа',
            'short_name' => $this->short_name ?? 'ФАП',
            'inn' => $this->inn ?? '',
            'kpp' => $this->kpp ?? '',
            'ogrn' => $this->ogrn ?? '',
            'okpo' => $this->okpo ?? '',
            'okved' => $this->okved ?? '',
            'okato' => $this->okato ?? '',
            'legal_address' => $this->legal_address ?? 'г. Москва',
            'bank_details' => [
                'name' => $this->bank_name ?? '',
                'city' => $this->bank_city ?? '',
                'bic' => $this->bic ?? '',
                'correspondent' => $this->correspondent_account ?? '',
                'settlement' => $this->settlement_account ?? '',
            ],
            'management' => [
                'ceo' => [
                    'name' => $this->ceo_name ?? 'Директор',
                    'position' => $this->ceo_position ?? 'Генеральный директор',
                    'basis' => $this->ceo_basis ?? '',
                ],
                'accountant' => [
                    'name' => $this->accountant_name ?? '',
                    'position' => $this->accountant_position ?? '',
                ],
            ],
            'contacts' => [
                'phone' => $this->phone ?? '8 (800) 123-45-67',
                'email' => $this->email ?? 'info@fap24.ru',
                'website' => $this->website ?? 'https://fap24.ru',
            ],
            'certificate' => $this->certificate_number ?? '',
        ];
    }

    public function getLegalNameAttribute()
    {
        return $this->company->legal_name ?? $this->name ?? 'Федеральная Арендная Платформа';
    }
}
