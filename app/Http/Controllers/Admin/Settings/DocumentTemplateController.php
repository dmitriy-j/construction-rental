<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Services\DocumentGeneratorService;
use App\Services\InvoiceGeneratorService;
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
        $templateTypes = DocumentTemplate::getTypes();
        $scenarios = DocumentTemplate::getScenarios(); // Убедитесь, что эта строка есть

        return view('admin.settings.document-templates.create', compact('templateTypes', 'scenarios'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(DocumentTemplate::getTypes())),
            'scenario' => 'required|string|in:' . implode(',', array_keys(DocumentTemplate::getScenarios())),
            'description' => 'nullable|string',
            'template_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'mapping' => 'required|json',
        ]);

        try {
            // Обработка файла шаблона
            if ($request->hasFile('template_file')) {
                $file = $request->file('template_file');
                $fileName = $validated['type'] . '_' . $validated['scenario'] . '_' . time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('document_templates', $fileName, 'public');
                $validated['file_path'] = $filePath;
            }

            // Добавляем mapping и is_active
            $validated['mapping'] = json_decode($request->mapping, true);
            $validated['is_active'] = $request->has('is_active');

            $template = DocumentTemplate::create($validated);

            return redirect()->route('admin.settings.document-templates.index')
                ->with('success', 'Шаблон документа успешно создан');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка при создании шаблона: '.$e->getMessage());
        }
    }

    public function edit(DocumentTemplate $documentTemplate)
    {
        $templateTypes = DocumentTemplate::getTypes();
        $scenarios = DocumentTemplate::getScenarios(); // И здесь

        return view('admin.settings.document-templates.edit', compact('documentTemplate', 'templateTypes', 'scenarios'));
    }

    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(DocumentTemplate::getTypes())),
            'scenario' => 'required|string|in:' . implode(',', array_keys(DocumentTemplate::getScenarios())),
            'description' => 'nullable|string',
            'template_file' => 'nullable|file|mimes:xlsx,xls,csv|max:10240',
            'mapping' => 'required|json',
        ]);

        try {
            $data = [
                'name' => $validated['name'],
                'type' => $validated['type'],
                'scenario' => $validated['scenario'],
                'description' => $validated['description'],
                'mapping' => json_decode($request->mapping, true),
                'is_active' => $request->has('is_active'),
            ];

            // Обновляем файл, если загружен новый
            if ($request->hasFile('template_file')) {
                // Удаляем старый файл
                Storage::disk('public')->delete($documentTemplate->file_path);

                $file = $request->file('template_file');
                $fileName = $validated['type'] . '_' . $validated['scenario'] . '_' . time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('document_templates', $fileName, 'public');
                $data['file_path'] = $filePath;
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
        $entities = $this->getAvailableEntities($documentTemplate->type, $documentTemplate->scenario);

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
            $entityData = $this->getEntityData($request->entity_type, $request->entity_id, $documentTemplate->scenario);

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

    public function generateInvoiceForm()
    {
        $invoiceTemplates = DocumentTemplate::active()
            ->byType(DocumentTemplate::TYPE_INVOICE)
            ->get();

        $orders = \App\Models\Order::whereIn('status', ['active', 'completed'])->get();
        $upds = \App\Models\Upd::where('status', 'accepted')->get();

        return view('admin.settings.document-templates.generate-invoice', compact('invoiceTemplates', 'orders', 'upds'));
    }

    public function generateInvoice(Request $request, InvoiceGeneratorService $invoiceService)
    {
        $request->validate([
            'template_id' => 'required|exists:document_templates,id',
            'order_id' => 'required_without:upd_id|exists:orders,id',
            'upd_id' => 'nullable|exists:upds,id',
            'scenario' => 'required|string|in:' . implode(',', array_keys(DocumentTemplate::getInvoiceScenarios())),
        ]);

        try {
            $order = \App\Models\Order::findOrFail($request->order_id);
            $upd = $request->upd_id ? \App\Models\Upd::findOrFail($request->upd_id) : null;
            $template = DocumentTemplate::findOrFail($request->template_id);

            // Генерируем счет
            $filePath = $invoiceService->generateInvoice($order, $request->scenario, $upd);

            return response()->download($filePath, "invoice_{$order->order_number}.xlsx")
                ->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка генерации счета: '.$e->getMessage());
        }
    }

    protected function getAvailableEntities($templateType, $scenario)
    {
        // Возвращаем список сущностей в зависимости от типа шаблона и сценария
        switch ($templateType) {
            case DocumentTemplate::TYPE_WAYBILL:
                return \App\Models\Order::where('status', 'active')->get();
            case DocumentTemplate::TYPE_ACT:
                return \App\Models\CompletionAct::all();
            case DocumentTemplate::TYPE_INVOICE:
                return $this->getInvoiceEntities($scenario);
            case DocumentTemplate::TYPE_UPD:
                return \App\Models\Order::where('status', 'completed')->get();
            default:
                return [];
        }
    }

    protected function getInvoiceEntities($scenario)
    {
        switch ($scenario) {
            case DocumentTemplate::SCENARIO_INVOICE_ORDER:
                return \App\Models\Order::whereIn('status', ['active', 'pending'])->get();
            case DocumentTemplate::SCENARIO_INVOICE_UPD:
                return \App\Models\Upd::where('status', 'accepted')->get();
            case DocumentTemplate::SCENARIO_INVOICE_ADVANCE:
                return \App\Models\Order::where('status', 'pending')->get();
            default:
                return [];
        }
    }

    protected function getEntityData($entityType, $entityId, $scenario)
    {
        // Получаем данные сущности в зависимости от типа и сценария
        switch ($entityType) {
            case 'order':
                $order = \App\Models\Order::with(['customer', 'equipment', 'lessorCompany', 'lesseeCompany'])->findOrFail($entityId);
                return $this->prepareOrderData($order, $scenario);

            case 'upd':
                $upd = \App\Models\Upd::with(['order', 'lessorCompany', 'lesseeCompany'])->findOrFail($entityId);
                return $this->prepareUpdData($upd, $scenario);

            case 'completion_act':
                $act = \App\Models\CompletionAct::with(['order', 'waybill'])->findOrFail($entityId);
                return $this->prepareActData($act, $scenario);

            default:
                throw new \Exception('Неизвестный тип сущности');
        }
    }

    protected function prepareOrderData($order, $scenario)
    {
        $data = [
            'order' => [
                'id' => $order->id,
                'number' => $order->order_number,
                'date' => $order->created_at->format('d.m.Y'),
                'start_date' => $order->start_date?->format('d.m.Y'),
                'end_date' => $order->end_date?->format('d.m.Y'),
                'total_amount' => $order->total_amount,
                'advance_amount' => $order->advance_amount ?? 0,
                'remaining_amount' => $order->total_amount - ($order->advance_amount ?? 0),
            ],
            'customer' => [
                'name' => $order->customer->name,
                'phone' => $order->customer->phone,
                'email' => $order->customer->email,
            ],
            'lessor_company' => [
                'name' => $order->lessorCompany->name,
                'legal_name' => $order->lessorCompany->legal_name,
                'inn' => $order->lessorCompany->inn,
                'kpp' => $order->lessorCompany->kpp,
                'address' => $order->lessorCompany->address,
            ],
            'lessee_company' => [
                'name' => $order->lesseeCompany->name,
                'legal_name' => $order->lesseeCompany->legal_name,
                'inn' => $order->lesseeCompany->inn,
                'kpp' => $order->lesseeCompany->kpp,
                'address' => $order->lesseeCompany->address,
            ],
            'scenario' => $scenario,
        ];

        // Добавляем данные по оборудованию
        if ($order->relationLoaded('equipment')) {
            $data['equipment'] = $order->equipment->map(function ($item) {
                return [
                    'name' => $item->name,
                    'model' => $item->model,
                    'serial_number' => $item->serial_number,
                    'rental_price' => $item->pivot->rental_price ?? $item->price,
                    'quantity' => $item->pivot->quantity ?? 1,
                    'total' => ($item->pivot->rental_price ?? $item->price) * ($item->pivot->quantity ?? 1),
                ];
            })->toArray();
        }

        return $data;
    }

    protected function prepareUpdData($upd, $scenario)
    {
        return [
            'upd' => [
                'id' => $upd->id,
                'number' => $upd->number,
                'issue_date' => $upd->issue_date->format('d.m.Y'),
                'total_amount' => $upd->total_amount,
                'amount_without_vat' => $upd->amount,
                'vat_amount' => $upd->tax_amount,
            ],
            'order' => [
                'id' => $upd->order->id,
                'number' => $upd->order->order_number,
                'total_amount' => $upd->order->total_amount,
            ],
            'lessor_company' => [
                'name' => $upd->lessorCompany->name,
                'legal_name' => $upd->lessorCompany->legal_name,
                'inn' => $upd->lessorCompany->inn,
                'kpp' => $upd->lessorCompany->kpp,
            ],
            'lessee_company' => [
                'name' => $upd->lesseeCompany->name,
                'legal_name' => $upd->lesseeCompany->legal_name,
                'inn' => $upd->lesseeCompany->inn,
                'kpp' => $upd->lesseeCompany->kpp,
            ],
            'scenario' => $scenario,
        ];
    }
}
