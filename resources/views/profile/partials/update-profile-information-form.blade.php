{{-- resources/views/profile/partials/update-profile-information-form.blade.php --}}
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <!-- Этот файл теперь DEPRECATED - используем personal-information.blade.php -->
    <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-800">
            <i class="bi bi-exclamation-triangle mr-1"></i>
            Этот раздел перемещен в "Персональные данные". Используйте форму выше.
        </p>
    </div>
</section>
