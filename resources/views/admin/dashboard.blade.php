@extends('layouts.app')

@section('title', 'Статистика')
@section('breadcrumbs', Breadcrumbs::render('admin-dashboard'))

@section('content')
<div class="container-fluid">
    <div id="admin-dashboard-app">
        <admin-dashboard></admin-dashboard>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/dashboards/admin.js')
@endpush
