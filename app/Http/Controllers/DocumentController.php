<?php

namespace App\Http\Controllers;

use App\Models\CompletionAct;
use App\Models\Contract;
use App\Models\DeliveryNote;
use App\Models\Waybill;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDF;

class DocumentController extends Controller
{
    public function createDeliveryNote(Order $order)
    {
        // Валидация данных
        $data = request()->validate([
            'delivery_date' => 'required|date',
            'driver_name' => 'required|string',
            'equipment_condition' => 'required|string',
        ]);

        // Создание накладной
        $deliveryNote = $order->deliveryNote()->create($data);

        // Обновление даты начала услуг
        $order->update([
            'service_start_date' => $data['delivery_date'],
        ]);

        return response()->json($deliveryNote, 201);
    }

    public function createWaybill(Order $order)
    {
        $data = request()->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'operator_id' => 'required|exists:users,id',
            'work_date' => 'required|date',
            'hours_worked' => 'required|numeric|min:0',
            'downtime_hours' => 'nullable|numeric|min:0',
            'downtime_cause' => 'nullable|in:lessee,lessor,force_majeure',
        ]);

        $waybill = $order->waybills()->create($data);

        return response()->json($waybill, 201);
    }

    public function generateCompletionAct(Order $order)
    {
        if (! $order->canGenerateCompletionAct()) {
            abort(400, 'Невозможно сформировать акт для этого заказа');
        }

        // Создаем экземпляр генератора и вызываем метод
        $generator = new CompletionActGenerator;
        $act = $generator->generateForOrder($order);

        return response()->json($act, 201);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $type = $request->input('type');

        // Для арендодателя: тип по умолчанию - путевые листы
        // Для арендатора: тип по умолчанию - транспортные накладные
        if (! $type) {
            $type = $user->company->is_lessor ? 'waybills' : 'delivery_notes';
        }

        \Log::debug('Document access', [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'requested_type' => $type,
        ]);

        $query = null;
        $userType = $user->company->is_lessor ? 'lessor' : 'lessee';

        switch ($type) {
            case 'delivery_notes':
                $query = DeliveryNote::with([
                    'order.lesseeCompany',
                    'order.lessorCompany',
                    'senderCompany',
                    'receiverCompany',
                    'orderItem',
                ]);

                // Фильтрация для арендатора
                if ($userType === 'lessee') {
                    $query->where('receiver_company_id', $user->company_id);
                }
                // Фильтрация для арендодателя
                else {
                    $query->where('sender_company_id', $user->company_id);
                }
                break;

            case 'waybills':
                // Только для арендодателей!
                if ($userType !== 'lessor') {
                    abort(403, 'Доступ запрещен');
                }

                $query = Waybill::with([
                    'order.lesseeCompany',
                    'order.lessorCompany',
                    'operator',
                    'equipment',
                ])
                    ->where('perspective', 'lessor')
                    ->whereHas('order', function ($q) use ($user) {
                        $q->where('lessor_company_id', $user->company_id);
                    });
                break;

            case 'contracts':
                // Для арендодателей и арендаторов
                $query = Contract::with(['platformCompany', 'counterpartyCompany'])
                    ->where('counterparty_company_id', $user->company_id)
                    ->where('counterparty_type', $userType);
                break;

            case 'completion_acts':
                // Только для арендодателей!
                if ($userType !== 'lessor') {
                    abort(403, 'Доступ запрещен');
                }

                $query = CompletionAct::with('order.lesseeCompany')
                    ->where('perspective', 'lessor')
                    ->whereHas('order', function ($q) use ($user) {
                        $q->where('lessor_company_id', $user->company_id);
                    });
                break;

            default:
                abort(404, 'Неизвестный тип документов');
        }

        // Пагинация
        $documents = $query->orderBy('created_at', 'desc')->paginate(10);

        \Log::debug('Documents loaded', [
            'type' => $type,
            'count' => $documents->count(),
            'userType' => $userType,
        ]);

        return view("{$userType}.documents.index", compact('documents', 'type', 'userType'));
    }

    public function statusUpdate(Request $request)
    {
        $type = $request->input('type');
        $user = auth()->user();

        $documents = match ($type) {
            'delivery_notes' => DeliveryNote::whereHas('order', fn ($q) => $q->where('lessor_company_id', $user->company_id))
                ->whereIn('status', [DeliveryNote::STATUS_DRAFT, DeliveryNote::STATUS_IN_TRANSIT])
                ->get(),
            'waybills' => Waybill::whereHas('order', fn ($q) => $q->where('lessor_company_id', $user->company_id))
                ->whereIn('status', [Waybill::STATUS_CREATED, Waybill::STATUS_IN_PROGRESS])
                ->get(),
            default => collect(),
        };

        return response()->json($documents->map(function ($doc) {
            return [
                'id' => $doc->id,
                'status' => $doc->status,
                'status_text' => $doc->status_text,
                'status_color' => $doc->status_color,
            ];
        }));
    }

    public function download($id, $type)
    {
        if ($type === 'delivery_notes') {
            $note = DeliveryNote::with('orderItem')->findOrFail($id);

            // Проверка прав
            if (auth()->user()->cannot('view', $note)) {
                abort(403);
            }

            return $this->downloadDeliveryNote($note);
        }

        // Получаем документ в зависимости от типа
        $document = match ($type) {
            'contracts' => Contract::findOrFail($id),
            'waybills' => Waybill::findOrFail($id),
            'completion_acts' => CompletionAct::findOrFail($id),
        };

        // Проверка прав доступа для договоров
        if ($type === 'contracts') {
            $user = auth()->user();
            if ($document->counterparty_company_id !== $user->company_id ||
                $document->counterparty_type !== ($user->company->is_lessor ? 'lessor' : 'lessee')) {
                abort(403, 'Доступ запрещен');
            }
        }

        // Проверка перспективы для waybills и completion_acts
        if (in_array($type, ['waybills', 'completion_acts'])) {
            if ($document->perspective !== 'lessor') {
                abort(403, 'Доступ запрещен. Неверный тип документа.');
            }
        }

        // Проверка прав доступа для остальных типов
        if (auth()->user()->cannot('view', $document)) {
            abort(403);
        }

        // Генерация PDF для договоров
        if ($type === 'contracts') {
            if (!$document->file_path) {
                abort(404, 'Файл договора не найден');
            }
            return response()->download(storage_path('app/public/' . $document->file_path));
        }

        $generatorClass = match ($type) {
            'waybills' => WaybillPdfGenerator::class,
            'completion_acts' => CompletionActGenerator::class,
        };

        return app($generatorClass)->generate($document);
    }

    public function downloadUPDF(Order $order, $type)
    {
        $generator = new UPDPdfGenerator;

        return match ($type) {
            'lessor' => $generator->generateForLessor($order),
            'lessee' => $generator->generateForLessee($order),
            default => abort(404)
        };
    }

    public function downloadDeliveryNote(DeliveryNote $note)
    {
        // Если документ еще не сгенерирован или отсутствует в хранилище
        if (! $note->document_path || ! Storage::exists($note->document_path)) {
            // Создаем новый генератор
            $pdfGenerator = app(DeliveryNoteGenerator::class);

            // Генерируем и сохраняем PDF
            $pdfPath = $pdfGenerator->generateAndSave($note);

            // Обновляем путь к документу
            $note->update(['document_path' => $pdfPath]);
        }

        return Storage::download($note->document_path, "ТН-{$note->document_number}.pdf");
    }

    public function showCompletionAct(CompletionAct $act)
    {
        // Проверка прав доступа
        if ($act->order->lessor_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        // Загрузка связанных данных
        $act->load([
            'order.lesseeCompany',
            'waybill.equipment',
            'waybill.operator',
        ]);

        return view('lessor.documents.completion_acts.show', compact('act'));
    }

    public function signDeliveryNote(DeliveryNote $note, Request $request)
    {
        $request->validate(['signature' => 'required|string']);
        $signaturePath = $this->saveSignature($request->signature);
        $user = $request->user();

        // Проверка роли и типа накладной
        if ($user->isLessor() && $note->type === DeliveryNote::TYPE_LESSOR_TO_PLATFORM) {
            $note->update(['sender_signature_path' => $signaturePath]);
        } elseif ($user->isLessee() && $note->type === DeliveryNote::TYPE_PLATFORM_TO_LESSEE) {
            $note->update(['receiver_signature_path' => $signaturePath]);
        } elseif ($user->isPlatformAdmin()) {
            $note->update(['carrier_signature_path' => $signaturePath]);
        }

        // Проверка полноты подписей
        if ($note->isFullySigned()) {
            event(new DeliveryNoteSigned($note));
        }

        return response()->json(['status' => 'success']);
    }

    private function saveSignature($base64): string
    {
        $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
        $fileName = 'signatures/'.Str::uuid().'.png';
        Storage::put($fileName, $image);

        return $fileName;
    }
}
