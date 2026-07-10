<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Calendar\CalendarAutoConnector;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request, CalendarAutoConnector $calendar): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        $calendarRedirect = $calendar->redirectIfRequired($request, $request->user());
        if ($calendarRedirect !== null) {
            return $calendarRedirect;
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
