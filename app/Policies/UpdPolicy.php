<?php

namespace App\Policies;

use App\Models\Upd;
use App\Models\User;

class UpdPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Upd $upd): bool
    {
        //
    }

    public function accept(User $user, Upd $upd)
    {
        return $user->hasAnyRole(['platform_super', 'platform_admin', 'financial_manager']) &&
            $upd->status === Upd::STATUS_PENDING;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Upd $upd): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Upd $upd): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Upd $upd): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Upd $upd): bool
    {
        //
    }
}
