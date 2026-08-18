<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ProfileCompletionService;
use App\Services\TodaysAgendaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, ProfileCompletionService $profileCompletion, TodaysAgendaService $todaysAgenda): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'is_online' => true,
        ])->save();

        $user->ensureDefaultRole();

        $redirect = redirect()->intended(route('dashboard', absolute: false));

        if (! $profileCompletion->isComplete($user)) {
            $redirect->with('show_profile_completion_modal', true);
        } elseif ($todaysAgenda->summaryFor($user)['has_items']) {
            $redirect->with('show_todays_agenda_modal', true);
        }

        return $redirect;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        Auth::guard('web')->logout();

        $user?->forceFill([
            'is_online' => false,
        ])->save();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
