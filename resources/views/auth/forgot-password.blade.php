<x-guest-layout>
    <x-auth-premium-shell
        headline="Reset Your Access"
        intro="Recover your account securely and return to your onboarding, training, and team progress."
        title="Forgot Password"
        subtitle="Secure password recovery · member access"
    >
        <p class="mb-5 rounded-2xl border border-gw-accent/20 bg-[#131316] p-4 text-sm leading-6 text-slate-300">
            Enter your email address and we will send you a password reset link.
        </p>

        <x-auth-session-status class="mb-5 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 p-4 text-sm text-emerald-100" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="member@example.com" class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-gw-accent focus:ring-gw-accent">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <button type="submit" class="w-full rounded-full bg-gradient-to-r from-gw-accent-dark via-gw-accent to-gw-accent-hover px-5 py-3 text-sm font-extrabold uppercase tracking-wider text-black shadow-lg transition hover:-translate-y-0.5 hover:shadow-gw-accent/20">
                Email Password Reset Link
            </button>

            <div class="border-t border-gw-accent/20 pt-5 text-center text-sm text-slate-400">
                Remembered your password?
                <a href="{{ route('login') }}" class="font-semibold text-gw-accent hover:text-gw-accent-hover hover:underline">Sign in</a>
            </div>
        </form>
    </x-auth-premium-shell>
</x-guest-layout>
