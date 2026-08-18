@php
    $profileFeedback = session('profile_feedback');
@endphp

@if ($profileFeedback)
    <div
        id="cfm-portal-profile-feedback"
        class="rounded-lg border px-4 py-3 text-sm {{ $profileFeedback['type'] === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800' }}"
        role="alert"
    >
        <p class="font-semibold">
            {{ $profileFeedback['type'] === 'success' ? 'Profile saved' : 'Could not save profile' }}
        </p>
        <p class="mt-1">{{ $profileFeedback['message'] }}</p>
    </div>
@endif

@if ($errors->any() && ! $profileFeedback)
    <div id="cfm-portal-profile-feedback" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        <p class="font-semibold">Could not save profile</p>
        <ul class="mt-2 list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
