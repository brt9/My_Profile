<?php

use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\GoogleCalendarOAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Models\GoogleCalendarConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', HomeController::class);

Route::get('/dashboard', function (Request $request) {
    $adminEmail = mb_strtolower(trim((string) config('portfolio.admin_email')));
    $userEmail = mb_strtolower(trim((string) $request->user()?->email));
    $canManageCalendar = $adminEmail !== '' && hash_equals($adminEmail, $userEmail);

    return Inertia::render('Dashboard', [
        'canManageCalendar' => $canManageCalendar,
        'googleCalendarConfigured' => filled(config('services.google_calendar.client_id'))
            && filled(config('services.google_calendar.client_secret')),
        'googleCalendarConnected' => $canManageCalendar && GoogleCalendarConnection::query()
            ->whereBelongsTo($request->user())
            ->exists(),
        'googleCalendarWriteEnabled' => (bool) config('services.google_calendar.write_enabled'),
    ]);
})->middleware(['auth', 'verified.environment'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified.environment', 'portfolio.admin'])->prefix('admin/calendar')->name('calendar.')->group(function () {
    Route::get('/connect', [GoogleCalendarOAuthController::class, 'connect'])->name('connect');
    Route::get('/callback', [GoogleCalendarOAuthController::class, 'callback'])->name('callback');
    Route::delete('/revoke', [GoogleCalendarOAuthController::class, 'revoke'])->name('revoke');
});

Route::middleware(['auth', 'verified.environment', 'portfolio.admin'])->prefix('api/calendar/events')->name('calendar.events.')->group(function () {
    Route::get('/', [CalendarEventController::class, 'index'])->name('index');
    Route::post('/', [CalendarEventController::class, 'store'])->name('store');
    Route::put('/{event}', [CalendarEventController::class, 'update'])->name('update');
    Route::delete('/{event}', [CalendarEventController::class, 'destroy'])->name('destroy');
});

require __DIR__.'/auth.php';
