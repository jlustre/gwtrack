<?php

namespace App\Support;

class ResourceVideoCategories
{
    /**
     * @return array<string, array{label: string, description: string, accent: string}>
     */
    public static function all(): array
    {
        return [
            'onboarding' => [
                'label' => 'Onboarding',
                'description' => 'Welcome messages, portal tours, and first-week guidance.',
                'accent' => 'bg-blue-50 text-blue-700 border-blue-100',
            ],
            'training' => [
                'label' => 'Training',
                'description' => 'Skill-building clips, product education, and field fundamentals.',
                'accent' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            ],
            'leadership' => [
                'label' => 'Leadership',
                'description' => 'Leadership messages, team culture, and agency vision.',
                'accent' => 'bg-violet-50 text-violet-700 border-violet-100',
            ],
            'product' => [
                'label' => 'Product',
                'description' => 'Product overviews, case studies, and solution explainers.',
                'accent' => 'bg-amber-50 text-amber-800 border-amber-100',
            ],
            'recruiting' => [
                'label' => 'Recruiting',
                'description' => 'Opportunity presentations and team-building content.',
                'accent' => 'bg-cyan-50 text-cyan-700 border-cyan-100',
            ],
            'general' => [
                'label' => 'General',
                'description' => 'Shared videos that apply across multiple workflows.',
                'accent' => 'bg-slate-100 text-slate-700 border-slate-200',
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
