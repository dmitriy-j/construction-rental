<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\EquipmentAvailability;
use Illuminate\Database\Seeder;

class EquipmentAvailabilitySeeder extends Seeder
{
    public function run()
    {
        $startDate = now()->subDays(15);
        $endDate = now()->addMonths(3);
        $days = $startDate->diffInDays($endDate) + 1;

        Equipment::chunk(100, function ($equipments) use ($startDate, $days) {
            $availabilities = [];

            foreach ($equipments as $equipment) {
                for ($i = 0; $i < $days; $i++) {
                    $date = $startDate->copy()->addDays($i);
                    $status = rand(1, 10) <= 9 ? 'available' : 'booked';

                    $availabilities[] = [
                        'equipment_id' => $equipment->id,
                        'date' => $date->format('Y-m-d'),
                        'status' => $status,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($availabilities) >= 500) {
                        EquipmentAvailability::insert($availabilities);
                        $availabilities = [];
                    }
                }
            }

            if (! empty($availabilities)) {
                EquipmentAvailability::insert($availabilities);
            }
        });
    }
}
