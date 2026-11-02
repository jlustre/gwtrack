@php
    $photoPreviewUrl = $photoPreviewUrl ?? $user->profilePhotoUrl();
    $photoPersistKey = 'gwtrack:profile-completion-photo:'.$user->id;
    $profileSavedSuccessfully = session('profile_feedback') && (session('profile_feedback')['type'] ?? '') === 'success';
@endphp

<div
    x-data="profilePhotoUpload(@js($photoPreviewUrl), @js($user->initials()), @js($photoPersistKey))"
    @if ($profileSavedSuccessfully)
        x-init="clearPersistedPhoto()"
    @endif
    class="rounded-lg border border-slate-200 bg-slate-50 p-4"
>
    <x-input-label for="completion_profile_photo" :value="__('Profile Photo')" />
    <p class="mt-1 text-xs text-slate-500">
        {{ __('Upload a square photo (JPEG, PNG, or WebP, up to 2 MB). It appears in the top bar, your profile header, and team views.') }}
    </p>

    @if ($hasStagedProfilePhoto ?? false)
        <p class="mt-2 text-xs font-medium text-emerald-700" role="status">
            {{ __('Your photo is saved and will upload when you save your profile.') }}
        </p>
    @endif

    <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
        <div class="flex flex-col items-center gap-2">
            <div class="relative h-20 w-20 overflow-hidden rounded-full border border-gw-accent/50 bg-gw-primary">
                <img
                    x-show="previewUrl && ! imageFailed"
                    x-cloak
                    :src="previewUrl"
                    alt="Profile photo preview"
                    class="h-20 w-20 object-cover"
                    x-on:error="onImageError()"
                >
                <span
                    x-show="! previewUrl || imageFailed"
                    x-cloak
                    x-text="initials"
                    class="flex h-20 w-20 items-center justify-center text-lg font-bold text-gw-accent"
                ></span>
            </div>
            <p class="text-xs text-slate-500">{{ __('Preview') }}</p>
        </div>

        <div class="min-w-0 flex-1 space-y-3">
            <input
                id="completion_profile_photo"
                name="photo"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-md file:border-0 file:bg-gw-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-gw-accent hover:file:bg-gw-primary-hover"
                x-ref="photoInput"
                x-on:change="onFileSelected($event)"
            >
            <button
                type="button"
                class="text-sm font-medium text-slate-600 hover:text-gw-primary"
                x-show="$refs.photoInput?.files?.length || persistedSelection"
                x-cloak
                x-on:click="clearPreview()"
            >
                {{ __('Clear selection') }}
            </button>
        </div>
    </div>

    <p x-show="clientError" x-cloak x-text="clientError" class="mt-2 text-sm text-red-600"></p>
    <x-input-error :messages="$errors->get('photo')" class="mt-2" />
</div>
