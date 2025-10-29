<?php
// app/Http/Controllers/Lessor/EquipmentMassImportController.php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\EquipmentImport;
use App\Models\Category;
use App\Services\EquipmentMassImportService;
use App\Exports\EquipmentTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EquipmentMassImportController extends Controller
{
    protected $categories;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->categories = Category::all();
            return $next($request);
        });
    }

    public function create()
    {
        $recentImports = EquipmentImport::where('company_id', auth()->user()->company_id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('lessor.equipment.mass-import.create', [
            'categories' => $this->categories,
            'recentImports' => $recentImports,
        ]);
    }

    public function store(Request $request, EquipmentMassImportService $importService)
    {
        Log::info("Начало обработки запроса массового импорта", [
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id,
            'file_name' => $request->file('import_file')?->getClientOriginalName()
        ]);

        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            // Сохраняем файл
            $file = $request->file('import_file');
            $fileName = 'import_' . time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('temp_imports', $fileName);

            Log::info("Файл сохранен", [
                'file_name' => $fileName,
                'file_path' => $filePath,
                'original_name' => $file->getClientOriginalName()
            ]);

            // Создаем запись об импорте
            $importRecord = EquipmentImport::create([
                'company_id' => auth()->user()->company_id,
                'file_name' => $fileName,
                'original_name' => $file->getClientOriginalName(),
                'status' => 'pending',
            ]);

            Log::info("Запись импорта создана", [
                'import_id' => $importRecord->id,
                'company_id' => $importRecord->company_id
            ]);

            // Обрабатываем импорт
            $importService->processImport(
                storage_path('app/' . $filePath),
                auth()->user()->company_id,
                $importRecord->id
            );

            // Очищаем временный файл
            Storage::delete($filePath);
            Log::info("Временный файл удален", ['file_path' => $filePath]);

            Log::info("Импорт успешно завершен", [
                'import_id' => $importRecord->id,
                'successful_rows' => $importRecord->successful_rows,
                'total_rows' => $importRecord->total_rows
            ]);

            return redirect()
                ->route('lessor.equipment.index')
                ->with('success', "Успешно импортировано {$importRecord->successful_rows} из {$importRecord->total_rows} единиц техники");

        } catch (\Exception $e) {
            Log::error("Ошибка при массовом импорте", [
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Удаляем временный файл в случае ошибки
            if (isset($filePath)) {
                Storage::delete($filePath);
                Log::info("Временный файл удален после ошибки", ['file_path' => $filePath]);
            }

            return back()
                ->with('error', 'Ошибка импорта: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function downloadTemplate()
    {
        Log::info("Скачивание шаблона массового импорта", [
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id
        ]);

        return Excel::download(new EquipmentTemplateExport, 'шаблон_массовой_загрузки_техники.xlsx');
    }

    public function show(EquipmentImport $import)
    {
        $this->authorize('view', $import);

        Log::info("Просмотр результата импорта", [
            'import_id' => $import->id,
            'user_id' => auth()->id()
        ]);

        return view('lessor.equipment.mass-import.show', compact('import'));
    }
}
