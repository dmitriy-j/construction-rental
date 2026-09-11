@extends('layouts.app')

@section('title', 'Каталог техники')

@section('content')
<div id="catalog-app"></div>
@endsection

@push('scripts')
<script>
    window.isAuthenticated = {{ auth()->check() ? 'true' : 'false' }};
    window.csrfToken = '{{ csrf_token() }}';
</script>
@endpush
