<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ExcelMapping;
use App\Services\Parsers\UpdParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelMappingController extends Controller
{
    protected $updParserService;

    public function __construct(UpdParserService $updParserService)
    {
        $this->updParserService = $updParserService;
    }

    public function index()
    {
        $mappings = ExcelMapping::with('company')
            ->updTemplates()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.excel-mappings.index', compact('mappings'));
    }

    public function create()
    {
        $companies = Company::where('is_lessor', true)->get();
        $defaultConfig = ExcelMapping::getDefaultUpdMappingConfig();
        $parsers = $this->getAvailableParsers();

        return view('admin.excel-mappings.create', compact('companies', 'defaultConfig', 'parsers'));
    }

    protected function getAvailableParsers(): array
    {
        return [
            'none' => 'Без обработки',
            'inn_kpp' => 'Извлечь ИНН/КПП из одной ячейки',
            'split_slash' => 'Разделить по слешу',
            'extract_numbers' => 'Извлечь только цифры',
            // Добавьте другие парсеры по необходимости
        ];
    }

    public function store(Request $request)
    {
        \Log::debug('=== START SAVING TEMPLATE ===');
        \Log::debug('All request data:', $request->all());

        try {
            // Валидация
            $validated = $request->validate([
                'company_id' => 'required|exists:companies,id',
                'name' => 'required|string|max:255',
                'mapping' => 'required|array',
                'file_example' => 'required|file|mimes:xlsx,xls',
                'is_active' => 'sometimes|boolean',
            ]);

            \Log::debug('Validation passed');

            // Проверяем кодировку JSON данных
            $jsonTest = json_encode($request->mapping);
            if ($jsonTest === false) {
                $error = json_last_error_msg();
                \Log::error('JSON encoding failed:', ['error' => $error, 'data' => $request->mapping]);
                throw new \Exception('Ошибка в данных маппинга: '.$error);
            }

            \Log::debug('JSON encoding successful');

            // Сохраняем файл
            $filePath = $request->file('file_example')->store('excel-templates');
            \Log::debug('File saved to:', ['path' => $filePath]);

            // Подготовка данных для сохранения
            $mappingData = [
                'company_id' => $request->company_id,
                'name' => $request->name,
                'type' => 'upd',
                'mapping' => json_encode($request->mapping), // Преобразуем массив в JSON строку
                'file_example_path' => $filePath,
                'is_active' => $request->boolean('is_active', false),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            \Log::debug('Data to save:', $mappingData);

            // Пробуем сохранить через DB facade
            $id = DB::table('excel_mappings')->insertGetId($mappingData);
            \Log::debug('Template saved via DB facade, ID:', ['id' => $id]);

            // Проверяем, что запись действительно создалась
            $savedMapping = DB::table('excel_mappings')->find($id);
            \Log::debug('Saved record:', (array) $savedMapping);

            return redirect()->route('admin.excel-mappings.index')
                ->with('success', 'Шаблон УПД успешно создан.');

        } catch (\Exception $e) {
            \Log::error('Error saving template:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'code' => $e->getCode(),
            ]);

            return redirect()->back()
                ->with('error', 'Ошибка создания шаблона: '.$e->getMessage())
                ->withInput();
        }
    }

    public function show(ExcelMapping $excelMapping)
    {
        $excelMapping->load('company');
        // Тестовый парсинг примера файла
        try {
            $parsedData = $this->updParserService->parseUpdFromExcel(
                Storage::path($excelMapping->file_example_path),
                $excelMapping->mapping // Исправлено: было mapping_config, стало mapping
            );

            return view('admin.excel-mappings.show', compact('excelMapping', 'parsedData'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка тестового парсинга: '.$e->getMessage());
        }
    }

    public function edit(ExcelMapping $excelMapping)
    {
        $companies = Company::where('is_lessor', true)->get();
        $defaultConfig = ExcelMapping::getDefaultUpdMappingConfig();
        $parsers = $this->getAvailableParsers();

        return view('admin.excel-mappings.edit', compact('excelMapping', 'companies', 'defaultConfig', 'parsers'));
    }

    public function update(Request $request, ExcelMapping $excelMapping)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'mapping' => 'required|array', // Исправлено: было mapping_config, стало mapping
            'file_example' => 'nullable|file|mimes:xlsx,xls',
            'is_active' => 'boolean',
        ]);
        try {
            $data = [
                'company_id' => $request->company_id,
                'name' => $request->name,
                'mapping' => $request->mapping, // Исправлено: было mapping_config, стало mapping
                'is_active' => $request->is_active ?? false,
            ];
            if ($request->hasFile('file_example')) {
                // Проверяем новую конфигурацию на новом файле
                $filePath = $request->file('file_example')->store('temp');
                $fullPath = Storage::path($filePath);
                $this->updParserService->parseUpdFromExcel($fullPath, $request->mapping); // Исправлено: mapping_config -> mapping
                // Удаляем старый файл
                if ($excelMapping->file_example_path) {
                    Storage::delete($excelMapping->file_example_path);
                }
                $data['file_example_path'] = $request->file('file_example')->store('excel-templates');
                Storage::delete($filePath);
            } else {
                // Проверяем новую конфигурацию на старом файле
                $this->updParserService->parseUpdFromExcel(
                    Storage::path($excelMapping->file_example_path),
                    $request->mapping // Исправлено: mapping_config -> mapping
                );
            }
            $excelMapping->update($data);

            return redirect()->route('admin.excel-mappings.index')
                ->with('success', 'Шаблон УПД успешно обновлен.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка обновления шаблона: '.$e->getMessage())
                ->withInput();
        }
    }

    public function destroy(ExcelMapping $excelMapping)
    {
        try {
            if ($excelMapping->file_example_path) {
                Storage::delete($excelMapping->file_example_path);
            }

            $excelMapping->delete();

            return redirect()->route('admin.excel-mappings.index')
                ->with('success', 'Шаблон УПД успешно удален.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка удаления шаблона: '.$e->getMessage());
        }
    }

    public function testParse(ExcelMapping $excelMapping)
    {
        try {
            $parsedData = $this->updParserService->parseUpdFromExcel(
                Storage::path($excelMapping->file_example_path),
                $excelMapping->mapping_config
            );

            $this->updParserService->validateParsedData($parsedData);

            return response()->json([
                'success' => true,
                'data' => $parsedData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function downloadExample(ExcelMapping $excelMapping): StreamedResponse
    {
        // Проверяем существование файла
        if (! Storage::exists($excelMapping->file_example_path)) {
            abort(404, 'Файл не найден');
        }

        // Генерируем имя файла для скачивания
        $fileName = 'пример_шаблона_'.$excelMapping->name.'.'.
                    pathinfo($excelMapping->file_example_path, PATHINFO_EXTENSION);

        return Storage::download($excelMapping->file_example_path, $fileName);
    }
}
