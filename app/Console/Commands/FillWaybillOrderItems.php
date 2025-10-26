<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Models\Waybill;
use Illuminate\Console\Command;

class FillWaybillOrderItems extends Command
{
    protected $signature = 'fill:waybill-order-items';

    protected $description = 'Fill order_item_id in waybills';

    public function handle()
    {
        $waybills = Waybill::whereNull('order_item_id')->get();

        foreach ($waybills as $waybill) {
            $item = OrderItem::where('order_id', $waybill->order_id)
                ->where('equipment_id', $waybill->equipment_id)
                ->first();

            if ($item) {
                $waybill->update(['order_item_id' => $item->id]);
                $this->info("Updated waybill {$waybill->id} with item {$item->id}");
            } else {
                $this->error("No item found for waybill {$waybill->id}");
            }
        }

        $this->info('Completed!');
    }
}
