@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Документы</h1>
    
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'contracts' ? 'active' : '' }}" 
               href="?type=contracts">Договоры</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'waybills' ? 'active' : '' }}" 
               href="?type=waybills">Путевые листы</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'delivery_notes' ? 'active' : '' }}" 
               href="?type=delivery_notes">Накладные</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'completion_acts' ? 'active' : '' }}" 
               href="?type=completion_acts">Акты выполненных работ</a>
        </li>
    </ul>

    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Заказ</th>
                        <th>Контрагент</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                    <tr>
                        <td>{{ $doc->id }}</td>
                        <td>{{ $doc->created_at->format('d.m.Y') }}</td>
                        <td>Заказ #{{ $doc->order_id }}</td>
                        <td>
                            {{ auth()->user()->isLessee() 
                                ? $doc->order->lessorCompany->legal_name
                                : $doc->order->lesseeCompany->legal_name }}
                        </td>
                        <td>{{ $doc->status ?? '-' }}</td>
                        <td>
                            <a href="{{ route('documents.download', [$doc->id, $type]) }}" 
                               class="btn btn-sm btn-outline-primary">
                                Скачать
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">Документы не найдены</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($documents->hasPages())
        <div class="card-footer">
            {{ $documents->appends(['type' => request('type')])->links() }}
        </div>
        @endif
    </div>
</div>
@endsection