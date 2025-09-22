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
        if (! $locationIds) {
            $locationIds = Location::pluck('id')->all();
        }

        return [
            'document_number' => 'TN-'.$this->faker->unique()->numerify('########'),
            'issue_date' => $this->faker->dateTimeBetween('-1 year'),
            'type' => $this->faker->randomElement([
                DeliveryNote::TYPE_LESSOR_TO_PLATFORM,
                DeliveryNote::TYPE_PLATFORM_TO_LESSEE,
                DeliveryNote::TYPE_DIRECT,
            ]),
            'delivery_scenario' => $this->faker->randomElement([
                DeliveryNote::SCENARIO_LESSOR_PLATFORM,
                DeliveryNote::SCENARIO_PLATFORM_DIRECT,
            ]),
            'order_id' => null, // Связь будет устанавливаться отдельно
            'order_item_id' => null, // Связь будет устанавливаться отдельно
            'sender_company_id' => null, // Связь будет устанавливаться отдельно
            'receiver_company_id' => null, // Связь будет устанавливаться отдельно
            'delivery_from_id' => $this->faker->randomElement($locationIds),
            'delivery_to_id' => $this->faker->randomElement($locationIds),
            'delivery_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'cargo_description' => $this->faker->sentence(3),
            'cargo_weight' => $this->faker->randomFloat(2, 100, 10000),
            'cargo_value' => $this->faker->numberBetween(1000, 100000),
            'transport_type' => $this->faker->randomElement([
                DeliveryNote::VEHICLE_25T,
                DeliveryNote::VEHICLE_45T,
                DeliveryNote::VEHICLE_110T,
            ]),
            'equipment_condition' => $this->faker->randomElement(['Хорошее', 'Удовлетворительное', 'Новое']),
            'transport_driver_name' => $this->faker->name, // Исправлено на transport_driver_name
            'transport_vehicle_model' => $this->faker->randomElement(['КАМАЗ-65115', 'МАЗ-7510', 'Scania R730']), // Исправлено
            'transport_vehicle_number' => strtoupper($this->faker->bothify('??###??')), // Исправлено
            'driver_contact' => $this->faker->phoneNumber,
            'distance_km' => $this->faker->numberBetween(10, 500),
            'calculated_cost' => $this->faker->numberBetween(500, 5000),
            'status' => $this->faker->randomElement([
                DeliveryNote::STATUS_DRAFT,
                DeliveryNote::STATUS_IN_TRANSIT,
                DeliveryNote::STATUS_DELIVERED,
            ]),
        ];
    }
}
