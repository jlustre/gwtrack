<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResendEmailVerificationController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.resend-verification', [
            'email' => old('email', $request->string('email')->toString()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return redirect()
            ->route('login')
            ->with('status', 'If an account with that email requires verification, we sent a new verification link.');
    }
}
