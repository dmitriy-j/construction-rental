<?php

namespace App\Http\Controllers;

use App\Mail\ContactNotification;
use App\Models\ContactMessage;
use App\Models\Equipment;
use App\Models\News;
use App\Models\RentalRequest;
use App\Models\User;
use App\Notifications\NewContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class HomeController extends Controller
{
    /**
     * Главная страница сайта.
     */
    public function index()
    {
        // Популярная техника: 6 случайных утверждённых единиц с фото
        $popularEquipment = Equipment::where('is_approved', true)
            ->with(['mainImage', 'category', 'rentalTerms', 'location'])
            ->inRandomOrder()
            ->take(6)
            ->get();

        // Статистика платформы
        $stats = [
            'lessors' => User::whereHas('company', function ($q) {
                $q->where('is_lessor', true);
            })->count(),
            'lessees' => User::whereHas('company', function ($q) {
                $q->where('is_lessee', true);
            })->count(),
            'orders' => RentalRequest::count(),
            'equipment' => Equipment::where('is_approved', true)->count(),
        ];

        $latestNews = NewsController::getLatest(4);

        return view('home', compact('popularEquipment', 'stats', 'latestNews'));
    }

    /**
     * Отправка формы обратной связи.
     */
    public function contact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'message' => 'nullable|string|max:5000',
        ]);

        // 1. Сохраняем в БД
        $contactMessage = ContactMessage::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'message' => $validated['message'] ?? null,
            'is_read' => false,
        ]);

        // 2. Отправляем email администратору
        try {
            $adminEmail = env('ADMIN_EMAIL', 'office@fap24.ru');
            Mail::to($adminEmail)->send(new ContactNotification($contactMessage));
        } catch (\Exception $e) {
            \Log::warning('Не удалось отправить email администратору: ' . $e->getMessage());
        }

        // 3. Создаём уведомление в БД для всех администраторов (Laravel Notifications)
        try {
            $admins = User::all()->filter(fn($u) => $u->isPlatformAdmin());
            foreach ($admins as $admin) {
                $admin->notify(new NewContactMessage($contactMessage));
            }
        } catch (\Exception $e) {
            \Log::warning('Не удалось создать уведомление админам: ' . $e->getMessage());
        }

        // 4. Возвращаем успешный ответ
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Спасибо! Мы свяжемся с вами в ближайшее время.',
            ]);
        }

        return redirect()->back()->with('success', 'Спасибо! Мы свяжемся с вами в ближайшее время.');
    }
}
