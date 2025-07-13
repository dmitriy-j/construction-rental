<?php

namespace Database\Factories;

use App\Models\DeliveryNote;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryNoteFactory extends Factory
{
    protected $model = DeliveryNote::class;

    public function definition()
    {
        static $locationIds;
        if (!$locationIds) {
            $locationIds = Location::pluck('id')->all();
        }

        return [
            'order_id' => null, // Связь будет устанавливаться отдельно
            'equipment_condition' => $this->faker->optional()->sentence,
            'delivery_from_id' => $this->faker->randomElement($locationIds),
            'delivery_to_id' => $this->faker->randomElement($locationIds),
            'delivery_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'driver_name' => $this->faker->name,
            'receiver_name' => $this->faker->name,
            'receiver_signature_path' => $this->faker->optional()->imageUrl(),
            'vehicle_type' => $this->faker->randomElement([
                DeliveryNote::VEHICLE_25T,
                DeliveryNote::VEHICLE_45T,
                DeliveryNote::VEHICLE_110T
            ]),
            'distance_km' => $this->faker->numberBetween(10, 500),
            'calculated_cost' => $this->faker->numberBetween(500, 5000),
        ];
    }
}
