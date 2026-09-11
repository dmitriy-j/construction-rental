@extends('layouts.app')

@section('title', $equipment->title . ' — Каталог техники')

@section('content')
<div id="catalog-detail-app">
    <!-- Vue-компонент CatalogDetail будет смонтирован сюда -->
</div>
@endsection

@push('scripts')
<script>
    window.__EQUIPMENT_ID__ = {{ $equipment->id }};
    window.isAuthenticated = {{ auth()->check() ? 'true' : 'false' }};
    window.csrfToken = '{{ csrf_token() }}';
</script>
@endpush
