<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RentalRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = $request->user();
        $isLessee = $user && $user->company && $user->company->is_lessee;
        $isLessor = $user && $user->company && $user->company->is_lessor;
        $isAdmin = $user && $user->isAdmin();
        $isGuest = !$user;

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'status_color' => $this->status_color,
            'rental_period_start' => $this->rental_period_start,
            'rental_period_end' => $this->rental_period_end,
            'location' => $this->whenLoaded('location', fn() => [
                'id' => $this->location?->id,
                'name' => $this->location?->name,
            ]),
            'items' => RentalRequestItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->items_count ?? $this->items?->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'expires_at' => $this->expires_at,
            'visibility' => $this->visibility,
            'delivery_required' => $this->delivery_required,
        ];

        // Администратор — видит всё
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

        // Арендатор (создатель заявки) — видит всё, но без контактов lessor
        elseif ($isLessee && $this->user_id === $user->id) {
            $data = array_merge($data, [
                'hourly_rate' => $this->hourly_rate,
                'total_budget' => $this->total_budget,
                'calculated_budget_from' => $this->calculated_budget_from,
                'calculated_budget_to' => $this->calculated_budget_to,
                'rental_conditions' => $this->rental_conditions,
                'proposals' => $this->whenLoaded('responses', function() {
                    // Скрываем данные lessor — только название компании
                    return $this->responses
                        ->filter(fn($r) => $r->status !== 'comment' && $r->equipment_id !== null)
                        ->values()
                        ->map(function ($response) {
                            return [
                                'id' => $response->id,
                                'proposed_price' => $response->proposed_price,
                                'proposed_quantity' => $response->proposed_quantity,
                                'counter_price' => $response->counter_price,
                                'status' => $response->status,
                                'message' => $response->message,
                                'price_breakdown' => $response->price_breakdown,
                                'additional_terms' => $response->additional_terms,
                                'expires_at' => $response->expires_at,
                                'is_bulk_main' => $response->is_bulk_main,
                                'created_at' => $response->created_at,
                                'lessor_company_name' => $response->lessor?->company?->legal_name,
                                'equipment' => $response->equipment ? [
                                    'id' => $response->equipment->id,
                                    'name' => $response->equipment->name,
                                    'brand' => $response->equipment->brand,
                                    'model' => $response->equipment->model,
                                ] : null,
                            ];
                        });
                }),
                'comments' => $this->whenLoaded('responses', function() {
                    return $this->responses
                        ->filter(fn($r) => $r->status === 'comment' || $r->equipment_id === null)
                        ->values()
                        ->map(function ($response) {
                            return [
                                'id' => $response->id,
                                'message' => $response->message,
                                'lessor_company_name' => $response->lessor?->company?->legal_name,
                                'created_at' => $response->created_at,
                            ];
                        });
                }),
                'proposals_count' => $this->responses_count ?? $this->responses?->count(),
            ]);
        }

        // Арендодатель — видит заявку со своими ценами, но без данных арендатора
        elseif ($isLessor) {
            $data = array_merge($data, [
                'lessor_pricing' => $this->lessor_pricing ?? null,
                // Скрываем оригинальные цены, показываем только lessor_pricing
                'total_equipment_quantity' => $this->total_equipment_quantity,
                'desired_specifications' => $this->desired_specifications,
                'rental_conditions' => $this->rental_conditions,
                'active_proposals_count' => $this->active_proposals_count ?? 0,
                // Данные арендатора — только название компании
                'lessee_company_name' => $this->whenLoaded('user', fn() => $this->user?->company?->legal_name),
            ]);
        }

        // Гость — только базовые поля, без цен и контактов
        elseif ($isGuest) {
            $data = array_merge($data, [
                'total_equipment_quantity' => $this->total_equipment_quantity,
                'desired_specifications' => $this->desired_specifications,
                'rental_period_start' => $this->rental_period_start,
                'rental_period_end' => $this->rental_period_end,
                'active_proposals_count' => $this->active_proposals_count ?? 0,
            ]);
            // Убираем чувствительные поля
            unset($data['description']);
            $data['description_short'] = mb_substr($this->description ?? '', 0, 200);
        }

        return $data;
    }
}
