@php
    if (! function_exists('cfmPortalStatCardThemeApplyShell')) {
        function cfmPortalStatCardThemeApplyShell(array $theme, bool $darkCard = false): array
        {
            $card = preg_replace('/\brounded-lg\b/', 'rounded-xl', $theme['card']);
            $card = preg_replace('/\bborder-l-\[3px\]/', 'border-l-4', $card);
            $card = str_replace(' p-3 ', ' p-3 sm:p-4 ', $card);

            if (! str_contains($card, 'sm:p-4')) {
                $card = str_replace(' p-3', ' p-3 sm:p-4', $card);
            }

            return [
                'card' => $card,
                'label' => 'text-[0.65rem] font-semibold uppercase tracking-wide '.($theme['label'] ?? 'text-slate-500'),
                'value' => 'text-2xl font-bold '.($theme['value'] ?? 'text-gw-primary'),
                'meta' => $darkCard ? 'text-xs text-gw-accent/80' : 'text-xs text-slate-600',
                'bar_track' => $theme['bar_track'] ?? 'bg-slate-200',
                'bar_fill' => $theme['bar_fill'] ?? 'bg-gw-accent',
            ];
        }
    }

    if (! function_exists('cfmPortalStatCardTheme')) {
        function cfmPortalStatCardTheme(string $toneOrKey): array
        {
            $progressKeys = [
                'onboarding' => 'onboarding',
                'licensing' => 'credentials',
                'fap' => 'apprenticeship',
                'training' => 'training',
                'rank' => 'profile',
            ];

            if (isset($progressKeys[$toneOrKey])) {
                $theme = dashboardStatCardTheme($progressKeys[$toneOrKey], 'personal');

                return cfmPortalStatCardThemeApplyShell($theme);
            }

            $toneThemeKey = match ($toneOrKey) {
                'gold' => 'onboarding',
                'sky' => 'credentials',
                'success' => 'recruits',
                'capacity' => 'production',
                'completion' => 'recruits',
                'session' => 'training',
                default => 'profile',
            };

            if ($toneOrKey === 'danger') {
                return [
                    'card' => 'relative overflow-hidden rounded-xl border border-red-200/80 border-l-4 border-l-red-600 bg-gradient-to-br from-red-50/95 via-rose-50/50 to-white p-3 shadow-sm sm:p-4',
                    'label' => 'text-[0.65rem] font-semibold uppercase tracking-wide text-red-950/70',
                    'value' => 'text-2xl font-bold text-red-700',
                    'meta' => 'text-xs text-red-900/60',
                    'bar_track' => 'bg-red-200/45',
                    'bar_fill' => 'bg-red-600',
                ];
            }

            $theme = dashboardStatCardTheme($toneThemeKey, 'personal');

            return cfmPortalStatCardThemeApplyShell($theme, $toneOrKey === 'capacity');
        }
    }
@endphp
