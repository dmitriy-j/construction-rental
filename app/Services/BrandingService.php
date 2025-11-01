<?php

namespace App\Services;

class BrandingService
{
    public static function replaceRentTechToFAP(string $content): string
    {
        $replacements = [
            'RentTech' => 'Федеральная Арендная Платформа',
            'renttech' => 'ФАП',
            'career@renttech.ru' => 'career@fap24.ru',
            'арендодателей и арендаторов' => 'бизнеса по всей России',
            'платформа для аренды' => 'федеральный оператор аренды',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}
