<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Services\DocumentGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DocumentTemplateController extends Controller
{
    public function index()
    {
        $templates = DocumentTemplate::all();

        return view('admin.settings.document-templates.index', compact('templates'));
    }

    public function create()
    {
        $templateTypes = [
            'путевой_лист' => 'Путевой лист',
            'акт' => 'Акт приема-передачи',
            'счет_на_оплату' => 'Счет на оплату',
            'договор' => 'Договор аренды',
            'упд' => 'УПД (Универсальный передаточный документ)',
            'акт_сверки' => 'Акт сверки',
            'транспортная_накладная' => 'Транспортная накладная',
            'акт_выполненных_работ' => 'Акт выполненных работ',
            'эсм_7' => 'ЭСМ-7 (Акт о сдаче-приемке выполненных работ)',
            'счет_фактура' => 'Счет-фактура'
        ];

        return view('admin.settings.document-templates.create', compact('templateTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_file' => 'required|file|mimes:xlsx,xls',
            'mapping' => 'required|json',
        ]);

        try {
            // Сохраняем файл шаблона
            $filePath = $request->file('template_file')->store('document-templates', 'public');

            // Создаем запись в базе данных
            DocumentTemplate::create([
                'name' => $request->name,
                'type' => $request->type,
                'description' => $request->description,
                'file_path' => $filePath,
                'mapping' => json_decode($request->mapping, true),
                'is_active' => $request->has('is_active'),
            ]);

            return redirect()->route('admin.settings.document-templates.index')
                ->with('success', 'Шаблон документа успешно создан');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка при создании шаблона: '.$e->getMessage());
        }
    }

    public function edit(DocumentTemplate $documentTemplate)
    {
        $templateTypes = [
            'путевой_лист' => 'Путевой лист',
            'акт' => 'Акт приема-передачи',
            'счет_на_оплату' => 'Счет на оплату',
            'договор' => 'Договор аренды',
            'упд' => 'УПД (Универсальный передаточный документ)',
            'акт_сверки' => 'Акт сверки',
        ];

        return view('admin.settings.document-templates.edit', compact('documentTemplate', 'templateTypes'));
    }

    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_file' => 'nullable|file|mimes:xlsx,xls',
            'mapping' => 'required|json',
        ]);

        try {
            $data = [
                'name' => $request->name,
                'type' => $request->type,
                'description' => $request->description,
                'mapping' => json_decode($request->mapping, true),
                'is_active' => $request->has('is_active'),
            ];

            // Обновляем файл, если загружен новый
            if ($request->hasFile('template_file')) {
                // Удаляем старый файл
                Storage::disk('public')->delete($documentTemplate->file_path);

                // Сохраняем новый файл
                $data['file_path'] = $request->file('template_file')->store('document-templates', 'public');
            }

            $documentTemplate->update($data);

            return redirect()->route('admin.settings.document-templates.index')
                ->with('success', 'Шаблон документа успешно обновлен');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка при обновлении шаблона: '.$e->getMessage());
        }
    }

    public function destroy(DocumentTemplate $documentTemplate)
    {
        try {
            // Удаляем файл
            Storage::disk('public')->delete($documentTemplate->file_path);

            // Удаляем запись из базы данных
            $documentTemplate->delete();

            return redirect()->route('admin.settings.document-templates.index')
                ->with('success', 'Шаблон документа успешно удален');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка при удалении шаблона: '.$e->getMessage());
        }
    }

    public function download(DocumentTemplate $documentTemplate)
    {
        return Storage::disk('public')->download($documentTemplate->file_path, $documentTemplate->name.'.xlsx');
    }

    public function preview(DocumentTemplate $documentTemplate)
    {
        // Загружаем файл для предварительного просмотра
        $filePath = Storage::disk('public')->path($documentTemplate->file_path);

        // Читаем файл с помощью PhpSpreadsheet
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Получаем данные для отображения
        $data = [];
        foreach ($worksheet->getRowIterator() as $row) {
            $rowData = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }

            $data[] = $rowData;
        }

        return view('admin.settings.document-templates.preview', compact('documentTemplate', 'data'));
    }

    public function generateForm(DocumentTemplate $documentTemplate)
    {
        // Получаем список сущностей, для которых можно генерировать документы
        $entities = $this->getAvailableEntities($documentTemplate->type);

        return view('admin.settings.document-templates.generate', compact('documentTemplate', 'entities'));
    }

    public function generate(Request $request, DocumentTemplate $documentTemplate, DocumentGeneratorService $generatorService)
    {
        $request->validate([
            'entity_type' => 'required|string',
            'entity_id' => 'required|integer',
        ]);

        try {
            // Получаем данные сущности
            $entityData = $this->getEntityData($request->entity_type, $request->entity_id);

            // Генерируем документ
            $filePath = $generatorService->generateDocument($documentTemplate, $entityData);

            // Возвращаем файл для скачивания
            return response()->download($filePath, $documentTemplate->name.'.xlsx')
                ->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка генерации документа: '.$e->getMessage());
        }
    }

    protected function getAvailableEntities($templateType)
    {
        // Возвращаем список сущностей в зависимости от типа шаблона
        switch ($templateType) {
            case 'путевой_лист':
                return \App\Models\Order::where('status', 'active')->get();
            case 'акт':
                return \App\Models\CompletionAct::all();
            case 'счет_на_оплату':
                return \App\Models\Invoice::where('status', 'pending')->get();
            default:
                return [];
        }
    }

    protected function getEntityData($entityType, $entityId)
    {
        // Получаем данные сущности в зависимости от типа
        switch ($entityType) {
            case 'order':
                $order = \App\Models\Order::with(['customer', 'equipment'])->findOrFail($entityId);

                return [
                    'order' => [
                        'id' => $order->id,
                        'number' => $order->order_number,
                        'date' => $order->created_at->format('d.m.Y'),
                        'total' => $order->total_amount,
                    ],
                    'customer' => [
                        'name' => $order->customer->name,
                        'phone' => $order->customer->phone,
                        'email' => $order->customer->email,
                    ],
                    'equipment' => [
                        'name' => $order->equipment->name,
                        'model' => $order->equipment->model,
                    ],
                ];
                // Добавьте другие case для разных типов сущностей
            default:
                throw new \Exception('Неизвестный тип сущности');
        }
    }
}
