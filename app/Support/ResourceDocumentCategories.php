<?php

namespace App\Support;

class ResourceDocumentCategories
{
    /**
     * @return array<string, array{label: string, description: string, accent: string}>
     */
    public static function all(): array
    {
        return [
            'onboarding' => [
                'label' => 'Onboarding',
                'description' => 'Welcome packets, getting-started guides, and first-week checklists.',
                'accent' => 'bg-blue-50 text-blue-700 border-blue-100',
            ],
            'forms' => [
                'label' => 'Forms',
                'description' => 'Applications, agreements, and printable field paperwork.',
                'accent' => 'bg-violet-50 text-violet-700 border-violet-100',
            ],
            'scripts' => [
                'label' => 'Scripts',
                'description' => 'Call scripts, presentation outlines, and conversation guides.',
                'accent' => 'bg-amber-50 text-amber-800 border-amber-100',
            ],
            'guides' => [
                'label' => 'Guides',
                'description' => 'Playbooks, how-tos, and step-by-step field references.',
                'accent' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            ],
            'compliance' => [
                'label' => 'Compliance',
                'description' => 'AML, privacy, licensing, and regulatory documentation.',
                'accent' => 'bg-rose-50 text-rose-700 border-rose-100',
            ],
            'general' => [
                'label' => 'General',
                'description' => 'Shared documents that apply across multiple workflows.',
                'accent' => 'bg-slate-100 text-slate-700 border-slate-200',
            ],
        ];
    }

    public static function label(string $key): string
    {
        return self::all()[$key]['label'] ?? str($key)->headline()->toString();
    }

    public static function optionsForSelect(): array
    {
        return collect(self::all())
            ->mapWithKeys(fn (array $category, string $key) => [$key => $category['label']])
            ->all();
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::all());
    }
}
