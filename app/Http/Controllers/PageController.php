<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\CooperationRequestMail;

class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function contacts(): View
    {
        $platform = Platform::getMain();
        return view('pages.contacts', compact('platform'));
    }

    public function cooperation(): View
    {
        return view('cooperation'); // у вас представление лежит в корне views
    }

    public function submitCooperation(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'company' => 'nullable|string|max:255',
            'direction' => 'required|string',
            'region' => 'nullable|string',
            'agree' => 'accepted',
        ], [
            'name.required' => 'Укажите ваше имя',
            'phone.required' => 'Укажите контактный телефон',
            'direction.required' => 'Выберите направление сотрудничества',
            'agree.accepted' => 'Необходимо согласие на обработку данных',
        ]);

        // Отправляем email на office@fap24.ru
        Mail::to('office@fap24.ru')->send(new CooperationRequestMail($validated));

        return redirect()->route('cooperation.form')
            ->with('success', 'Ваша заявка на партнерство отправлена! Мы свяжемся с вами в ближайшее время.');
    }

    public function vacancies(): View
    {
        return view('pages.vacancies');
    }
}
