@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header bg-gradient-dark text-Black d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="fas fa-clock mr-2"></i>Управление отложенными транзакциями
        </h3>
        <a href="{{ route('admin.bank-statements.index') }}" class="btn btn-outline-black btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Назад к выпискам
        </a>
    </div>

    <div class="card-body p-0">
        <div class="p-3 border-bottom">
            <ul class="nav nav-pills nav-justified" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="pill" href="#incoming">
                        <i class="fas fa-download mr-1"></i> Входящие платежи
                        <span class="badge badge-light ml-1">{{ $pendingTransactions->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#outgoing">
                        <i class="fas fa-upload mr-1"></i> Исходящие платежи
                        <span class="badge badge-light ml-1">{{ $pendingPayouts->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#refunds">
                        <i class="fas fa-exchange-alt mr-1"></i> Возвраты средств
                        <span class="badge badge-light ml-1">{{ $refundTransactions->count() }}</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="tab-content p-3">
            <!-- Вкладка входящих платежей -->
            <div id="incoming" class="tab-pane active">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Ожидающие входящие платежи</h5>
                    <span class="badge badge-warning">Всего: {{ $pendingTransactions->total() }}</span>
                </div>

                @if($pendingTransactions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Тип</th>
                                <th>ИНН</th>
                                <th>Название компании</th>
                                <th class="text-center">Сумма</th>
                                <th>Назначение платежа</th>
                                <th class="text-center">Дата</th>
                                <th class="text-center">Статус</th>
                                <th class="text-center">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingTransactions as $transaction)
                            <tr>
                                <td>
                                    <span class="badge badge-success">Входящий</span>
                                </td>
                                <td><code>{{ $transaction->company_inn }}</code></td>
                                <td>{{ $transaction->company_name }}</td>
                                <td class="text-center text-success font-weight-bold">
                                    {{ number_format($transaction->amount, 2) }} руб.
                                </td>
                                <td class="text-truncate" style="max-width: 200px;" title="{{ $transaction->transaction_data['НазначениеПлатежа'] ?? 'Не указано' }}">
                                    {{ $transaction->transaction_data['НазначениеПлатежа'] ?? 'Не указано' }}
                                </td>
                                <td class="text-center">{{ $transaction->created_at->format('d.m.Y H:i') }}</td>
                                <td class="text-center">
                                    <span class="badge badge-warning">{{ $transaction->status }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.bank-statements.process-pending', $transaction) }}"
                                           class="btn btn-primary" title="Обработать транзакцию">
                                            <i class="fas fa-cog"></i>
                                        </a>
                                        <a href="#" class="btn btn-success" title="Создать компанию" onclick="alert('Функционал создания компании находится в разработке'); return false;">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    {{ $pendingTransactions->links() }}
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">Нет ожидающих входящих платежей</p>
                </div>
                @endif
            </div>

            <!-- Вкладка исходящих платежей -->
            <div id="outgoing" class="tab-pane fade">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Ожидающие исходящие платежи</h5>
                    <span class="badge badge-warning">Всего: {{ $pendingPayouts->total() }}</span>
                </div>

                @if($pendingPayouts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Тип</th>
                                <th>ИНН получателя</th>
                                <th>Название получателя</th>
                                <th class="text-center">Сумма</th>
                                <th>Назначение платежа</th>
                                <th class="text-center">Статус</th>
                                <th class="text-center">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingPayouts as $payout)
                            <tr>
                                <td>
                                    <span class="badge badge-info">Исходящий</span>
                                </td>
                                <td><code>{{ $payout->payee_inn }}</code></td>
                                <td>{{ $payout->payee_name }}</td>
                                <td class="text-center text-danger font-weight-bold">
                                    {{ number_format($payout->amount, 2) }} руб.
                                </td>
                                <td class="text-truncate" style="max-width: 200px;" title="{{ $payout->purpose }}">
                                    {{ $payout->purpose }}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-warning">{{ $payout->status }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="#" class="btn btn-success" title="Создать компанию" onclick="alert('Функционал создания компании находится в разработке'); return false;">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                        <form action="{{ route('admin.bank-statements.cancel-payout', $payout) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-danger" title="Отменить выплату">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    {{ $pendingPayouts->links() }}
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">Нет ожидающих исходящих платежей</p>
                </div>
                @endif
            </div>

            <!-- Вкладка возвратов -->
            <div id="refunds" class="tab-pane fade">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Ожидающие возвраты средств</h5>
                    <span class="badge badge-warning">Всего: {{ $refundTransactions->total() }}</span>
                </div>

                @if($refundTransactions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Тип</th>
                                <th>ИНН</th>
                                <th>Название компании</th>
                                <th class="text-center">Сумма</th>
                                <th>Назначение платежа</th>
                                <th class="text-center">Статус</th>
                                <th class="text-center">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($refundTransactions as $refund)
                            <tr>
                                <td>
                                    <span class="badge badge-{{ $refund->type === 'refund_incoming' ? 'info' : 'warning' }}">
                                        {{ $refund->type === 'refund_incoming' ? 'Входящий возврат' : 'Исходящий возврат' }}
                                    </span>
                                </td>
                                <td><code>{{ $refund->company_inn }}</code></td>
                                <td>{{ $refund->company_name }}</td>
                                <td class="text-center font-weight-bold {{ $refund->type === 'refund_incoming' ? 'text-info' : 'text-warning' }}">
                                    {{ number_format($refund->amount, 2) }} руб.
                                </td>
                                <td class="text-truncate" style="max-width: 200px;" title="{{ $refund->purpose }}">
                                    {{ $refund->purpose }}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-warning">{{ $refund->status }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-primary" data-toggle="modal"
                                                data-target="#processRefundModal" data-refund-id="{{ $refund->id }}"
                                                title="Обработать возврат">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <a href="#" class="btn btn-success" title="Создать компанию" onclick="alert('Функционал создания компании находится в разработке'); return false;">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    {{ $refundTransactions->links() }}
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">Нет ожидающих возвратов средств</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для обработки возвратов -->
<div class="modal fade" id="processRefundModal" tabindex="-1" role="dialog" aria-labelledby="processRefundModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="processRefundModalLabel">
                    <i class="fas fa-exchange-alt mr-2"></i>Обработка возврата средств
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.bank-statements.process-refund') }}" method="POST">
                @csrf
                <input type="hidden" name="refund_id" id="refundId">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="refundAction" class="font-weight-bold">Действие:</label>
                        <select class="form-control select2" id="refundAction" name="action" required>
                            <option value="process">Провести возврат</option>
                            <option value="cancel">Отклонить возврат</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="refundNotes" class="font-weight-bold">Примечание:</label>
                        <textarea class="form-control" id="refundNotes" name="notes" rows="3" placeholder="Укажите причину возврата или дополнительную информацию"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Отмена
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check mr-1"></i> Подтвердить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .nav-pills .nav-link {
        border-radius: 0.25rem;
        transition: all 0.3s ease;
    }
    .nav-pills .nav-link.active {
        background-color: #007bff;
        box-shadow: 0 2px 6px rgba(0, 123, 255, 0.5);
    }
    .table th {
        border-top: none;
        font-weight: 600;
    }
    .card-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    }
    .btn-group-sm > .btn {
        border-radius: 0.2rem;
    }
</style>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Активация табов при загрузке страницы на основе URL хэша
        function activateTabFromHash() {
            var hash = window.location.hash;
            if (hash) {
                $('.nav-pills a[href="' + hash + '"]').tab('show');
            }
        }

        // Инициализация при загрузке
        activateTabFromHash();

        // Обработчик для изменения URL при переключении вкладок
        $('.nav-pills a').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            history.pushState(null, null, target);
            $(this).tab('show');
        });

        // Обработчик для кнопок переключения вкладок
        $('.nav-pills a[data-toggle="pill"]').on('shown.bs.tab', function(e) {
            var target = $(e.target).attr('href");
            history.replaceState(null, null, target);
        });

        // Обработчик изменения хэша в URL
        $(window).on('hashchange', function() {
            activateTabFromHash();
        });

        // Инициализация Select2 с правильным dropdownParent
        if (typeof $.fn.select2 !== 'undefined') {
            $('#refundAction').select2({
                dropdownParent: $('#processRefundModal'),
                minimumResultsForSearch: Infinity,
                width: '100%'
            });
        }

        // Обработчик для модального окна возвратов
        $('#processRefundModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var refundId = button.data('refund-id');
            $(this).find('#refundId').val(refundId);
        });
    });
</script>
@endsection
