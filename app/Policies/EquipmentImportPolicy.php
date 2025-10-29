<?php
// app/Policies/EquipmentImportPolicy.php

namespace App\Policies;

use App\Models\EquipmentImport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EquipmentImportPolicy
{
    use HandlesAuthorization;

    public function view(User $user, EquipmentImport $import)
    {
        return $user->company_id === $import->company_id;
    }

    public function create(User $user)
    {
        return $user->hasRole('company_admin') && $user->company_id;
    }

    public function delete(User $user, EquipmentImport $import)
    {
        return $user->company_id === $import->company_id;
    }
}
