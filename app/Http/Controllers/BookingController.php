<?php

namespace App\Http\Controllers;

use App\Models\AvailabilitySchedule;
use App\Models\Booking;
use App\Models\BookingEventType;
use App\Models\BookingLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function dashboard(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('view booking dashboard'), 403);

        return view('bookings.dashboard', [
            'eventTypes' => BookingEventType::query()->where('owner_id', $request->user()->id)->latest()->limit(6)->get(),
            'pendingRequests' => Booking::query()->where('cfm_id', $request->user()->id)->where('status', 'pending_approval')->latest()->limit(6)->get(),
            'upcomingBookings' => Booking::query()
                ->where(fn ($query) => $query->where('cfm_id', $request->user()->id)->orWhere('trainee_id', $request->user()->id))
                ->whereIn('status', ['confirmed', 'pending_approval'])
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at')
                ->limit(6)
                ->get(),
            'availabilitySchedules' => AvailabilitySchedule::query()
                ->where('user_id', $request->user()->id)
                ->whereNull('deleted_at')
                ->count(),
        ]);
    }

    public function availability(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('manage own availability'), 403);

        return view('bookings.availability', [
            'schedules' => AvailabilitySchedule::query()->where('user_id', $request->user()->id)->with('rules', 'overrides')->get(),
        ]);
    }

    public function eventTypes(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('manage own booking event types'), 403);

        return view('bookings.event-types', [
            'eventTypes' => BookingEventType::query()->where('owner_id', $request->user()->id)->withCount(['links', 'bookings', 'questions'])->get(),
        ]);
    }

    public function links(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('create booking links'), 403);

        return view('bookings.links', [
            'links' => BookingLink::query()->where('owner_id', $request->user()->id)->with('eventType', 'apprentice')->latest()->get(),
        ]);
    }

    public function requests(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('approve booking requests'), 403);

        return view('bookings.requests', [
            'bookings' => Booking::query()->where('cfm_id', $request->user()->id)->with('eventType', 'trainee')->latest()->paginate(12),
        ]);
    }

    public function myBookings(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('view own bookings'), 403);

        return view('bookings.my-bookings');
    }

    public function calendar(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('view own bookings'), 403);

        return view('bookings.calendar', [
            'bookings' => Booking::query()
                ->where(fn ($query) => $query->where('cfm_id', $request->user()->id)->orWhere('trainee_id', $request->user()->id))
                ->where('starts_at', '>=', now()->startOfMonth())
                ->orderBy('starts_at')
                ->get(),
        ]);
    }

    public function settings(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('manage booking settings'), 403);

        return view('bookings.settings');
    }

    public function publicPage(string $username, ?string $eventTypeSlug = null): View
    {
        $slug = Str::slug($username);
        $mentor = User::query()
            ->role('certified-field-mentor')
            ->where('is_active', true)
            ->get()
            ->first(fn (User $user): bool => Str::slug($user->name) === $slug || Str::slug(Str::before($user->email, '@')) === $slug);

        abort_unless($mentor, 404);
        $eventTypes = BookingEventType::query()
            ->where('owner_id', $mentor->id)
            ->where('is_active', true)
            ->when($eventTypeSlug, fn ($query) => $query->where('slug', $eventTypeSlug))
            ->get();
        abort_if($eventTypeSlug && $eventTypes->isEmpty(), 404);

        return view('bookings.public', [
            'mentor' => $mentor,
            'eventTypes' => $eventTypes,
            'eventTypeSlug' => $eventTypeSlug,
            'inviteLink' => null,
        ]);
    }

    public function invite(string $token): View
    {
        $link = BookingLink::query()->where('token', $token)->with('owner', 'eventType', 'apprentice')->firstOrFail();
        abort_unless($link->is_active && $link->eventType?->is_active, 404);
        abort_if($link->expires_at?->isPast(), 410, 'This booking link has expired.');
        abort_if($link->is_one_time && $link->used_at, 410, 'This booking link has already been used.');
        abort_if($link->max_uses !== null && $link->uses_count >= $link->max_uses, 410, 'This booking link has reached its booking limit.');

        return view('bookings.public', [
            'mentor' => $link?->owner,
            'eventTypes' => $link?->eventType ? collect([$link->eventType]) : collect(),
            'eventTypeSlug' => $link?->eventType?->slug,
            'inviteLink' => $link,
        ]);
    }
}
