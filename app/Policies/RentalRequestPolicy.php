<?php

namespace App\Policies;

use App\Models\RentalRequest;
use App\Models\User;

class RentalRequestPolicy
{
    /**
     * Определяет, может ли пользователь просматривать список своих заявок.
     */
    public function viewAny(User $user): bool
    {
        // Любой авторизованный пользователь может видеть свои заявки
        return true;
    }

    /**
     * Определяет, может ли пользователь просматривать конкретную заявку.
     */
    public function view(User $user, RentalRequest $rentalRequest): bool
    {
        // Только владелец заявки может её просматривать
        return $user->id === $rentalRequest->user_id;
    }

    /**
     * Определяет, может ли пользователь создавать заявки.
     */
    public function create(User $user): bool
    {
        // Только арендаторы могут создавать заявки
        return $user->company && $user->company->is_lessee;
    }

    /**
     * Определяет, может ли пользователь обновлять заявку.
     */
    public function update(User $user, RentalRequest $rentalRequest): bool
    {
        // Только владелец заявки может её редактировать
        // И только если заявка в статусе draft или active
        return $user->id === $rentalRequest->user_id
            && in_array($rentalRequest->status, [
                RentalRequest::STATUS_DRAFT,
                RentalRequest::STATUS_ACTIVE,
            ]);
    }

    /**
     * Определяет, может ли пользователь удалять заявку.
     */
    public function delete(User $user, RentalRequest $rentalRequest): bool
    {
        // Только владелец может удалить заявку
        // И только в статусе draft или cancelled
        return $user->id === $rentalRequest->user_id
            && in_array($rentalRequest->status, [
                RentalRequest::STATUS_DRAFT,
                RentalRequest::STATUS_CANCELLED,
            ]);
    }
}
