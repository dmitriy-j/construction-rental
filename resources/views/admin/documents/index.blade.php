@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Документы</h1>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['contracts'] }}</h5>
                    <p class="card-text">Договоры</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['delivery_notes'] }}</h5>
                    <p class="card-text">Накладные</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['waybills'] }}</h5>
                    <p class="card-text">Путевые листы</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['completion_acts'] }}</h5>
                    <p class="card-text">Акты выполненных работ</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['upds'] }}</h5>
                    <p class="card-text">УПД</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['invoices'] }}</h5>
                    <p class="card-text">Счета</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Навигация по типам документов -->
    <div class="card mb-4">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link {{ ($type ?? '') == 'contracts' ? 'active' : '' }}" href="{{ route('admin.documents.index', ['type' => 'contracts']) }}">Договоры</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($type ?? '') == 'delivery_notes' ? 'active' : '' }}" href="{{ route('admin.documents.index', ['type' => 'delivery_notes']) }}">Транспортные накладные</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($type ?? '') == 'waybills' ? 'active' : '' }}" href="{{ route('admin.documents.index', ['type' => 'waybills']) }}">Путевые листы</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($type ?? '') == 'completion_acts' ? 'active' : '' }}" href="{{ route('admin.documents.index', ['type' => 'completion_acts']) }}">Акты выполненных работ</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($type ?? '') == 'upds' ? 'active' : '' }}" href="{{ route('admin.documents.index', ['type' => 'upds']) }}">УПД</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($type ?? '') == 'invoices' ? 'active' : '' }}" href="{{ route('admin.documents.index', ['type' => 'invoices']) }}">Счета</a>
            </li>
        </ul>
    </div>
        <div class="card-body">
            <!-- Контент в зависимости от типа документа -->
            @if($type == 'contracts')
                @include('admin.documents.partials.contracts-index')
            @elseif($type == 'delivery_notes')
                @include('admin.documents.partials.delivery-notes-index')
            @elseif($type == 'waybills')
                @include('admin.documents.partials.waybills-index')
            @elseif($type == 'completion_acts')
                @include('admin.documents.partials.completion-acts-index')
            @elseif($type == 'upds')
                @include('admin.documents.partials.upds-index')
            @elseif($type == 'invoices')
                @include('admin.documents.partials.invoices-index')
            @endif
        </div>
    </div>
</div>
@endsection
