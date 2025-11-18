<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Номер</th>
                <th>Дата выставления</th>
                <th>Срок оплаты</th>
                <th>Компания</th>
                <th>Сумма</th>
                <th>Оплачено</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $invoice)
                <tr>
                    <td>{{ $invoice->number }}</td>
                    <td>{{ $invoice->issue_date->format('d.m.Y') }}</td>
                    <td>{{ $invoice->due_date->format('d.m.Y') }}</td>
                    <td>{{ $invoice->company->legal_name }}</td>
                    <td>{{ number_format($invoice->amount, 2) }} ₽</td>
                    <td>{{ number_format($invoice->amount_paid, 2) }} ₽</td>
                    <td>
                        @php
                            $statusClass = match($invoice->status) {
                                'draft' => 'secondary',
                                'sent' => 'info',
                                'viewed' => 'primary',
                                'paid' => 'success',
                                'overdue' => 'danger',
                                'canceled' => 'dark',
                                default => 'light'
                            };
                        @endphp
                        <span class="badge badge-{{ $statusClass }}">
                            {{ $invoice->status }}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <!-- Просмотр через существующий маршрут документов -->
                            <a href="{{ route('admin.documents.show', ['type' => 'invoices', 'id' => $invoice->id]) }}"
                               class="btn btn-info" title="Просмотр">
                                <i class="fas fa-eye"></i>
                            </a>

                            <!-- Скачивание через новый маршрут -->
                            @if($invoice->file_path)
                                <a href="{{ route('admin.invoices.download', $invoice) }}"
                                   class="btn btn-success" title="Скачать счет">
                                    <i class="fas fa-download"></i>
                                </a>
                            @endif

                            <!-- Отмена счета -->
                            @if($invoice->status !== 'paid' && $invoice->status !== 'canceled')
                                <button type="button" class="btn btn-danger"
                                        data-toggle="modal"
                                        data-target="#cancelInvoiceModal{{ $invoice->id }}"
                                        title="Отменить счет">
                                    <i class="fas fa-ban"></i>
                                </button>
                            @endif
                        </div>

                        <!-- Модальное окно отмены счета -->
                        <div class="modal fade" id="cancelInvoiceModal{{ $invoice->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.invoices.cancel', $invoice) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Отмена счета #{{ $invoice->number }}</h5>
                                            <button type="button" class="close" data-dismiss="modal">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Причина отмены</label>
                                                <textarea class="form-control" name="reason" rows="3" required
                                                          placeholder="Укажите причину отмены счета..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                                            <button type="submit" class="btn btn-danger">Подтвердить отмену</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Счета не найдены</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $documents->links() }}
