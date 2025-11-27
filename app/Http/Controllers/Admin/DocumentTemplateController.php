<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentTemplateController extends Controller
{
    /**
     * Типы шаблонов документов
     */
    protected $templateTypes = [
        'contract' => 'Договор',
        'invoice' => 'Счет',
        'act' => 'Акт выполненных работ',
        'waybill' => 'Путевой лист',
        'completion_act' => 'Акт сдачи-приемки',
        'upd' => 'УПД',
        'delivery_note' => 'Накладная',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = DocumentTemplate::latest()->paginate(20);

        return view('admin.settings.document-templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $templateTypes = DocumentTemplate::getTypes();
        $scenarios = DocumentTemplate::getScenarios(); // ДОБАВИТЬ ЭТУ СТРОКУ

        return view('admin.settings.document-templates.create', compact('templateTypes', 'scenarios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(DocumentTemplate::getTypes())),
            'scenario' => 'required|string|in:' . implode(',', array_keys(DocumentTemplate::getScenarios())),
            'description' => 'nullable|string',
            'template_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'mapping' => 'required|json', // ДОБАВИТЬ ВАЛИДАЦИЮ ДЛЯ MAPPING
        ]);

        // ДОБАВЬТЕ ЛОГИРОВАНИЕ ДЛЯ ОТЛАДКИ
        \Log::info('Store document template mapping data', [
            'mapping' => $request->mapping,
            'mapping_decoded' => json_decode($request->mapping, true)
        ]);

        try {
            // Обработка загрузки файла
            if ($request->hasFile('template_file')) {
                $file = $request->file('template_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('document_templates', $fileName, 'public');
                $validated['file_path'] = $filePath;
            }

            // ДОБАВЛЯЕМ MAPPING И ДРУГИЕ ПОЛЯ
            $validated['mapping'] = json_decode($request->mapping, true);
            $validated['is_active'] = $request->has('is_active');

            $template = DocumentTemplate::create($validated);

            return redirect()->route('admin.settings.document-templates.index')
                ->with('success', 'Шаблон успешно создан');

        } catch (\Exception $e) {
            \Log::error('Error creating document template', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Ошибка при создании шаблона: '.$e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(DocumentTemplate $documentTemplate)
    {
        return view('admin.settings.document-templates.show', compact('documentTemplate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DocumentTemplate $documentTemplate)
    {
        $templateTypes = DocumentTemplate::getTypes();
        $scenarios = DocumentTemplate::getScenarios(); // ДОБАВИТЬ ЭТУ СТРОКУ

        return view('admin.settings.document-templates.edit', compact('documentTemplate', 'templateTypes', 'scenarios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(DocumentTemplate::getTypes())),
            'scenario' => 'required|string|in:' . implode(',', array_keys(DocumentTemplate::getScenarios())),
            'description' => 'nullable|string',
            'template_file' => 'nullable|file|mimes:xlsx,xls,csv|max:10240',
            'mapping' => 'required|json', // ДОБАВИТЬ ВАЛИДАЦИЮ ДЛЯ MAPPING
        ]);

        // ДОБАВЬТЕ ЛОГИРОВАНИЕ ДЛЯ ОТЛАДКИ
        \Log::info('Update document template mapping data', [
            'template_id' => $documentTemplate->id,
            'mapping' => $request->mapping,
            'mapping_decoded' => json_decode($request->mapping, true)
        ]);

        try {
            $data = [
                'name' => $validated['name'],
                'type' => $validated['type'],
                'scenario' => $validated['scenario'],
                'description' => $validated['description'],
                'mapping' => json_decode($request->mapping, true), // ДОБАВИТЬ MAPPING
                'is_active' => $request->has('is_active'),
            ];

            // Обработка загрузки нового файла
            if ($request->hasFile('template_file')) {
                // Удаляем старый файл
                if ($documentTemplate->file_path) {
                    Storage::disk('public')->delete($documentTemplate->file_path);
                }

                $file = $request->file('template_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('document_templates', $fileName, 'public');
                $data['file_path'] = $filePath;
            }

            $documentTemplate->update($data);

            return redirect()->route('admin.settings.document-templates.index')
                ->with('success', 'Шаблон успешно обновлен');

        } catch (\Exception $e) {
            \Log::error('Error updating document template', [
                'template_id' => $documentTemplate->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()
                ->with('error', 'Ошибка при обновлении шаблона: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DocumentTemplate $documentTemplate)
    {
        // Удаляем файл
        if ($documentTemplate->file_path) {
            Storage::disk('public')->delete($documentTemplate->file_path);
        }

        $documentTemplate->delete();

        return redirect()->route('admin.settings.document-templates.index')
            ->with('success', 'Шаблон успешно удален');
    }

    /**
     * Download template file
     */
    public function download(DocumentTemplate $documentTemplate)
    {
        if (!$documentTemplate->file_path) {
            abort(404, 'Файл шаблона не найден');
        }

        return response()->download(storage_path('app/public/' . $documentTemplate->file_path));
    }

    /**
     * Preview template
     */
    public function preview(DocumentTemplate $documentTemplate)
    {
        return view('admin.settings.document-templates.preview', compact('documentTemplate'));
    }

    /**
     * Show generate form
     */
    public function generateForm(DocumentTemplate $documentTemplate)
    {
        return view('admin.settings.document-templates.generate', compact('documentTemplate'));
    }

    /**
     * Generate document from template
     */
    public function generate(Request $request, DocumentTemplate $documentTemplate)
    {
        // Здесь будет логика генерации документа
        return redirect()->back()
            ->with('success', 'Документ успешно сгенерирован');
    }
}
