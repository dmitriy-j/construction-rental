<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WaybillController extends Controller
{
    public function sign(Waybill $waybill, Request $request)
    {
        $request->validate([
            'odometer_end' => 'required|integer|min:'.$waybill->odometer_start,
            'fuel_end' => 'required|numeric|min:0',
            'signature' => 'required|string'
        ]);

        $waybill->update([
            'odometer_end' => $request->odometer_end,
            'fuel_end' => $request->fuel_end,
            'fuel_consumption_actual' => $waybill->fuel_start - $request->fuel_end,
            'customer_signature_path' => $this->saveSignature($request->signature),
            'completed_at' => now()
        ]);

        event(new WaybillCompleted($waybill));

        return response()->json(['status' => 'success']);
    }

    private function saveSignature(string $svg): string
    {
        $filename = 'signatures/'.Str::uuid().'.svg';
        Storage::put($filename, $svg);
        return $filename;
    }
}
