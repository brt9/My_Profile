<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Calendar\CalendarDashboard;
use Illuminate\View\View;

final class CalendarPageController extends Controller
{
    public function __invoke(CalendarDashboard $calendarDashboard): View
    {
        $portfolio = config('portfolio');

        return view('calendar', [
            'portfolio' => $portfolio,
            'calendar' => ($portfolio['integrations']['calendar'] ?? false)
                ? $calendarDashboard->forHome()
                : null,
            'title' => 'Agenda e Google Calendar API — '.$portfolio['name'],
            'metaDescription' => 'Estudo de caso sobre uma agenda Laravel integrada ao Google Calendar com OAuth, filas e projeção segura de dados.',
        ]);
    }
}
