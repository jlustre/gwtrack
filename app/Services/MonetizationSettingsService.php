<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\UserSubscription;
use App\Support\GreatwayRankCatalog;

class MonetizationSettingsService
{
    public const SETTINGS_KEY = 'monetization';

    public const SESSION_KEY = 'monetization_enabled';

    /** @var array{enabled: bool, prices: array<string, float>}|null */
    private ?array $cachedSettings = null;

    public function enabled(): bool
    {
        return (bool) ($this->settings()['enabled'] ?? false);
    }

    public function sessionEnabled(): bool
    {
        if (session()->has(self::SESSION_KEY)) {
            return (bool) session(self::SESSION_KEY);
        }

        return $this->syncToSession();
    }

    public function syncToSession(): bool
    {
        $enabled = $this->enabled();

        session()->put(self::SESSION_KEY, $enabled);

        return $enabled;
    }

    /**
     * @return list<array{key: string, label: string, codes: list<string>, amount: float}>
     */
    public function priceGroups(): array
    {
        $defaults = $this->defaultGroups();
        $savedAmounts = $this->settings()['prices'] ?? [];

        return array_map(function (array $group) use ($savedAmounts): array {
            $amount = round((float) ($savedAmounts[$group['key']] ?? $group['amount']), 2);

            return [
                ...$group,
                'amount' => $amount,
                'annual_amount' => round($amount * $this->annualMonths(), 2),
            ];
        }, $defaults);
    }

    /**
     * @return array<string, float>
     */
    public function pricesByRankCode(): array
    {
        $prices = [];

        foreach ($this->priceGroups() as $group) {
            foreach ($group['codes'] as $code) {
                $prices[$code] = $group['amount'];
            }
        }

        return $prices;
    }

    public function amountForRank(?string $code): float
    {
        if ($code === null || $code === '') {
            return 0.0;
        }

        return round((float) ($this->pricesByRankCode()[$code] ?? 0), 2);
    }

    public function annualMonths(): int
    {
        return max(1, (int) config('monetization.annual_months', 12));
    }

    public function amountForInterval(?string $code, string $interval): float
    {
        $monthly = $this->amountForRank($code);

        if ($interval === UserSubscription::INTERVAL_ANNUAL) {
            return round($monthly * $this->annualMonths(), 2);
        }

        return $monthly;
    }

    public function rankLabel(?string $code): string
    {
        if ($code === null || $code === '') {
            return 'Unranked';
        }

        foreach ($this->priceGroups() as $group) {
            if (in_array($code, $group['codes'], true)) {
                return $group['label'];
            }
        }

        $definition = GreatwayRankCatalog::definition($code);

        if (is_array($definition) && filled($definition['name'] ?? null)) {
            return $definition['name'].' ('.$code.')';
        }

        return $code;
    }

    /**
     * @return array{
     *     enabled: bool,
     *     price_groups: list<array{key: string, label: string, codes: list<string>, amount: float, amount_label: string}>
     * }
     */
    public function registrationDisclosure(): array
    {
        return [
            'enabled' => $this->enabled(),
            'price_groups' => array_map(fn (array $group): array => [
                ...$group,
                'amount_label' => '$'.number_format($group['amount'], 2),
                'annual_amount_label' => '$'.number_format($group['annual_amount'], 2),
            ], $this->priceGroups()),
        ];
    }

    /**
     * @param  array<string, float|int|string>  $pricesByGroupKey
     */
    public function save(bool $enabled, array $pricesByGroupKey): void
    {
        $prices = [];

        foreach ($this->defaultGroups() as $group) {
            $prices[$group['key']] = round((float) ($pricesByGroupKey[$group['key']] ?? $group['amount']), 2);
        }

        AppSetting::query()->updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            [
                'value' => [
                    'enabled' => $enabled,
                    'prices' => $prices,
                ],
            ],
        );

        $this->cachedSettings = [
            'enabled' => $enabled,
            'prices' => $prices,
        ];

        $this->syncToSession();
    }

    /**
     * @return array{enabled: bool, prices: array<string, float>}
     */
    private function settings(): array
    {
        if ($this->cachedSettings !== null) {
            return $this->cachedSettings;
        }

        $stored = AppSetting::query()->where('key', self::SETTINGS_KEY)->value('value');

        if (! is_array($stored)) {
            return $this->cachedSettings = [
                'enabled' => (bool) config('monetization.enabled', false),
                'prices' => [],
            ];
        }

        return $this->cachedSettings = [
            'enabled' => (bool) ($stored['enabled'] ?? false),
            'prices' => is_array($stored['prices'] ?? null) ? $stored['prices'] : [],
        ];
    }

    /**
     * @return list<array{key: string, label: string, codes: list<string>, amount: float}>
     */
    private function defaultGroups(): array
    {
        return config('monetization.price_groups', []);
    }
}
