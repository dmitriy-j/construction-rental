<?php

namespace App\Http\Middleware;

use App\Models\WaybillShift;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckShiftStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $shiftId = $request->route('shift');

        // Если передается модель (route model binding)
        if ($shiftId instanceof WaybillShift) {
            $shift = $shiftId;
        } else {
            $shift = WaybillShift::findOrFail($shiftId);
        }

        // Проверяем статус родительского путевого листа
        if ($shift->waybill->status !== \App\Models\Waybill::STATUS_ACTIVE) {
            abort(403, 'Редактирование смены невозможно: путевой лист не активен');
        }

        return $next($request);
    }
}
