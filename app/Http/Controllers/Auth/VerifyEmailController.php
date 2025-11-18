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
        $user = $request->user();

        Log::channel('registration')->info('VerifyEmailController invoked', [
            'user_id' => $user->id,
            'email' => $user->email,
            'has_verified_email' => $user->hasVerifiedEmail(),
            'company_status' => $user->company?->status,
            'route_params' => $request->route()->parameters()
        ]);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            // Обновляем статус компании после верификации email
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

        Log::channel('registration')->info('Email verification completed successfully', [
            'user_id' => $user->id,
            'email_verified_at' => $user->email_verified_at
        ]);

        return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
    }
}
