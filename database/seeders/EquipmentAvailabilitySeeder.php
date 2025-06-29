<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\EquipmentAvailability;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EquipmentAvailabilitySeeder extends Seeder
{
    public function run()
    {
        $startDate = now()->subDays(15);
        $endDate = now()->addMonths(3);

        Equipment::each(function ($equipment) use ($startDate, $endDate) {
            $current = $startDate->copy();

            while ($current <= $endDate) {
                // 90% дней доступны
                $status = rand(1, 10) <= 9 ? 'available' : 'booked';

                EquipmentAvailability::create([
                    'equipment_id' => $equipment->id,
                    'date' => $current->format('Y-m-d'),
                    'status' => $status,
                    'order_id' => null //$status === 'booked' ? rand(1, 50) : null
                ]);

                $current->addDay();
            }
        });
    }
}
