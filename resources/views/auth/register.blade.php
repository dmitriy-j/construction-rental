<<<<<<< HEAD
@extends('layouts.app')
@section('title', 'Регистрация юридического лица')
@section('content')
<div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-6">
    <form method="POST" action="{{ route('register.company') }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label>Название компании</label>
                <input type="text" name="company_name" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label>ИНН (10 цифр)</label>
                <input type="text" name="inn" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label>КПП (9 цифр)</label>
                <input type="text" name="kpp" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label>ОГРН (13 цифр)</label>
                <input type="text" name="ogrn" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label>БИК банка</label>
                <input type="text" name="bik" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" id="same-address" class="mr-2">
                Юридический адрес совпадает с фактическим
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label>Юридический адрес</label>
                <input type="text" name="legal_address" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label>Фактический адрес</label>
                <input type="text" id="actual-address" name="actual_address"
                       class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label>Телефон</label>
                <input type="text" name="phone" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label>Директор</label>
                <input type="text" name="director" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
            Зарегистрироваться
        </button>
    </form>
</div>

@push('scripts')
<script>
    document.getElementById('same-address').addEventListener('change', function () {
        const actualAddress = document.getElementById('actual-address');
        actualAddress.disabled = this.checked;

        if (this.checked) {
            actualAddress.value = document.querySelector("[name='legal_address']").value;
        }
    });

    // Валидация телефона
    document.querySelector("[name='phone']").addEventListener('input', function () {
        let value = this.value.replace(/\D/g, '');
        if (value.length > 11) value = value.slice(0, 11);
        this.value = '+7' + value.slice(1);
    });
</script>
@endpush
@endsection
=======
<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
>>>>>>> fcd7d4a4baa8f97b9d23a7d6c554d8533aeec4fc
