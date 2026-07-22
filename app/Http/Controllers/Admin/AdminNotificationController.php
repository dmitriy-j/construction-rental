<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    /**
     * Показать все уведомления администратора.
     */
    public function index()
    {
        $notifications = Auth::user()->notifications()
            ->whereIn('type', [
                'App\Notifications\AdminSystemNotification',
                'App\Notifications\NewContactMessage',
            ])
            ->paginate(30);

        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * Отметить все уведомления как прочитанные.
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications()
            ->whereIn('type', [
                'App\Notifications\AdminSystemNotification',
                'App\Notifications\NewContactMessage',
            ])
            ->update(['read_at' => now()]);

        return redirect()->back()->with('success', 'Все уведомления помечены как прочитанные');
    }

    /**
     * Отметить одно уведомление как прочитанное.
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $data = $notification->data;
        if (!empty($data['action_url'])) {
            return redirect($data['action_url']);
        }

        return redirect()->back()->with('success', 'Уведомление помечено как прочитанное');
    }

    /**
     * Получить количество непрочитанных уведомлений (для бейджа).
     */
    public function unreadCount()
    {
        $count = Auth::user()->unreadNotifications()
            ->whereIn('type', [
                'App\Notifications\AdminSystemNotification',
                'App\Notifications\NewContactMessage',
            ])
            ->count();

        return response()->json(['count' => $count]);
    }
}
