@vite(['resources/css/app.css', 'resources/js/app.js'])
@extends('layouts.app')

@section('content')
<div class="bg-white p-6 rounded-lg shadow">
  <h1 class="text-2xl font-bold mb-4">О компании</h1>
  <p>Текст описания компании...</p>
</div>
@endsection
=======
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ __('You are logged in!') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

