<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class AdminContactController extends Controller
{
    public function index()
    {
        // Автоматически отмечаем все непрочитанные сообщения как прочитанные
        // при открытии страницы списка обращений
        ContactMessage::where('is_read', false)->update(['is_read' => true]);

        $messages = ContactMessage::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.contacts.index', compact('messages'));
    }

    public function markAsRead(ContactMessage $contact)
    {
        $contact->update(['is_read' => true]);
        return redirect()->back()->with('success', 'Обращение отмечено как прочитанное.');
    }

    public function destroy(ContactMessage $contact)
    {
        $contact->delete();
        return redirect()->back()->with('success', 'Обращение удалено.');
    }
}
