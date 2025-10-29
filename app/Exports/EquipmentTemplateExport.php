<?php
// app/Exports/EquipmentTemplateExport.php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EquipmentTemplateExport implements FromArray, WithHeadings, WithTitle, WithStrictNullComparison, WithColumnWidths, WithStyles
{
    public function array(): array
    {
        $categories = Category::all();

        $examples = [
            [
                'Гусеничный экскаватор CAT 336',
                'Гусеничный экскаватор в отличном техническом состоянии, полный сервис, готов к работе',
                $categories->where('name', 'like', '%экскаватор%')->first()->id ?? '1',
                'Caterpillar',
                '336',
                '2021',
                '1250.5',
                '3200',
                'Склад Москва',
                'г. Москва, ул. Промышленная, 15',
                '38500',
                '11.2',
                '3.4',
                '3.8'
            ],
            [
                'Фронтальный погрузчик Volvo L150H',
                'Фронтальный погрузчик с ковшом 3.5м³, низкая наработка',
                $categories->where('name', 'like', '%погрузчик%')->first()->id ?? '3',
                'Volvo',
                'L150H',
                '2022',
                '650',
                '2800',
                'Склад Казань',
                'г. Казань, ул. Заводская, 28',
                '18200',
                '8.5',
                '2.9',
                '3.6'
            ]
        ];

        return $examples;
    }

    public function headings(): array
    {
        // Используем транслитерированные заголовки для совместимости
        return [
            'nazvanie_texniki',
            'opisanie',
            'id_kategorii',
            'brend',
            'model',
            'god_vypuska',
            'narabotka_casy',
            'cena_za_cas_rub',
            'nazvanie_lokacii',
            'adres_lokacii',
            'ves_kg',
            'dlina_m',
            'sirina_m',
            'vysota_m'
        ];
    }

    public function title(): string
    {
        return 'Шаблон загрузки';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 35,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 18,
            'H' => 18,
            'I' => 20,
            'J' => 30,
            'K' => 12,
            'L' => 12,
            'M' => 12,
            'N' => 12,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Простые стили для заголовков
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);
        $sheet->getStyle('A1:N1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A1:N1')->getFill()->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:N1')->getFont()->getColor()->setARGB('FFFFFFFF');

        // Добавляем пояснения
        $sheet->setCellValue('A5', 'Пояснения по заполнению:');
        $sheet->setCellValue('A6', '1. Все поля обязательны для заполнения');
        $sheet->setCellValue('A7', '2. ID категории можно посмотреть в списке категорий в личном кабинете');
        $sheet->setCellValue('A8', '3. Числовые значения вводить без единиц измерения (только цифры)');
        $sheet->setCellValue('A9', '4. Удалите примеры перед заполнением своими данными');

        // Стиль для пояснений
        $sheet->getStyle('A5:A9')->getFont()->setBold(true);
        $sheet->getStyle('A5:A9')->getFont()->getColor()->setARGB('FFFF0000');

        return [];
    }
}
