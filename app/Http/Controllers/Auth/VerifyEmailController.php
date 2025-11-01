<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));

            // ДОБАВЛЕНО: Обновляем статус компании после верификации email
            $user = $request->user();
            if ($user->company) {
                Log::channel('registration')->info('Обновление статуса компании после верификации email', [
                    'user_id' => $user->id,
                    'company_id' => $user->company->id,
                    'old_status' => $user->company->status,
                    'new_status' => 'verified'
                ]);

                $user->company->update([
                    'status' => 'verified',
                    'verified_at' => now(),
                ]);

                Log::channel('registration')->info('Статус компании успешно обновлен', [
                    'company_id' => $user->company->id,
                    'status' => $user->company->status,
                    'verified_at' => $user->company->verified_at
                ]);
            }
        }

        return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
    }
}
