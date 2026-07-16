@extends('layouts.app')

@section('title', 'Публичные заявки')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Заявки на аренду</h1>

    <div id="rental-requests-app">
        <rental-requests
            :user-role="{{ json_encode($user && $user->company && $user->company->is_lessor ? 'lessor' : 'guest') }}"
            :auth-user="{{ json_encode($user) }}"
            :initial-categories="{{ json_encode($categories) }}"
            :initial-locations="{{ json_encode($locations) }}"
            :debug-info="{{ json_encode([
                'is_authenticated' => Auth::check(),
                'user_id' => $user ? $user->id : null,
                'is_lessor' => $user && $user->company ? $user->company->is_lessor : false,
                'requests_count' => $rentalRequests->count(),
                'categories_count' => $categories->count(),
                'locations_count' => $locations->count()
            ]) }}"
        />
    </div>
</div>
@endsection
