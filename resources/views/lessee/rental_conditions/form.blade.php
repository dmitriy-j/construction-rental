@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    {{ isset($condition) ? 'Редактирование условий аренды' : 'Новые условия аренды' }}
                </div>

                <div class="card-body">
                    <form method="POST"
                        action="{{ isset($condition)
                            ? route('lessee.rental-conditions.update', $condition)
                            : route('lessee.rental-conditions.store') }}">
                        @csrf
                        @isset($condition) @method('PUT') @endisset

                        @include('lessee.rental_conditions.partials.form')

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ isset($condition) ? 'Обновить' : 'Создать' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
