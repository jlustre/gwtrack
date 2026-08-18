<?php

namespace App\Livewire\Notifications;

use App\Services\Notifications\NotificationDeliverySetupService;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Notification Preferences')]
class NotificationPreferences extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $matrix = [];

    /** @var array<string, mixed> */
    public array $delivery = [];

    public function mount(
        NotificationPreferenceService $preferences,
        NotificationDeliverySetupService $deliverySetup,
    ): void {
        abort_unless(auth()->user()?->can('manage own notification preferences'), 403);

        $this->matrix = $preferences->matrixFor(auth()->user())->all();
        $this->delivery = $deliverySetup->statusFor(auth()->user());
    }

    public function save(NotificationPreferenceService $preferences): void
    {
        abort_unless(auth()->user()?->can('manage own notification preferences'), 403);

        $payload = [];

        foreach ($this->matrix as $typeRow) {
            foreach ($typeRow['channels'] as $channelRow) {
                $payload[] = [
                    'type_id' => $typeRow['type_id'],
                    'channel_id' => $channelRow['channel_id'],
                    'enabled' => (bool) ($channelRow['enabled'] ?? true),
                    'frequency' => $channelRow['frequency'] ?? 'immediate',
                ];
            }
        }

        $preferences->save(auth()->user(), $payload);

        session()->flash('preferences_saved', true);
    }

    public function render(): View
    {
        return view('livewire.notifications.notification-preferences', [
            'frequencies' => config('notifications.frequencies', []),
        ])->layout('layouts.app');
    }
}
