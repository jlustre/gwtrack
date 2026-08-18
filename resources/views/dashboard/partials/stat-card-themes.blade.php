@php
    if (! function_exists('dashboardTrackerStatTheme')) {
        function dashboardTrackerStatTheme(string $key): string
        {
            return match ($key) {
                'profile' => 'navy',
                'onboarding' => 'gold',
                'credentials' => 'cyan',
                'apprenticeship' => 'emerald',
                'training' => 'violet',
                'prospects' => 'cyan',
                'hot_prospects' => 'red',
                'followups_due' => 'amber',
                'activities' => 'violet',
                'prospect_conversion' => 'emerald',
                'recruits' => 'emerald',
                'production' => 'gold',
                'fna' => 'navy',
                default => 'slate',
            };
        }
    }

    if (! function_exists('dashboardStatBarClasses')) {
        function dashboardStatBarClasses(string $theme): array
        {
            return match ($theme) {
                'gold' => ['track' => 'bg-gw-accent/20', 'fill' => 'bg-gw-accent'],
                'navy' => ['track' => 'bg-gw-primary/12', 'fill' => 'bg-gw-primary'],
                'cyan' => ['track' => 'bg-cyan-200/60', 'fill' => 'bg-cyan-600'],
                'emerald' => ['track' => 'bg-emerald-200/60', 'fill' => 'bg-emerald-600'],
                'violet' => ['track' => 'bg-violet-200/60', 'fill' => 'bg-violet-600'],
                'amber' => ['track' => 'bg-amber-200/60', 'fill' => 'bg-amber-600'],
                'red' => ['track' => 'bg-red-200/60', 'fill' => 'bg-red-600'],
                default => ['track' => 'bg-slate-200', 'fill' => 'bg-slate-600'],
            };
        }
    }

    if (! function_exists('dashboardStatCardTheme')) {
        function dashboardStatCardTheme(string $key, string $variant = 'team'): array
        {
            $themes = [
                'team' => [
                    'profile' => [
                        'card' => 'relative rounded-lg border border-gw-primary/20 border-l-4 border-l-gw-primary bg-gradient-to-br from-gw-primary/14 via-gw-primary/6 to-white p-4 shadow-sm',
                        'label' => 'text-gw-primary/75',
                        'value' => 'text-gw-primary',
                        'bar_track' => 'bg-gw-primary/12',
                        'bar_fill' => 'bg-gw-primary',
                        'button' => 'border-gw-primary/35 text-gw-primary hover:bg-gw-primary/10',
                        'icon' => 'text-gw-primary',
                    ],
                    'onboarding' => [
                        'card' => 'relative rounded-lg border border-gw-accent/30 border-l-4 border-l-gw-accent bg-gradient-to-br from-gw-accent/22 via-amber-50/80 to-white p-4 shadow-sm',
                        'label' => 'text-[#5c4a1f]',
                        'value' => 'text-gw-primary',
                        'bar_track' => 'bg-gw-accent/20',
                        'bar_fill' => 'bg-gw-accent',
                        'button' => 'border-gw-accent/50 text-gw-primary hover:bg-gw-accent/15',
                        'icon' => 'text-gw-accent',
                    ],
                    'credentials' => [
                        'card' => 'relative rounded-lg border border-sky-200/80 border-l-4 border-l-sky-700 bg-gradient-to-br from-sky-100/90 via-sky-50/60 to-white p-4 shadow-sm',
                        'label' => 'text-sky-900/75',
                        'value' => 'text-gw-primary',
                        'bar_track' => 'bg-sky-200/60',
                        'bar_fill' => 'bg-sky-700',
                        'button' => 'border-sky-600/35 text-sky-950 hover:bg-sky-100',
                        'icon' => 'text-sky-700',
                    ],
                    'apprenticeship' => [
                        'card' => 'relative rounded-lg border border-teal-200/80 border-l-4 border-l-teal-600 bg-gradient-to-br from-teal-100/80 via-emerald-50/50 to-white p-4 shadow-sm',
                        'label' => 'text-teal-900/75',
                        'value' => 'text-gw-primary',
                        'bar_track' => 'bg-teal-200/50',
                        'bar_fill' => 'bg-teal-600',
                        'button' => 'border-teal-600/35 text-teal-950 hover:bg-teal-100',
                        'icon' => 'text-teal-600',
                    ],
                    'training' => [
                        'card' => 'relative rounded-lg border border-indigo-200/80 border-l-4 border-l-indigo-600 bg-gradient-to-br from-indigo-100/80 via-violet-50/40 to-white p-4 shadow-sm',
                        'label' => 'text-indigo-900/75',
                        'value' => 'text-gw-primary',
                        'bar_track' => 'bg-indigo-200/50',
                        'bar_fill' => 'bg-indigo-600',
                        'button' => 'border-indigo-600/35 text-indigo-950 hover:bg-indigo-100',
                        'icon' => 'text-indigo-600',
                    ],
                ],
                'personal' => [
                    'profile' => [
                        'card' => 'rounded-lg border border-gw-primary/15 border-l-[3px] border-l-gw-primary bg-gradient-to-br from-gw-primary/8 to-white p-3 shadow-sm',
                        'label' => 'text-gw-primary/70',
                        'value' => 'text-gw-primary',
                        'bar_track' => 'bg-gw-primary/10',
                        'bar_fill' => 'bg-gw-primary',
                    ],
                    'onboarding' => [
                        'card' => 'rounded-lg border border-gw-accent/25 border-l-[3px] border-l-gw-accent bg-gradient-to-br from-gw-accent/14 to-amber-50/40 p-3 shadow-sm',
                        'label' => 'text-[#5c4a1f]/90',
                        'value' => 'text-gw-primary',
                        'bar_track' => 'bg-gw-accent/18',
                        'bar_fill' => 'bg-gw-accent',
                    ],
                    'credentials' => [
                        'card' => 'rounded-lg border border-sky-200/70 border-l-[3px] border-l-sky-600 bg-gradient-to-br from-sky-50/90 to-white p-3 shadow-sm',
                        'label' => 'text-sky-900/70',
                        'value' => 'text-gw-primary',
                        'bar_track' => 'bg-sky-200/50',
                        'bar_fill' => 'bg-sky-600',
                    ],
                    'apprenticeship' => [
                        'card' => 'rounded-lg border border-teal-200/70 border-l-[3px] border-l-teal-500 bg-gradient-to-br from-teal-50/80 to-white p-3 shadow-sm',
                        'label' => 'text-teal-900/70',
                        'value' => 'text-gw-primary',
                        'bar_track' => 'bg-teal-200/45',
                        'bar_fill' => 'bg-teal-500',
                    ],
                    'training' => [
                        'card' => 'rounded-lg border border-indigo-200/70 border-l-[3px] border-l-indigo-500 bg-gradient-to-br from-indigo-50/80 to-white p-3 shadow-sm',
                        'label' => 'text-indigo-900/70',
                        'value' => 'text-gw-primary',
                        'bar_track' => 'bg-indigo-200/45',
                        'bar_fill' => 'bg-indigo-500',
                    ],
                    'prospects' => [
                        'card' => 'rounded-lg border border-cyan-200/70 border-l-[3px] border-l-cyan-600 bg-gradient-to-br from-cyan-50/90 via-sky-50/40 to-white p-3 shadow-sm',
                        'label' => 'text-cyan-950/70',
                        'value' => 'text-cyan-950',
                        'bar_track' => 'bg-cyan-200/45',
                        'bar_fill' => 'bg-cyan-600',
                    ],
                    'recruits' => [
                        'card' => 'rounded-lg border border-emerald-200/70 border-l-[3px] border-l-emerald-600 bg-gradient-to-br from-emerald-50/90 via-green-50/30 to-white p-3 shadow-sm',
                        'label' => 'text-emerald-950/70',
                        'value' => 'text-emerald-950',
                        'bar_track' => 'bg-emerald-200/45',
                        'bar_fill' => 'bg-emerald-600',
                    ],
                    'production' => [
                        'card' => 'rounded-lg border border-gw-accent/35 border-l-[3px] border-l-gw-accent bg-gradient-to-br from-gw-primary via-[#132d52] to-gw-primary p-3 shadow-md',
                        'label' => 'text-gw-accent/90',
                        'value' => 'text-gw-accent',
                        'bar_track' => 'bg-white/15',
                        'bar_fill' => 'bg-gw-accent',
                    ],
                ],
            ];

            return $themes[$variant][$key] ?? $themes[$variant]['profile'];
        }
    }
@endphp
