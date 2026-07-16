@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Детали выписки #{{ $bankStatement->id }}</h3>
            <div>
                <form action="{{ route('admin.bank-statements.destroy', $bankStatement) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Удалить выписку и все транзакции?')">
                        <i class="bi bi-trash"></i> Удалить
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <strong>Файл:</strong> {{ $bankStatement->filename }}<br>
                    <strong>Банк:</strong> {{ $bankStatement->bank_name }}<br>
                    <strong>Дата загрузки:</strong> {{ $bankStatement->created_at->format('d.m.Y H:i') }}<br>
                </div>
                <div class="col-md-4">
                    <strong>Загрузил:</strong> {{ $bankStatement->processedBy->name ?? 'Неизвестно' }}<br>
                    <strong>Транзакций:</strong> {{ $bankStatement->transactions_count }}<br>
                    <strong>Обработано:</strong> {{ $bankStatement->processed_count }}<br>
                    <strong>Ошибок:</strong> {{ $bankStatement->error_count }}<br>
                </div>
                <div class="col-md-4">
                    <strong>Статус:</strong>
                    <span class="badge bg-{{ $bankStatement->status === 'completed' ? 'success' : ($bankStatement->status === 'failed' ? 'danger' : 'warning') }}">
                        {{ $bankStatement->status === 'completed' ? 'Завершена' : ($bankStatement->status === 'processing' ? 'Обрабатывается' : ($bankStatement->status === 'completed_with_errors' ? 'С ошибками' : ($bankStatement->status === 'failed' ? 'Ошибка' : $bankStatement->status))) }}
                    </span><br>
                    @php
                        $totalIn = $bankStatement->transactions->where('type', 'incoming')->sum('amount');
                        $totalOut = $bankStatement->transactions->where('type', 'outgoing')->sum('amount');
                        $balance = $totalIn - $totalOut;
                    @endphp
                    <strong>Приход:</strong> <span class="text-success">{{ number_format($totalIn, 2) }} ₽</span><br>
                    <strong>Расход:</strong> <span class="text-danger">{{ number_format($totalOut, 2) }} ₽</span><br>
                    <strong>Остаток:</strong> <span class="fw-bold {{ $balance >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($balance, 2) }} ₽</span>
                </div>
            </div>

            @if($bankStatement->status === 'failed')
                <div class="alert alert-danger">
                    <h5>Выписка не была обработана</h5>
                    <p>Возникли ошибки при обработке выписки. Детали можно посмотреть в логах системы.</p>
                </div>
            @endif

            @if($bankStatement->transactions->count() > 0)
                <!-- Фильтр по статусу сопоставления -->
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-auto">
                        <select name="match_status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Все транзакции</option>
                            <option value="matched" {{ request('match_status') === 'matched' ? 'selected' : '' }}>Сопоставленные</option>
                            <option value="unmatched" {{ request('match_status') === 'unmatched' ? 'selected' : '' }}>Не сопоставленные</option>
                        </select>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Дата</th>
                                <th>Сумма</th>
                                <th>Тип</th>
                                <th>Плательщик</th>
                                <th>Получатель</th>
                                <th>Назначение</th>
                                <th>Документ</th>
                                <th>Статус сопоставления</th>
                                <th>Компания</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $transactions = $bankStatement->transactions;
                                if (request('match_status') === 'matched') {
                                    $transactions = $transactions->where('is_unmatched', false)->whereNotNull('document_type');
                                } elseif (request('match_status') === 'unmatched') {
                                    $transactions = $transactions->where('is_unmatched', true);
                                }
                            @endphp
                            @forelse($transactions as $transaction)
                            <tr class="{{ $transaction->status === 'error' ? 'table-danger' : ($transaction->status === 'processed' ? 'table-success' : '') }}">
                                <td>{{ $transaction->date->format('d.m.Y') }}</td>
                                <td class="fw-bold {{ $transaction->type === 'incoming' ? 'text-success' : 'text-danger' }}">
                                    {{ $transaction->type === 'incoming' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} ₽
                                </td>
                                <td>
                                    <span class="badge bg-{{ $transaction->type === 'incoming' ? 'success' : 'info' }}">
                                        {{ $transaction->type === 'incoming' ? 'Входящий' : 'Исходящий' }}
                                    </span>
                                </td>
                                <td>
                                    <small>{{ $transaction->payer_name }}</small>
                                    <br><code>{{ $transaction->payer_inn }}</code>
                                </td>
                                <td>
                                    <small>{{ $transaction->payee_name }}</small>
                                    <br><code>{{ $transaction->payee_inn }}</code>
                                </td>
                                <td><small>{{ Str::limit($transaction->purpose, 40) }}</small></td>
                                <td>
                                    @if($transaction->document_type && $transaction->document_id)
                                        <span class="badge bg-primary">
                                            {{ $transaction->document_type_label }} №{{ $transaction->document_id }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $matchBadge = match(true) {
                                            $transaction->document_type && $transaction->document_id => ['bg-success', 'Сопоставлен'],
                                            $transaction->is_unmatched => ['bg-warning text-dark', 'Не сопоставлен'],
                                            default => ['bg-secondary', 'Ожидает']
                                        };
                                    @endphp
                                    <span class="badge {{ $matchBadge[0] }}">{{ $matchBadge[1] }}</span>
                                    @if($transaction->unmatched_reason)
                                        <div class="text-danger small">{{ $transaction->unmatched_reason }}</div>
                                    @endif
                                </td>
                                <td>{{ $transaction->company->legal_name ?? ($transaction->matchedCompany->legal_name ?? 'Не найдена') }}</td>
                                <td>
                                    <span class="badge bg-{{ $transaction->status === 'processed' ? 'success' : ($transaction->status === 'error' ? 'danger' : 'warning') }}">
                                        {{ $transaction->status === 'processed' ? 'Проведён' : ($transaction->status === 'pending' ? 'В обработке' : ($transaction->status === 'on_hold' ? 'На удержании' : $transaction->status)) }}
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    @if($transaction->is_unmatched || !$transaction->document_type)
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="openMatchModal({{ $transaction->id }})"
                                                title="Сопоставить вручную">
                                            <i class="bi bi-link-45deg"></i>
                                        </button>
                                    @endif
                                    @if($transaction->status === 'error')
                                        <span class="text-danger" title="{{ $transaction->error_message }}">
                                            <i class="bi bi-exclamation-circle"></i>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="text-center py-4">Нет транзакций</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">Транзакции не найдены или ещё обрабатываются.</div>
            @endif
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.bank-statements.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>
</div>

<!-- Модальное окно ручного сопоставления -->
<div class="modal fade" id="matchModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <form method="POST" id="matchForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Ручное сопоставление транзакции</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="transaction_id" id="matchTransactionId">

                    <div class="mb-3">
                        <label class="form-label">Тип документа</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="document_type" value="upd" id="docUpd" checked>
                                <label class="form-check-label" for="docUpd">УПД</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="document_type" value="invoice" id="docInvoice">
                                <label class="form-check-label" for="docInvoice">Счёт</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="document_type" value="order" id="docOrder">
                                <label class="form-check-label" for="docOrder">Заказ / Договор</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Номер документа</label>
                        <input type="text" name="document_number" class="form-control" placeholder="Введите номер документа" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Привязать к компании (если не найдена)</label>
                        <select name="matched_company_id" class="form-select">
                            <option value="">Не привязывать</option>
                            @foreach(\App\Models\Company::orderBy('legal_name')->get() as $c)
                                <option value="{{ $c->id }}">{{ $c->legal_name }} (ИНН: {{ $c->inn }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-info">
                        <strong>Подсказка:</strong> После сопоставления будет проверено совпадение суммы.
                        Если сумма расходится более чем на 1 рубль — транзакция будет помечена как не сопоставленная.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-link-45deg"></i> Сопоставить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openMatchModal(transactionId) {
    document.getElementById('matchTransactionId').value = transactionId;
    document.getElementById('matchForm').action = '/admin/bank-statements/' + transactionId + '/match';
    var modal = new bootstrap.Modal(document.getElementById('matchModal'));
    modal.show();
}
</script>
@endsection
