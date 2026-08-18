<x-guest-layout>
    <x-auth-premium-shell
        headline="Confirm Secure Access"
        intro="For sensitive actions, GWTrack asks you to confirm your password before continuing."
        title="Confirm Password"
        subtitle="Protected area · account verification"
    >
        <p class="mb-5 rounded-2xl border border-gw-accent/20 bg-[#131316] p-4 text-sm leading-6 text-slate-300">
            This is a secure area of the portal. Please confirm your password before continuing.
        </p>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
            @csrf

            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Enter password" class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-gw-accent focus:ring-gw-accent">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <button type="submit" class="w-full rounded-full bg-gradient-to-r from-gw-accent-dark via-gw-accent to-gw-accent-hover px-5 py-3 text-sm font-extrabold uppercase tracking-wider text-black shadow-lg transition hover:-translate-y-0.5 hover:shadow-gw-accent/20">
                Confirm
            </button>
        </form>
    </x-auth-premium-shell>
</x-guest-layout>
