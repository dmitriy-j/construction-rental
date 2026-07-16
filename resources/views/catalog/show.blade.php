@extends('layouts.app')

@section('title', $equipment->title . ' — Каталог техники')

@section('content')
<div id="catalog-detail-app">
    <!-- Vue-компонент CatalogDetail будет смонтирован сюда -->
</div>
@endsection

@push('scripts')
<script>
    // Данные для Vue-компонента
    window.__EQUIPMENT_ID__ = {{ $equipment->id }};
</script>
@endpush
