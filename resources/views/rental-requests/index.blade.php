@extends('layouts.app')

@section('title', 'Публичные заявки')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Заявки на аренду</h1>

    <div id="rental-requests-app"
         data-user-role="{{ $user && $user->company && $user->company->is_lessor ? 'lessor' : 'guest' }}"
         data-auth-user="{{ json_encode($user) }}">
    </div>
</div>
@endsection
