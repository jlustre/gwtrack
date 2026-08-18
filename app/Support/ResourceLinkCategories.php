<?php

namespace App\Support;

class ResourceLinkCategories
{
    /**
     * @return array<string, array{label: string, description: string, accent: string, icon: string}>
     */
    public static function all(): array
    {
        return [
            'zoom' => [
                'label' => 'Zoom',
                'description' => 'Recurring Zoom rooms for trainings, huddles, and live sessions.',
                'accent' => 'bg-sky-50 text-sky-700 border-sky-100',
                'icon' => 'video',
            ],
            'team' => [
                'label' => 'Team',
                'description' => 'Team meetings, huddles, and leadership calls.',
                'accent' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                'icon' => 'users',
            ],
            'training' => [
                'label' => 'Training',
                'description' => 'Fast starts, product sessions, and skill-building calls.',
                'accent' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                'icon' => 'academic',
            ],
            'mentorship' => [
                'label' => 'Mentorship',
                'description' => 'CFM sessions, apprenticeship check-ins, and mentor office hours.',
                'accent' => 'bg-violet-50 text-violet-700 border-violet-100',
                'icon' => 'mentor',
            ],
            'tools' => [
                'label' => 'Tools',
                'description' => 'Portals, scheduling pages, and external productivity links.',
                'accent' => 'bg-amber-50 text-amber-800 border-amber-100',
                'icon' => 'link',
            ],
            'general' => [
                'label' => 'General',
                'description' => 'Shared links that apply across multiple workflows.',
                'accent' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'globe',
            ],
        ];
    }

    public static function label(string $key): string
    {
        return self::all()[$key]['label'] ?? str($key)->headline()->toString();
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::all());
    }
}
