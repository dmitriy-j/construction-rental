@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h1>УПД (Универсальные передаточные документы)</h1>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <a href="{{ route('lessor.upds.create') }}" class="btn btn-primary btn-sm">
                Загрузить УПД по акту выполненных работ
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-2">
            <div class="table-responsive" style="font-size: 0.85rem;">
                <table class="table table-hover table-sm mb-1">
                    <thead>
                        <tr>
                            <th class="p-1">Номер</th>
                            <th class="p-1">Дата УПД</th>
                            <th class="p-1">Заказ</th>
                            <th class="p-1">Арендатор</th>
                            <th class="p-1">П/лист</th>
                            <th class="p-1">Акт</th>
                            <th class="p-1">Без НДС</th>
                            <th class="p-1">НДС</th>
                            <th class="p-1">Итого</th>
                            <th class="p-1">Статус</th>
                            <th class="p-1">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upds as $upd)
                            <tr>
                                <td class="p-1">{{ $upd->number }}</td>
                                <td class="p-1">{{ $upd->issue_date->format('d.m.Y') }}</td>
                                <td class="p-1">#{{ $upd->order->id }}</td>
                                <td class="p-1" title="{{ $platformCompany->legal_name ?? 'Платформа' }}">
                                    {{ Str::limit($platformCompany->legal_name ?? 'Платформа', 15) }}
                                </td>
                                <td class="p-1">
                                    @if($upd->waybill)
                                        <a href="{{ route('lessor.waybills.show', $upd->waybill) }}" title="Путевой лист #{{ $upd->waybill->id }}">
                                            #{{ $upd->waybill->id }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="p-1">
                                    @if($upd->waybill && $upd->waybill->completionAct)
                                        <a href="{{ route('lessor.documents.completion_acts.show', $upd->waybill->completionAct) }}" title="Акт №{{ $upd->waybill->completionAct->number }}">
                                            #{{ $upd->waybill->completionAct->number }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="p-1">{{ number_format($upd->amount, 0, ',', ' ') }} ₽</td>
                                <td class="p-1">{{ number_format($upd->tax_amount, 0, ',', ' ') }} ₽</td>
                                <td class="p-1">{{ number_format($upd->total_amount, 0, ',', ' ') }} ₽</td>
                                <td class="p-1">
                                    <span class="badge badge-{{ $upd->status === 'accepted' ? 'success' : ($upd->status === 'rejected' ? 'danger' : 'warning') }}" style="font-size: 0.75rem;">
                                        {{ $upd->status === 'pending' ? 'Ожидает' : ($upd->status === 'accepted' ? 'Принят' : 'Отклонен') }}
                                    </span>
                                </td>
                                <td class="p-1">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('lessor.upds.show', $upd) }}" class="btn btn-info" title="Просмотр">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($upd->status === 'pending')
                                            <form action="{{ route('lessor.upds.destroy', $upd) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Удалить УПД?')" title="Удалить">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center p-2">УПД не найдены</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $upds->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<style>
    .table {
        margin-bottom: 0.2rem;
    }

    .table th,
    .table td {
        padding: 0.2rem;
        vertical-align: middle;
        white-space: nowrap;
    }

    .btn-group-sm > .btn,
    .btn-sm {
        padding: 0.15rem 0.3rem;
        font-size: 0.75rem;
    }

    .badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.3rem;
    }

    .card-body {
        padding: 0.5rem;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Скрываем полосу прокрутки на мобильных устройствах */
    @media (max-width: 768px) {
        .table-responsive::-webkit-scrollbar {
            display: none;
        }
    }
</style>
@endsection
