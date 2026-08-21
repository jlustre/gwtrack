<?php

namespace App\Livewire\Admin;

use App\Services\MonetizationSettingsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminSettingsPage extends Component
{
    public bool $monetizationEnabled = false;

    /** @var array<string, string> */
    public array $prices = [];

    public function mount(MonetizationSettingsService $settings): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['super-admin', 'admin', 'agency-owner']), 403);

        $this->monetizationEnabled = $settings->enabled();
        $this->prices = collect($settings->priceGroups())
            ->mapWithKeys(fn (array $group) => [$group['key'] => number_format($group['amount'], 2, '.', '')])
            ->all();
    }

    public function save(MonetizationSettingsService $settings): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['super-admin', 'admin']), 403);

        $validated = $this->validate([
            'monetizationEnabled' => ['required', 'boolean'],
            'prices' => ['required', 'array'],
            'prices.*' => ['required', 'numeric', 'min:0', 'max:999.99'],
        ]);

        $settings->save(
            (bool) $validated['monetizationEnabled'],
            $validated['prices'],
        );

        $this->monetizationEnabled = $settings->enabled();
        $this->prices = collect($settings->priceGroups())
            ->mapWithKeys(fn (array $group) => [$group['key'] => number_format($group['amount'], 2, '.', '')])
            ->all();

        session()->flash('status', 'Monetization settings saved.');
    }

    public function render(MonetizationSettingsService $settings): View
    {
        return view('livewire.admin.admin-settings-page', [
            'priceGroups' => $settings->priceGroups(),
            'canEdit' => auth()->user()?->hasAnyRole(['super-admin', 'admin']) ?? false,
        ])->layout('layouts.app');
    }
}
