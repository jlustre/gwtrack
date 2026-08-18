@php
    $useLivewire = $useLivewire ?? false;
    $profileFeedback = $useLivewire
        ? ($profileFeedback ?? null)
        : session('profile_feedback');
@endphp

@if ($profileFeedback)
    <div
        id="member-profile-feedback"
        class="mb-5 rounded-lg border px-4 py-3 text-sm {{ $profileFeedback['type'] === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800' }}"
        role="alert"
    >
        <p class="font-semibold {{ $profileFeedback['type'] === 'success' ? 'text-emerald-900' : 'text-red-900' }}">
            {{ $profileFeedback['type'] === 'success' ? 'Profile saved' : 'Could not save profile' }}
        </p>
        <p class="mt-1">{{ $profileFeedback['message'] }}</p>
    </div>
@endif

@if ($errors->getBag('profilePhoto')->isNotEmpty() && ! $profileFeedback)
    <div id="member-profile-feedback" class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        <p class="font-semibold text-red-900">Could not save profile photo</p>
        <ul class="mt-2 list-disc list-inside space-y-1">
            @foreach ($errors->getBag('profilePhoto')->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@elseif ($errors->any() && ! $profileFeedback && ! $useLivewire)
    <div id="member-profile-feedback" class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        <p class="font-semibold text-red-900">Could not save profile</p>
        <ul class="mt-2 list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
