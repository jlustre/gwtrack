<x-guest-layout>
    <x-auth-premium-shell
        headline="Resend Verification"
        intro="Enter the email you used during registration and we will send a fresh verification link."
        title="Resend Verification Email"
        subtitle="Account activation"
    >
        <x-auth-session-status class="mb-5 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 p-4 text-sm text-emerald-100" :status="session('status')" />

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-400/30 bg-red-400/10 p-4 text-sm text-red-100">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('verification.resend.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ $email }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="member@example.com"
                    class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-gw-accent focus:ring-gw-accent"
                >
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <button type="submit" class="w-full rounded-full bg-gradient-to-r from-gw-accent-dark via-gw-accent to-gw-accent-hover px-5 py-3 text-sm font-extrabold uppercase tracking-wider text-black shadow-lg transition hover:-translate-y-0.5 hover:shadow-gw-accent/20">
                Send Verification Link
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-400">
            <a href="{{ route('login') }}" class="font-semibold text-gw-accent hover:text-gw-accent-hover">Back to sign in</a>
        </p>
    </x-auth-premium-shell>
</x-guest-layout>
