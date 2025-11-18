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
        $templateTypes = $this->templateTypes;

        return view('admin.settings.document-templates.create', compact('templateTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys($this->templateTypes)),
            'description' => 'nullable|string',
            'template_file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Сделать обязательным
        ]);

        // Обработка загрузки файла
        if ($request->hasFile('template_file')) {
            $file = $request->file('template_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('document_templates', $fileName, 'public');
            $validated['file_path'] = $filePath;
        }

        // Добавляем mapping по умолчанию, если не используется второй контроллер
        if (!isset($validated['mapping'])) {
            $validated['mapping'] = [];
        }

        $template = DocumentTemplate::create($validated);

        return redirect()->route('admin.settings.document-templates.index')
            ->with('success', 'Шаблон успешно создан');
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
        $templateTypes = $this->templateTypes;

        return view('admin.settings.document-templates.edit', compact('documentTemplate', 'templateTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys($this->templateTypes)),
            'description' => 'nullable|string',
            'template_file' => 'nullable|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        // Обработка загрузки нового файла
        if ($request->hasFile('template_file')) {
            // Удаляем старый файл
            if ($documentTemplate->file_path) {
                Storage::disk('public')->delete($documentTemplate->file_path);
            }

            $file = $request->file('template_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('document_templates', $fileName, 'public');
            $validated['file_path'] = $filePath;
        }

        $documentTemplate->update($validated);

        return redirect()->route('admin.settings.document-templates.index')
            ->with('success', 'Шаблон успешно обновлен');
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
