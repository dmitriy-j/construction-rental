<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RentalRequestResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $request->user();

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
        ];

        // Items — для всех (чтобы гости/lessor видели позиции)
        if ($this->relationLoaded('items')) {
            $data['items'] = RentalRequestItemResource::collection($this->items);
            // Категория из первого item
            $firstItem = $this->items->first();
            $data['category'] = $firstItem?->category?->name;
            $data['category_id'] = $firstItem?->category_id;
        }

        // Авторизованные пользователи — дополнительные данные
        if ($user) {
            $isLessee = $user->company && $user->company->is_lessee;
            $isLessor = $user->company && $user->company->is_lessor;
            $isAdmin = $user->isAdmin();

            // Админ — всё
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
                        'inn' => $this->company?->inn,
                        'phone' => $this->company?->phone,
                    ]),
                    'hourly_rate' => $this->hourly_rate,
                    'total_budget' => $this->total_budget,
                    'calculated_budget_from' => $this->calculated_budget_from,
                    'calculated_budget_to' => $this->calculated_budget_to,
                    'responses' => $this->whenLoaded('responses', function() {
                        return RentalRequestResponseResource::collection($this->responses);
                    }),
                    'proposals_count' => $this->responses_count ?? $this->responses?->count(),
                ]);
            }

            // Арендатор (создатель) — видит всё без контактов lessor
            elseif ($isLessee && $this->user_id === $user->id) {
                $data = array_merge($data, [
                    'hourly_rate' => $this->hourly_rate,
                    'total_budget' => $this->total_budget,
                    'calculated_budget_from' => $this->calculated_budget_from,
                    'calculated_budget_to' => $this->calculated_budget_to,
                    'rental_conditions' => $this->rental_conditions,
                    'proposals' => $this->whenLoaded('responses', function() { /* ... */ return []; }),
                    'proposals_count' => $this->responses_count ?? $this->responses?->count(),
                ]);
            }

            // Арендодатель — без данных арендатора, с lessor_pricing
            elseif ($isLessor) {
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
