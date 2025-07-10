<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EquipmentPolicy
{
    public function view(User $user, Equipment $equipment)
    {
        return $user->company_id === $equipment->company_id;
    }

    public function update(User $user, Equipment $equipment)
    {
        return $user->company_id === $equipment->company_id;
    }

    public function delete(User $user, Equipment $equipment)
    {
        return $user->company_id === $equipment->company_id;
    }
}
