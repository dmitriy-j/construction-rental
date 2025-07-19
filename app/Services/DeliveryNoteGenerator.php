<?php

namespace App\Services;

use App\Models\DeliveryNote;
use App\Models\Platform;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class DeliveryNoteGenerator
{
    protected $platform;

    public function __construct()
    {
        $this->platform = Platform::first() ?? new Platform();
    }

    public function generateAndSave(DeliveryNote $note): string
    {
        $pdfContent = $this->generatePdf($note);
        $fileName = 'delivery_notes/' . $note->document_number . '.pdf';

        Storage::put($fileName, $pdfContent);
        return $fileName;
    }

    private function generatePdf(DeliveryNote $note)
    {
        // Определяем путь к шаблону в зависимости от типа пользователя
        $view = auth()->user()->isLessor()
            ? 'lessor.documents.delivery-note'
            : 'lessee.documents.delivery-note';

        return Pdf::loadView($view, [
            'note' => $note,
            'platform' => $this->platform,
            'currentDate' => now()->format('d.m.Y')
        ])->output();
    }
}
