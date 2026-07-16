<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RentalRequestItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'quantity' => $this->quantity,
            'hourly_rate' => $this->hourly_rate,
            'specifications' => $this->specifications,
            'standard_specifications' => $this->standard_specifications,
            'custom_specifications' => $this->custom_specifications,
            'formatted_specifications' => $this->formatted_specifications ?? [],
            'use_individual_conditions' => $this->use_individual_conditions,
            'individual_conditions' => $this->individual_conditions,
            'calculated_price' => $this->calculated_price,
        ];
    }
}
