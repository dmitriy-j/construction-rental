<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Models\Waybill; // Добавлен правильный импорт

class WaybillPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Order $order): bool
    {
        return $user->company_id === $order->lessor_company_id;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Waybill $waybill): bool
    {
        return $user->company_id === $waybill->order->lessor_company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Waybill $waybill): bool
    {
        return $user->company_id === $waybill->order->lessor_company_id;
    }

    /**
     * Determine whether the user can sign the waybill.
     */
    public function sign(User $user, Waybill $waybill): bool
    {
        // Проверяем, что пользователь относится к компании-арендатору
        return $user->company_id === $waybill->order->lessee_company_id;
    }
}
