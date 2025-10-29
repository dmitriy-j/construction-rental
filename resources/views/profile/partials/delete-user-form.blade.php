{{-- resources/views/profile/partials/delete-user-form.blade.php --}}
<div class="border border-danger rounded p-4">
    <header class="mb-4">
        <h5 class="text-danger mb-2">
            <i class="bi bi-exclamation-triangle me-2"></i>Удаление аккаунта
        </h5>
        <p class="text-muted small mb-0">
            После удаления аккаунта все ваши данные будут безвозвратно удалены.
            Перед удалением скачайте все данные, которые хотите сохранить.
        </p>
    </header>

    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmUserDeletion">
        <i class="bi bi-trash me-2"></i>Удалить аккаунт
    </button>

    <!-- Modal -->
    <div class="modal fade" id="confirmUserDeletion" tabindex="-1" aria-labelledby="confirmUserDeletionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="modal-header">
                        <h5 class="modal-title text-danger" id="confirmUserDeletionLabel">
                            <i class="bi bi-exclamation-triangle me-2"></i>Подтверждение удаления
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <p class="mb-3">
                            Вы уверены, что хотите удалить свой аккаунт?
                        </p>
                        <p class="text-muted small mb-3">
                            После удаления аккаунта все ваши данные будут безвозвратно удалены.
                            Для подтверждения введите ваш пароль.
                        </p>

                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль для подтверждения</label>
                            <input type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                                   id="password" name="password" placeholder="Введите ваш пароль" required>
                            @error('password', 'userDeletion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Отмена
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>Удалить аккаунт
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
