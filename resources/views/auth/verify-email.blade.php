<x-guest-layout>
    <x-auth-premium-shell
        headline="Verify Your Email"
        intro="Keep your account protected and make sure important team updates reach the right inbox."
        title="Email Verification"
        subtitle="Member security · account activation"
    >
        <p class="mb-5 rounded-2xl border border-gw-accent/20 bg-[#131316] p-4 text-sm leading-6 text-slate-300">
            Before getting started, please verify your email address by clicking the link we sent to you. If you did not receive it, we can send another.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-5 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 p-4 text-sm text-emerald-100">
                A new verification link has been sent to the email address you provided during registration.
            </div>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full sm:w-auto">
                @csrf

                <button type="submit" class="w-full rounded-full bg-gradient-to-r from-gw-accent-dark via-gw-accent to-gw-accent-hover px-5 py-3 text-sm font-extrabold uppercase tracking-wider text-black shadow-lg transition hover:-translate-y-0.5 hover:shadow-gw-accent/20 sm:w-auto">
                    Resend Verification Email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto">
                @csrf

                <button type="submit" class="w-full rounded-full border border-gw-accent/40 px-5 py-3 text-sm font-bold uppercase tracking-wider text-gw-accent transition hover:bg-gw-accent/10 sm:w-auto">
                    Log Out
                </button>
            </form>
        </div>
    </x-auth-premium-shell>
</x-guest-layout>
