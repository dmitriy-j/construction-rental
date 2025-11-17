<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractRequest;
use App\Models\Company;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        Log::info('ContractController: Просмотр списка договоров', [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'type' => $request->get('type', 'all')
        ]);

        $type = $request->get('type', 'all');

        $contracts = Contract::with(['platformCompany', 'counterpartyCompany'])
            ->when($type === 'lessors', function($query) {
                return $query->withLessors();
            })
            ->when($type === 'lessees', function($query) {
                return $query->withLessees();
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'all' => Contract::count(),
            'lessors' => Contract::withLessors()->count(),
            'lessees' => Contract::withLessees()->count(),
        ];

        return view('admin.documents.contracts.index', compact('contracts', 'type', 'stats'));
    }

    public function create()
    {
        Log::info('ContractController: Открытие формы создания договора', [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name
        ]);

        $lessorCompanies = Company::where('is_lessor', true)->get();
        $lesseeCompanies = Company::where('is_lessee', true)->get();

        return view('admin.documents.contracts.create', compact('lessorCompanies', 'lesseeCompanies'));
    }

    public function store(StoreContractRequest $request)
    {
        Log::info('ContractController: Начало создания договора - ДО ВАЛИДАЦИИ', [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'request_data' => $request->except(['file', '_token']),
            'has_file' => $request->hasFile('file')
        ]);

        try {
            $data = $request->validated();
            Log::debug('ContractController: Данные прошли валидацию', ['validated_data' => $data]);

            // Получаем компанию платформы
            $platformCompany = Company::platform()->first();

            if (!$platformCompany) {
                Log::error('ContractController: Компания платформы не найдена');
                return redirect()->back()
                    ->with('error', 'Компания платформы не найдена. Обратитесь к администратору.')
                    ->withInput();
            }

            Log::debug('ContractController: Компания платформы найдена', [
                'platform_company_id' => $platformCompany->id,
                'platform_company_name' => $platformCompany->legal_name
            ]);

            // Обработка файла
            if ($request->hasFile('file')) {
                Log::debug('ContractController: Начало обработки файла', [
                    'file_name' => $request->file('file')->getClientOriginalName(),
                    'file_size' => $request->file('file')->getSize(),
                    'file_mime' => $request->file('file')->getMimeType()
                ]);

                try {
                    $filePath = $request->file('file')->store('contracts', 'public');
                    $data['file_path'] = $filePath;
                    Log::info('ContractController: Файл успешно загружен', ['file_path' => $filePath]);
                } catch (\Exception $e) {
                    Log::error('ContractController: Ошибка загрузки файла', [
                        'error' => $e->getMessage(),
                        'file_name' => $request->file('file')->getClientOriginalName()
                    ]);
                    return redirect()->back()
                        ->with('error', 'Ошибка загрузки файла: ' . $e->getMessage())
                        ->withInput();
                }
            } else {
                Log::debug('ContractController: Файл не был загружен');
            }

            $data['company_id'] = $platformCompany->id;
            $data['is_active'] = $request->has('is_active');

            Log::debug('ContractController: Данные для создания договора', ['final_data' => $data]);

            // Создание договора
            $contract = Contract::create($data);

            Log::info('ContractController: Договор успешно создан', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->number,
                'counterparty_type' => $contract->counterparty_type,
                'counterparty_company_id' => $contract->counterparty_company_id
            ]);

            return redirect()->route('admin.contracts.index')
                ->with('success', 'Договор успешно создан');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('ContractController: Ошибка валидации', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            throw $e;

        } catch (\Exception $e) {
            Log::error('ContractController: Критическая ошибка при создании договора', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['file'])
            ]);

            return redirect()->back()
                ->with('error', 'Произошла непредвиденная ошибка: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Contract $contract)
    {
        Log::info('ContractController: Просмотр договора', [
            'user_id' => auth()->id(),
            'contract_id' => $contract->id,
            'contract_number' => $contract->number
        ]);

        $contract->load(['platformCompany', 'counterpartyCompany']);
        return view('admin.documents.contracts.show', compact('contract'));
    }

    public function edit(Contract $contract)
    {
        Log::info('ContractController: Открытие формы редактирования договора', [
            'user_id' => auth()->id(),
            'contract_id' => $contract->id,
            'contract_number' => $contract->number
        ]);

        $lessorCompanies = Company::where('is_lessor', true)->get();
        $lesseeCompanies = Company::where('is_lessee', true)->get();

        return view('admin.documents.contracts.edit', compact('contract', 'lessorCompanies', 'lesseeCompanies'));
    }

    public function update(StoreContractRequest $request, Contract $contract)
    {
        Log::info('ContractController: Начало обновления договора', [
            'user_id' => auth()->id(),
            'contract_id' => $contract->id,
            'contract_number' => $contract->number,
            'request_data' => $request->except(['file'])
        ]);

        try {
            $data = $request->validated();
            Log::debug('ContractController: Данные прошли валидацию для обновления', ['validated_data' => $data]);

            // Обработка файла
            if ($request->hasFile('file')) {
                Log::debug('ContractController: Начало обработки нового файла', [
                    'file_name' => $request->file('file')->getClientOriginalName()
                ]);

                try {
                    // Удаляем старый файл если есть
                    if ($contract->file_path) {
                        Storage::disk('public')->delete($contract->file_path);
                        Log::debug('ContractController: Старый файл удален', [
                            'old_file_path' => $contract->file_path
                        ]);
                    }

                    $filePath = $request->file('file')->store('contracts', 'public');
                    $data['file_path'] = $filePath;
                    Log::info('ContractController: Новый файл успешно загружен', ['file_path' => $filePath]);
                } catch (\Exception $e) {
                    Log::error('ContractController: Ошибка загрузки нового файла', [
                        'error' => $e->getMessage()
                    ]);
                    return redirect()->back()
                        ->with('error', 'Ошибка загрузки файла: ' . $e->getMessage())
                        ->withInput();
                }
            }

            $data['is_active'] = $request->has('is_active');

            Log::debug('ContractController: Данные для обновления договора', ['final_data' => $data]);

            $contract->update($data);

            Log::info('ContractController: Договор успешно обновлен', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->number
            ]);

            return redirect()->route('admin.contracts.show', $contract)
                ->with('success', 'Договор успешно обновлен');

        } catch (\Exception $e) {
            Log::error('ContractController: Ошибка при обновлении договора', [
                'contract_id' => $contract->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Произошла ошибка при обновлении договора: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Contract $contract)
    {
        Log::info('ContractController: Начало удаления договора', [
            'user_id' => auth()->id(),
            'contract_id' => $contract->id,
            'contract_number' => $contract->number
        ]);

        try {
            if ($contract->file_path) {
                Storage::disk('public')->delete($contract->file_path);
                Log::debug('ContractController: Файл договора удален', [
                    'file_path' => $contract->file_path
                ]);
            }

            $contract->delete();

            Log::info('ContractController: Договор успешно удален', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->number
            ]);

            return redirect()->route('admin.contracts.index')
                ->with('success', 'Договор успешно удален');

        } catch (\Exception $e) {
            Log::error('ContractController: Ошибка при удалении договора', [
                'contract_id' => $contract->id,
                'error_message' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Произошла ошибка при удалении договора: ' . $e->getMessage());
        }
    }

    public function download(Contract $contract)
    {
        Log::info('ContractController: Попытка скачивания файла договора', [
            'user_id' => auth()->id(),
            'contract_id' => $contract->id,
            'contract_number' => $contract->number
        ]);

        if (!$contract->file_path) {
            Log::warning('ContractController: Файл договора не найден', [
                'contract_id' => $contract->id
            ]);
            abort(404, 'Файл договора не найден');
        }

        Log::info('ContractController: Файл договора отправлен для скачивания', [
            'contract_id' => $contract->id,
            'file_path' => $contract->file_path
        ]);

        return Storage::disk('public')->download($contract->file_path);
    }
}
