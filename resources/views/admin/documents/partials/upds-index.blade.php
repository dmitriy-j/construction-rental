@php
    $documents = $documents ?? $upds ?? [];
@endphp

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Номер</th>
                <th>Дата</th>
                <th>Арендодатель</th>
                <th>Арендатор</th>
                <th>Заказ</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $upd)
                <tr>
                    <td>{{ $upd->number }}</td>
                    <td>{{ $upd->issue_date->format('d.m.Y') }}</td>
                    <td>{{ $upd->lessorCompany->legal_name ?? 'Не указан' }}</td>
                    <td>{{ $upd->lesseeCompany->legal_name ?? 'Не указан' }}</td>
                    <td>#{{ $upd->order_id }}</td>
                    <td>{{ number_format($upd->total_amount, 2) }} ₽</td>
                    <td>
                        <span class="badge badge-{{ $upd->status_color }}">
                            {{ $upd->status_text }}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="{{ route('admin.upds.show', $upd) }}" class="btn btn-info" title="Просмотр">
                                <i class="fas fa-eye"></i>
                            </a>

                            <!-- Кнопка скачивания -->
                            <!-- Улучшенные кнопки скачивания -->
                            @if($upd->file_path)
                                <a href="{{ route('admin.upds.download', $upd) }}" class="btn btn-success" title="Скачать УПД"
                                onclick="console.log('Скачивание УПД:', {id: {{ $upd->id }}, number: '{{ $upd->number }}'})">
                                    <i class="fas fa-download"></i>
                                </a>
                            @else
                                <a href="{{ route('admin.upds.download-generated', $upd) }}" class="btn btn-warning" title="Сгенерировать и скачать"
                                onclick="console.log('Генерация УПД:', {id: {{ $upd->id }}, number: '{{ $upd->number }}'})">
                                    <i class="fas fa-file-download"></i>
                                </a>
                            @endif

                            <!-- Кнопки принятия/отклонения -->
                            @if($upd->status == 'pending')
                                <form action="{{ route('admin.upds.accept', $upd) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success" title="Принять">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal{{ $upd->id }}" title="Отклонить">
                                    <i class="fas fa-times"></i>
                                </button>
                            @endif
                        </div>

                        <!-- Модальное окно отклонения -->
                        <div class="modal fade" id="rejectModal{{ $upd->id }}" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('admin.upds.reject', $upd) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Отклонение УПД</h5>
                                            <button type="button" class="close" data-dismiss="modal">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Причина отклонения</label>
                                                <textarea class="form-control" name="reason" rows="3" required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                                            <button type="submit" class="btn btn-danger">Подтвердить отклонение</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">УПД не найдены</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($documents instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="mt-4">
        {{ $documents->links() }}
    </div>
@endif
