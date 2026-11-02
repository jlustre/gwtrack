<x-guest-layout>
    <x-auth-premium-shell
        headline="Create A New Password"
        intro="Set a fresh secure password and continue your GWTracks journey with confidence."
        title="Reset Password"
        subtitle="Secure account recovery · member access"
    >
        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" placeholder="member@example.com" class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-gw-accent focus:ring-gw-accent">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">Password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="Create password" class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-gw-accent focus:ring-gw-accent">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Confirm password" class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-gw-accent focus:ring-gw-accent">
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <button type="submit" class="w-full rounded-full bg-gradient-to-r from-gw-accent-dark via-gw-accent to-gw-accent-hover px-5 py-3 text-sm font-extrabold uppercase tracking-wider text-black shadow-lg transition hover:-translate-y-0.5 hover:shadow-gw-accent/20">
                Reset Password
            </button>
        </form>
    </x-auth-premium-shell>
</x-guest-layout>
