<?php

namespace Database\Factories;

use App\Models\DeliveryNote;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryNoteFactory extends Factory
{
    protected $model = DeliveryNote::class;

    public function definition()
    {
        return [
            'order_id' => Order::factory(),
            'delivery_date' => $this->faker->dateTimeBetween('-1 week'),
            'driver_name' => $this->faker->name,
            'receiver_name' => $this->faker->name,
            'receiver_signature_path' => $this->faker->imageUrl(),
        ];
    }
}