<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Upd;
use App\Services\OneCExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class UpdExportController extends Controller
{
    protected $exportService;

    public function __construct(OneCExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function exportTo1C(Upd $upd, Request $request)
    {
        $format = $request->get('format', 'xml');

        try {
            $content = $this->exportService->exportUpd($upd, $format);

            if ($request->get('download')) {
                $fileName = "УПД_{$upd->number}_{$upd->issue_date->format('Y-m-d')}.{$format}";

                return Response::make($content, 200, [
                    'Content-Type' => $format === 'xml' ? 'application/xml' : 'application/json',
                    'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
                ]);
            }

            // Сохраняем файл и возвращаем путь
            $filePath = $this->exportService->saveExportFile($upd, $content, $format);

            return response()->json([
                'message' => 'Файл успешно экспортирован',
                'file_path' => $filePath,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка экспорта УПД',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportMultiple(Request $request)
    {
        $request->validate([
            'upd_ids' => 'required|array',
            'upd_ids.*' => 'exists:upds,id',
            'format' => 'in:xml,json',
        ]);

        $format = $request->get('format', 'xml');
        $upds = Upd::whereIn('id', $request->upd_ids)->get();

        $archiveName = 'upd_export_'.now()->format('Ymd_His').'.zip';
        $zipPath = Storage::disk('local')->path("upd_exports/{$archiveName}");

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            foreach ($upds as $upd) {
                try {
                    $content = $this->exportService->exportUpd($upd, $format);
                    $fileName = "УПД_{$upd->number}_{$upd->issue_date->format('Y-m-d')}.{$format}";
                    $zip->addFromString($fileName, $content);
                } catch (\Exception $e) {
                    // Пропускаем проблемные УПД и продолжаем
                    continue;
                }
            }
            $zip->close();

            return response()->download($zipPath, $archiveName)->deleteFileAfterSend(true);
        }

        return response()->json([
            'message' => 'Ошибка создания архива',
        ], 500);
    }
}
