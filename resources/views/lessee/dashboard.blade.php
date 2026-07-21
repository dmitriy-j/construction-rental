@extends('layouts.app')

@section('title', 'Личный кабинет арендатора')

@section('content')
<div class="container-fluid">
    <div id="lessee-dashboard-app">
        <lessee-dashboard></lessee-dashboard>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/dashboards/lessee.js')
@endpush
