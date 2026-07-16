@extends('layouts.app')
@php use App\Models\DeliveryNote; @endphp
@section('content')
<div class="container">
    <h1>Документы</h1>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'contracts' ? 'active' : '' }}"
               href="{{ route('documents.index', ['type' => 'contracts']) }}">Договоры</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'invoices' ? 'active' : '' }}"
               href="{{ route('documents.index', ['type' => 'invoices']) }}">Счета</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'upds' ? 'active' : '' }}"
               href="{{ route('documents.index', ['type' => 'upds']) }}">УПД</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'waybills' ? 'active' : '' }}"
               href="{{ route('documents.index', ['type' => 'waybills']) }}">Путевые листы</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'delivery_notes' ? 'active' : '' }}"
               href="{{ route('documents.index', ['type' => 'delivery_notes']) }}">Накладные</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'completion_acts' ? 'active' : '' }}"
               href="{{ route('documents.index', ['type' => 'completion_acts']) }}">Акты</a>
        </li>
    </ul>

    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        @if(request('type') === 'contracts')
                            <th>№ договора</th>
                            <th>Дата</th>
                            <th>Платформа</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        @else
                            <th>№ документа</th>
                            <th>Дата</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        @if(request('type') === 'contracts')
                            <tr>
                                <td><strong>{{ $doc->number }}</strong></td>
                                <td>{{ $doc->created_at->format('d.m.Y') }}</td>
                                <td>{{ $doc->platformCompany->legal_name ?? 'Платформа' }}</td>
                                <td><span class="badge bg-{{ $doc->is_active ? 'success' : 'secondary' }}">{{ $doc->is_active ? 'Активен' : 'Неактивен' }}</span></td>
                                <td><a href="{{ route('contracts.show', $doc) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        @elseif(in_array(request('type'), ['invoices', 'upds']))
                            <tr>
                                <td>{{ $doc->number ?? $doc->id }}</td>
                                <td>{{ $doc->created_at->format('d.m.Y') }}</td>
                                <td>{{ number_format($doc->total_amount ?? $doc->amount ?? 0, 2) }} ₽</td>
                                <td><span class="badge bg-info">{{ $doc->status ?? 'Создан' }}</span></td>
                                <td><a href="{{ url('/admin/' . request('type') . '/' . $doc->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        @else
                            <tr>
                                <td>{{ $doc->number ?? $doc->id }}</td>
                                <td>{{ $doc->created_at->format('d.m.Y H:i') }}</td>
                                <td>—</td>
                                <td><span class="badge bg-info">{{ $doc->status_text ?? 'Создан' }}</span></td>
                                <td>
                                    @if(request('type') === 'waybills')
                                        <a href="{{ route('documents.waybills.show', $doc->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    @elseif(request('type') === 'completion_acts')
                                        <a href="{{ route('documents.completion-acts.show', $doc->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    @elseif(request('type') === 'delivery_notes')
                                        <a href="{{ route('documents.delivery-notes.show', $doc->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Документы не найдены</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($documents, 'hasPages') && $documents->hasPages())
        <div class="card-footer">{{ $documents->appends(['type' => request('type')])->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>$(function () { $('[data-toggle="tooltip"]').tooltip(); $('[data-bs-toggle="tooltip"]').tooltip(); });</script>
@endpush
