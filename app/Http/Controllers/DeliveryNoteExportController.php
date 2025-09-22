<?php

namespace App\Http\Controllers;

use App\Exports\DeliveryNoteExport;
use App\Models\DeliveryNote;
use App\Services\DeliveryNoteGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class DeliveryNoteExportController extends Controller
{
    public function exportExcel(DeliveryNote $note)
    {
        $user = auth()->user();

        $isAuthorized = ($user->isLessee() && $note->receiver_company_id === $user->company_id) ||
                        ($user->isLessor() && $note->sender_company_id === $user->company_id);

        if (! $isAuthorized) {
            abort(403, 'Доступ запрещен');
        }

        return Excel::download(
            new DeliveryNoteExport($note),
            "Транспортная_накладная_{$note->document_number}.xlsx"
        );
    }

    public function exportPdf(DeliveryNote $note)
    {
        $user = auth()->user();

        $isAuthorized = ($user->isLessee() && $note->receiver_company_id === $user->company_id) ||
                        ($user->isLessor() && $note->sender_company_id === $user->company_id);

        if (! $isAuthorized) {
            abort(403, 'Доступ запрещен');
        }

        // Генерируем PDF, если он ещё не создан
        if (! $note->document_path || ! Storage::exists($note->document_path)) {
            $generator = new DeliveryNoteGenerator;
            $pdfPath = $generator->generateAndSave($note);
            $note->update(['document_path' => $pdfPath]);
        }

        return Pdf::loadView('documents.delivery-note-lessee', [
            'note' => $note,
            'platform' => \App\Models\Platform::getMain(),
            'currentDate' => now()->format('d.m.Y'),
        ])->download("ТН_{$note->document_number}.pdf");
    }
}
