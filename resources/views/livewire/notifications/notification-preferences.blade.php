<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Settings</p>
        <h1 class="text-2xl font-semibold text-gw-primary">Notification preferences</h1>
        <p class="mt-2 text-sm text-slate-600">Choose how GWTrack alerts you by category and channel.</p>
    </div>

    @if (session('preferences_saved'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            Your notification preferences were saved.
        </div>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-gw-primary">Mobile & browser delivery</h2>
        <p class="mt-1 text-sm text-slate-600">Enable push and SMS so urgent alerts reach you outside the portal.</p>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gw-primary">Push notifications</p>
                        <p class="mt-1 text-xs text-slate-500">
                            @if ($delivery['push']['enabled_globally'])
                                {{ $delivery['push']['device_count'] }} active device{{ $delivery['push']['device_count'] === 1 ? '' : 's' }}
                            @else
                                Push delivery is disabled by your administrator.
                            @endif
                        </p>
                    </div>
                    <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-bold uppercase text-violet-800">Push</span>
                </div>

                @if (count($delivery['push']['devices']) > 0)
                    <ul class="mt-3 space-y-1 text-xs text-slate-600">
                        @foreach ($delivery['push']['devices'] as $device)
                            <li>{{ $device['device_name'] }} · {{ $device['last_used_at'] ?? 'Registered' }}</li>
                        @endforeach
                    </ul>
                @endif

                <div
                    class="mt-4"
                    x-data="notificationPush({
                        vapidUrl: @js(route('notifications.push.vapid-public-key')),
                        storeUrl: @js(route('notifications.device-tokens.store')),
                        destroyUrl: @js(route('notifications.device-tokens.destroy')),
                    })"
                >
                    <button
                        type="button"
                        class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover disabled:opacity-50"
                        x-on:click="enablePush()"
                        x-bind:disabled="status === 'working' || ! browserSupported"
                    >
                        Enable push on this device
                    </button>
                    <p class="mt-2 text-xs text-slate-500" x-show="message" x-text="message"></p>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gw-primary">SMS alerts</p>
                        <p class="mt-1 text-xs text-slate-500">
                            @if ($delivery['sms']['has_phone'])
                                Sends to {{ $delivery['sms']['phone_masked'] }}
                            @else
                                Add a phone number on your profile to receive SMS.
                            @endif
                        </p>
                    </div>
                    <span class="rounded-full bg-cyan-100 px-2 py-0.5 text-[10px] font-bold uppercase text-cyan-800">SMS</span>
                </div>

                <p class="mt-3 text-xs text-slate-600">
                    @if ($delivery['sms']['enabled_globally'])
                        SMS is enabled for urgent reminders, session alerts, and critical escalations when you opt in below.
                    @else
                        SMS delivery is not enabled on this environment yet.
                    @endif
                </p>

                @unless ($delivery['sms']['has_phone'])
                    <a href="{{ route('profile.edit') }}" class="mt-4 inline-flex rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-gw-primary hover:border-gw-accent">
                        Update profile phone
                    </a>
                @endunless
            </div>
        </div>
    </section>

    <form wire:submit.prevent="save" class="space-y-4">
        @foreach ($matrix as $typeIndex => $typeRow)
            <section wire:key="pref-type-{{ $typeRow['type_id'] }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-gw-primary">{{ $typeRow['type_name'] }}</h2>

                <div class="mt-4 space-y-3">
                    @foreach ($typeRow['channels'] as $channelIndex => $channelRow)
                        <div wire:key="pref-channel-{{ $typeRow['type_id'] }}-{{ $channelRow['channel_id'] }}" class="flex flex-col gap-3 rounded-lg bg-slate-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ $channelRow['channel_name'] }}</p>
                                <p class="text-xs text-slate-500">Receive {{ strtolower($typeRow['type_name']) }} via {{ strtolower($channelRow['channel_name']) }}</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        wire:model.live="matrix.{{ $typeIndex }}.channels.{{ $channelIndex }}.enabled"
                                        class="rounded border-slate-300 text-gw-accent focus:ring-gw-accent"
                                    />
                                    Enabled
                                </label>

                                @if ($channelRow['channel_code'] === 'email')
                                    <select
                                        wire:model.live="matrix.{{ $typeIndex }}.channels.{{ $channelIndex }}.frequency"
                                        class="rounded-md border-slate-300 text-xs shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                    >
                                        @foreach ($frequencies as $frequency)
                                            <option value="{{ $frequency }}">{{ str_replace('_', ' ', ucfirst($frequency)) }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="flex flex-wrap gap-2">
            <button type="submit" class="rounded-lg bg-gw-accent px-5 py-2.5 text-sm font-bold text-gw-primary hover:bg-gw-accent-hover">
                Save Preferences
            </button>
            <a href="{{ route('notifications.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to notifications
            </a>
        </div>
    </form>
</div>
