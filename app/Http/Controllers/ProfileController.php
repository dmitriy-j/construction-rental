<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    // Автоматическое создание зеркальной накладной
    public function createMirrorNote(): DeliveryNote
    {
        return DeliveryNote::create([
            'original_note_id' => $this->id,
            'delivery_scenario' => $this->delivery_scenario,
            'type' => DeliveryNote::TYPE_PLATFORM_TO_LESSEE,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'sender_company_id' => Platform::main()->id,
            'receiver_company_id' => $this->order->lessee_company_id,
            'delivery_from_id' => $this->delivery_to_id, // Из склада платформы
            'delivery_to_id' => $this->order->delivery_location_id,
            'cargo_description' => $this->cargo_description,
            'cargo_weight' => $this->cargo_weight,
            'cargo_value' => $this->cargo_value,
            'transport_type' => $this->transport_type,
            'is_mirror' => true,
            'status' => self::STATUS_DRAFT,
        ]);
    }
}
