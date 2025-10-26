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
                        <a href="{{ route('admin.upds.show', $upd) }}" class="btn btn-sm btn-info">Просмотр</a>
                        @if($upd->status == 'pending')
                            <form action="{{ route('admin.upds.accept', $upd) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">Принять</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#rejectModal{{ $upd->id }}">Отклонить</button>
                        @endif
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
