<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RentalRequestResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $request->user();
        $isLessee = $user && $user->company && $user->company->is_lessee;
        $isLessor = $user && $user->company && $user->company->is_lessor;
        $isAdmin = $user && $user->isAdmin();

        // Определяем категорию из items
        $categoryName = null;
        $categoryId = null;
        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            $firstItem = $this->items->first();
            $categoryName = $firstItem->category?->name;
            $categoryId = $firstItem->category_id;
        }

        // Базовая информация — для ВСЕХ (гости, lessee, lessor, admin)
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'description_short' => mb_substr($this->description ?? '', 0, 200),
            'status' => $this->status,
            'status_text' => $this->status_text,
            'status_color' => $this->status_color,
            'rental_period_start' => $this->rental_period_start,
            'rental_period_end' => $this->rental_period_end,
            'location' => $this->whenLoaded('location', fn() => [
                'id' => $this->location?->id,
                'name' => $this->location?->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'expires_at' => $this->expires_at,
            'visibility' => $this->visibility,
            'delivery_required' => $this->delivery_required,
            'items_count' => $this->items_count ?? $this->items?->count(),
            'category' => $categoryName,
            'category_id' => $categoryId,
            'items' => $this->whenLoaded('items', fn() =>
                RentalRequestItemResource::collection($this->items)
            ),
        ];

        // Авторизованные пользователи — дополнительные данные
        if ($user) {
            if ($isAdmin) {
                $data = array_merge($data, [
                    'user_id' => $this->user_id,
                    'company_id' => $this->company_id,
                    'user' => $this->whenLoaded('user', fn() => [
                        'id' => $this->user?->id,
                        'name' => $this->user?->name,
                        'email' => $this->user?->email,
                        'phone' => $this->user?->phone,
                    ]),
                    'company' => $this->whenLoaded('company', fn() => [
                        'id' => $this->company?->id,
                        'legal_name' => $this->company?->legal_name,
                    ]),
                    'hourly_rate' => $this->hourly_rate,
                    'total_budget' => $this->total_budget,
                    'calculated_budget_from' => $this->calculated_budget_from,
                    'calculated_budget_to' => $this->calculated_budget_to,
                    'proposals_count' => $this->responses_count ?? $this->responses?->count(),
                ]);
            } elseif ($isLessee && $this->user_id === $user->id) {
                $data = array_merge($data, [
                    'hourly_rate' => $this->hourly_rate,
                    'total_budget' => $this->total_budget,
                    'calculated_budget_from' => $this->calculated_budget_from,
                    'calculated_budget_to' => $this->calculated_budget_to,
                    'rental_conditions' => $this->rental_conditions,
                    'proposals_count' => $this->responses_count ?? $this->responses?->count(),
                ]);
            } elseif ($isLessor) {
                $data = array_merge($data, [
                    'lessor_pricing' => $this->lessor_pricing ?? null,
                    'rental_conditions' => $this->rental_conditions,
                    'active_proposals_count' => $this->active_proposals_count ?? 0,
                ]);
            }
        }

        return $data;
    }
}
