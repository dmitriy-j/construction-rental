<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RentalRequestResponseResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $request->user();
        $isAdmin = $user && $user->isAdmin();

        // Админ видит все данные
        if ($isAdmin) {
            return [
                'id' => $this->id,
                'rental_request_id' => $this->rental_request_id,
                'lessor_id' => $this->lessor_id,
                'lessor' => $this->lessor ? [
                    'id' => $this->lessor->id,
                    'name' => $this->lessor->name,
                    'email' => $this->lessor->email,
                    'phone' => $this->lessor->phone,
                    'company' => $this->lessor->company ? [
                        'id' => $this->lessor->company->id,
                        'legal_name' => $this->lessor->company->legal_name,
                        'inn' => $this->lessor->company->inn,
                    ] : null,
                ] : null,
                'equipment_id' => $this->equipment_id,
                'equipment' => $this->equipment ? [
                    'id' => $this->equipment->id,
                    'name' => $this->equipment->name,
                    'brand' => $this->equipment->brand,
                    'model' => $this->equipment->model,
                ] : null,
                'proposed_price' => $this->proposed_price,
                'proposed_quantity' => $this->proposed_quantity,
                'counter_price' => $this->counter_price,
                'price_breakdown' => $this->price_breakdown,
                'message' => $this->message,
                'availability_dates' => $this->availability_dates,
                'additional_terms' => $this->additional_terms,
                'status' => $this->status,
                'expires_at' => $this->expires_at,
                'is_bulk_main' => $this->is_bulk_main,
                'is_bulk_item' => $this->is_bulk_item,
                'bulk_parent_id' => $this->bulk_parent_id,
                'order_id' => $this->order_id,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        }

        // Для остальных — только основные поля
        return [
            'id' => $this->id,
            'proposed_price' => $this->proposed_price,
            'proposed_quantity' => $this->proposed_quantity,
            'counter_price' => $this->counter_price,
            'status' => $this->status,
            'message' => $this->message,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
        ];
    }
}
