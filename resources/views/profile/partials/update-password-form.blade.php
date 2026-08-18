@php
    $useLivewire = $useLivewire ?? false;
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form
        @if ($useLivewire)
            wire:submit="updatePassword"
        @else
            method="post"
            action="{{ route('password.update') }}"
        @endif
        class="mt-6 space-y-6"
    >
        @unless ($useLivewire)
            @csrf
            @method('put')
        @endunless

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            @if ($useLivewire)
                <x-password-input id="update_password_current_password" wire:model="currentPassword" class="mt-1 block w-full" autocomplete="current-password" />
                <x-input-error :messages="$errors->get('currentPassword')" class="mt-2" />
            @else
                <x-password-input id="update_password_current_password" name="current_password" class="mt-1 block w-full" autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            @endif
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            @if ($useLivewire)
                <x-password-input id="update_password_password" wire:model="password" class="mt-1 block w-full" autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            @else
                <x-password-input id="update_password_password" name="password" class="mt-1 block w-full" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            @endif
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            @if ($useLivewire)
                <x-password-input id="update_password_password_confirmation" wire:model="passwordConfirmation" class="mt-1 block w-full" autocomplete="new-password" />
                <x-input-error :messages="$errors->get('passwordConfirmation')" class="mt-2" />
            @else
                <x-password-input id="update_password_password_confirmation" name="password_confirmation" class="mt-1 block w-full" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            @endif
        </div>

        <div class="flex items-center gap-4">
            @if ($useLivewire)
                <button
                    type="submit"
                    class="inline-flex items-center rounded-md border border-transparent bg-gw-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gw-primary-hover focus:bg-gw-primary-hover focus:outline-none focus:ring-2 focus:ring-gw-accent focus:ring-offset-2 disabled:opacity-60"
                    wire:loading.attr="disabled"
                    wire:target="updatePassword"
                >{{ __('Save') }}</button>
            @else
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            @endif

            @if (($useLivewire && $passwordStatus === 'password-updated') || (! $useLivewire && session('status') === 'password-updated'))
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
