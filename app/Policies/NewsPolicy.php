<?php

namespace App\Policies;

use App\Models\News;
use App\Models\User;

class NewsPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, News $news): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, News $news): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Разрешаем публичный доступ
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, News $news): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    /*public function create(User $user): bool
    {
        //
    }
    */
    /**
     * Determine whether the user can update the model.
     */
    /*  public function update(User $user, News $news): bool
      {
          //
      }
*/
    /**
     * Determine whether the user can delete the model.
     */
    /*public function delete(User $user, News $news): bool
    {
        //
    }*/

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, News $news): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, News $news): bool
    {
        //
    }
}
