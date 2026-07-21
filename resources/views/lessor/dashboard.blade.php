@extends('layouts.app')

@section('title', 'Личный кабинет арендодателя')

@section('content')
<div class="container-fluid">
    <div id="lessor-dashboard-app">
        <lessor-dashboard></lessor-dashboard>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/dashboards/lessor.js')
@endpush
