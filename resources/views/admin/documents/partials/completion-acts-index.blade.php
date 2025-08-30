<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body text-center">
                <h5>Массовая генерация УПД для арендаторов</h5>
                <p>Создать УПД для всех актов выполненных работ, предназначенных арендаторам</p>
                <form action="{{ route('admin.completion-acts.generate-upd-all') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-invoice"></i> Сгенерировать УПД для всех арендаторов
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@if($documents->count() > 0)
<div class="row mb-3">
    <div class="col-md-12">
        <div class="alert alert-info">
            <strong>Статистика:</strong>
            Всего актов: {{ $documents->total() }} |
            С УПД: {{ $documents->where('upd_id', '!=', null)->count() }} |
            Без УПД: {{ $documents->where('upd_id', null)->count() }}
        </div>
    </div>
</div>
@endif

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Номер</th>
                <th>Дата акта</th>
                <th>Заказ</th>
                <th>Период услуг</th>
                <th>Часы работы</th>
                <th>Сумма</th>
                <th>Статус акта</th>
                <th>Статус УПД</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $act)
                <tr>
                    <td>{{ $act->number }}</td>
                    <td>{{ $act->act_date->format('d.m.Y') }}</td>
                    <td>#{{ $act->order_id }}</td>
                    <td>{{ $act->service_start_date->format('d.m.Y') }} - {{ $act->service_end_date->format('d.m.Y') }}</td>
                    <td>{{ $act->total_hours }}</td>
                    <td>{{ number_format($act->total_amount, 2) }} ₽</td>
                    <td>
                        <span class="badge badge-{{ $act->status == 'draft' ? 'secondary' : 'success' }}">
                            {{ $act->status == 'draft' ? 'Черновик' : 'Подписан' }}
                        </span>
                    </td>
                    <td>
                        @if($act->upd_id)
                            @if($act->upd)
                                <span class="badge badge-success" data-toggle="tooltip" title="УПД №{{ $act->upd->number }} от {{ $act->upd->issue_date->format('d.m.Y') }}">
                                    <i class="fas fa-file-invoice"></i> Загружен
                                </span>
                                <br>
                                <small class="text-muted">
                                    №{{ $act->upd->number }}
                                    <br>
                                    от {{ $act->upd->issue_date->format('d.m.Y') }}
                                </small>
                            @else
                                <span class="badge badge-warning" data-toggle="tooltip" title="УПД был удален">
                                    <i class="fas fa-exclamation-triangle"></i> УПД удален
                                </span>
                            @endif
                        @else
                            <span class="badge badge-secondary">
                                <i class="fas fa-times-circle"></i> Отсутствует
                            </span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.documents.show', ['type' => 'completion_acts', 'id' => $act->id]) }}"
                           class="btn btn-sm btn-info" title="Просмотр акта">
                            <i class="fas fa-eye"></i>
                        </a>

                        @if($act->upd_id)
                            @if($act->upd)
                                <a href="{{ route('admin.documents.show', ['type' => 'upds', 'id' => $act->upd->id]) }}"
                                   class="btn btn-sm btn-success mt-1" title="Перейти к УПД">
                                    <i class="fas fa-external-link-alt"></i> УПД
                                </a>
                            @endif
                        @elseif($act->perspective == 'lessee')
                            <form action="{{ route('admin.completion-acts.generate-upd', $act) }}" method="POST" class="d-inline mt-1">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary" title="Сформировать УПД">
                                    <i class="fas fa-file-invoice"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Акты выполненных работ не найдены</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $documents->links() }}

@push('scripts')
<script>
$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
@endpush
