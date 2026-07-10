<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CalendarEventRequest;
use App\Models\CalendarEvent;
use App\Services\Calendar\CalendarEventManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CalendarEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $events = CalendarEvent::query()
            ->where('user_id', $request->user()->getKey())
            ->where('status', '!=', 'cancelado')
            ->orderBy('starts_at')
            ->limit(200)
            ->get()
            ->map(fn (CalendarEvent $event): array => $this->resource($event));

        return response()->json(['data' => $events]);
    }

    public function store(CalendarEventRequest $request, CalendarEventManager $manager): JsonResponse|RedirectResponse
    {
        $event = $manager->create($request->user(), $request->validated());

        if (! $request->expectsJson()) {
            $message = $event->sync_status === 'pending'
                ? 'Compromisso salvo. A sincronização com o Google foi iniciada.'
                : 'Compromisso salvo na agenda.';

            return redirect('/#agenda')->with('calendar_status', $message);
        }

        return response()->json(['data' => $this->resource($event)], 201);
    }

    public function update(CalendarEventRequest $request, CalendarEvent $event, CalendarEventManager $manager): JsonResponse
    {
        $this->authorizeEvent($request, $event);
        $event = $manager->update($event, $request->validated());

        return response()->json(['data' => $this->resource($event)]);
    }

    public function destroy(Request $request, CalendarEvent $event, CalendarEventManager $manager): JsonResponse
    {
        $this->authorizeEvent($request, $event);
        $event = $manager->cancel($event);

        return response()->json(['data' => $this->resource($event)]);
    }

    private function authorizeEvent(Request $request, CalendarEvent $event): void
    {
        abort_unless((int) $event->user_id === (int) $request->user()->getKey(), 404);
    }

    /** @return array<string, mixed> */
    private function resource(CalendarEvent $event): array
    {
        return [
            'id' => $event->getKey(),
            'title' => $event->public_title,
            'category' => $event->category,
            'status' => $event->status,
            'starts_at' => $event->starts_at->toIso8601String(),
            'ends_at' => $event->ends_at->toIso8601String(),
            'all_day' => $event->all_day,
            'source' => $event->source,
            'sync_status' => $event->sync_status,
        ];
    }
}
