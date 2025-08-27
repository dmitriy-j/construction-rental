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
                <th>Статус</th>
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
                        {{-- Статус УПД --}}
                        @if($act->upd)
                            <span class="badge badge-success">УПД создан</span>
                        @else
                            <span class="badge badge-secondary">УПД отсутствует</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.documents.show', ['type' => 'completion_acts', 'id' => $act->id]) }}"
                        class="btn btn-sm btn-info">Просмотр</a>
                        {{-- Кнопка генерации УПД --}}
                        @if($act->perspective == 'lessee' && !$act->upd)
                            <form action="{{ route('admin.completion-acts.generate-upd', $act) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">Сформировать УПД</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Акты выполненных работ не найдены</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $documents->links() }}
