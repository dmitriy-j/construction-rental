<?php

namespace App\Exports;

use App\Models\DeliveryNote;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeliveryNoteExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $note;

    public function __construct(DeliveryNote $note)
    {
        $this->note = $note;
    }

    public function query()
    {
        return DeliveryNote::whereId($this->note->id)
            ->with([
                'senderCompany',
                'receiverCompany',
                'deliveryFrom',
                'deliveryTo',
                'orderItem.equipment',
            ]);
    }

    public function headings(): array
    {
        return [
            'Номер документа',
            'Дата составления',
            'Отправитель',
            'ИНН отправителя',
            'Получатель',
            'ИНН получателя',
            'Пункт погрузки',
            'Пункт разгрузки',
            'Описание груза',
            'Вес груза (кг)',
            'Транспортное средство',
            'Гос. номер',
            'Водитель',
            'Контакт водителя',
            'Статус',
            'Дата доставки',
        ];
    }

    public function map($note): array
    {
        return [
            $note->document_number,
            $note->issue_date->format('d.m.Y'),
            $note->senderCompany->legal_name,
            $note->senderCompany->inn,
            $note->receiverCompany->legal_name,
            $note->receiverCompany->inn,
            $note->deliveryFrom->address,
            $note->deliveryTo->address,
            $note->cargo_description,
            $note->cargo_weight,
            $note->transport_vehicle_model,
            $note->transport_vehicle_number,
            $note->transport_driver_name,
            $note->driver_contact,
            $note->status_text,
            $note->delivery_date?->format('d.m.Y') ?? 'В пути',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'D9E1F2']],
            ],
        ];
    }
}
