@php
    $useLivewire = $useLivewire ?? false;
@endphp

<section x-data="profilePhotoUpload(@js($user->profilePhotoUrl()), @js($user->initials()))">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Profile Photo') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Upload a square photo (JPEG, PNG, or WebP, up to 2 MB). It appears in the top bar, your profile header, and team views.') }}
        </p>
    </header>

    <div class="mt-6 flex flex-col gap-6 sm:flex-row sm:items-start">
        <div class="flex flex-col items-center gap-2">
            <div class="relative h-24 w-24 overflow-hidden rounded-full border border-gw-accent/50 bg-gw-primary">
                <img
                    x-show="previewUrl && ! imageFailed"
                    x-cloak
                    :src="previewUrl"
                    alt="Profile photo preview"
                    class="h-24 w-24 object-cover"
                    x-on:error="onImageError()"
                >
                <span
                    x-show="! previewUrl || imageFailed"
                    x-cloak
                    x-text="initials"
                    class="flex h-24 w-24 items-center justify-center text-xl font-bold text-gw-accent"
                ></span>
            </div>
            <p class="text-xs text-slate-500">Preview</p>
        </div>

        <div class="min-w-0 flex-1 space-y-4">
            <form
                @if ($useLivewire)
                    wire:submit="uploadPhoto"
                @else
                    method="post"
                    action="{{ route('profile.photo.update') }}"
                    enctype="multipart/form-data"
                @endif
                class="space-y-4"
            >
                @unless ($useLivewire)
                    @csrf
                @endunless

                <div>
                    <x-input-label for="profile_photo" :value="__('Choose Photo')" />
                    <input
                        id="profile_photo"
                        @if ($useLivewire)
                            wire:model="photo"
                        @else
                            name="photo"
                        @endif
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:rounded-md file:border-0 file:bg-gw-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-gw-accent hover:file:bg-gw-primary-hover"
                        x-ref="photoInput"
                        x-on:change="onFileSelected($event)"
                    >
                    @if ($useLivewire)
                        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                    @else
                        <x-input-error :messages="$errors->getBag('profilePhoto')->get('photo')" class="mt-2" />
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if ($useLivewire)
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-md border border-transparent bg-gw-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gw-primary-hover focus:bg-gw-primary-hover focus:outline-none focus:ring-2 focus:ring-gw-accent focus:ring-offset-2 disabled:opacity-60"
                            wire:loading.attr="disabled"
                            wire:target="uploadPhoto,photo"
                        >
                            <span wire:loading.remove wire:target="uploadPhoto,photo">{{ __('Upload Photo') }}</span>
                            <span wire:loading wire:target="uploadPhoto,photo">Uploading…</span>
                        </button>
                    @else
                        <x-primary-button>{{ __('Upload Photo') }}</x-primary-button>
                    @endif
                    <button
                        type="button"
                        class="text-sm font-medium text-slate-600 hover:text-gw-primary"
                        x-show="$refs.photoInput?.files?.length"
                        x-cloak
                        x-on:click="clearPreview()"
                    >
                        {{ __('Clear selection') }}
                    </button>
                </div>
            </form>

            @if ($user->profile?->profile_photo_path)
                @if ($useLivewire)
                    <button
                        type="button"
                        class="text-sm font-semibold text-red-600 hover:text-red-700"
                        wire:click="removePhoto"
                        wire:confirm="Remove your profile photo?"
                    >
                        {{ __('Remove Photo') }}
                    </button>
                @else
                    <form
                        method="post"
                        action="{{ route('profile.photo.destroy') }}"
                        onsubmit="return confirm('Remove your profile photo?');"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700">
                            {{ __('Remove Photo') }}
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</section>
