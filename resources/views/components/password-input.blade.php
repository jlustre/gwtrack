@props([
    'variant' => 'default',
])

@php
    $inputClasses = match ($variant) {
        'guest' => 'block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 pr-12 text-sm text-slate-100 placeholder:text-slate-600 focus:border-gw-accent focus:ring-gw-accent',
        default => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pr-10',
    };

    $toggleClasses = match ($variant) {
        'guest' => 'absolute inset-y-0 right-0 flex items-center rounded-r-2xl px-3 text-slate-400 transition hover:text-gw-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-gw-accent',
        default => 'absolute inset-y-0 right-0 flex items-center rounded-r-md px-3 text-slate-500 transition hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500',
    };
@endphp

<div class="relative" x-data="{ showPassword: false }">
    <input
        {{ $attributes->class([$inputClasses]) }}
        :type="showPassword ? 'text' : 'password'"
    >

    <button
        type="button"
        class="{{ $toggleClasses }}"
        x-on:click="showPassword = ! showPassword"
        :aria-label="showPassword ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
        :aria-pressed="showPassword"
    >
        <svg x-show="! showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5S21.75 12 21.75 12s-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        <svg x-show="showPassword" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c1.841 0 3.573-.487 5.077-1.337M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
        </svg>
    </button>
</div>
