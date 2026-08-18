<?php

namespace Database\Seeders;

use App\Models\GoalCategory;
use Illuminate\Database\Seeder;

class GoalCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'recruiting', 'name' => 'Recruiting Goals', 'description' => 'Team growth, invitations, presentations, and registrations.', 'icon' => 'users', 'accent_class' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'sort_order' => 10],
            ['slug' => 'production', 'name' => 'Production Goals', 'description' => 'Premium, cases, and issued business.', 'icon' => 'chart', 'accent_class' => 'border-gw-accent/40 bg-gw-accent-soft text-gw-accent-dark', 'sort_order' => 20],
            ['slug' => 'prospecting', 'name' => 'Prospecting Goals', 'description' => 'Calls, follow-ups, appointments, and referrals.', 'icon' => 'phone', 'accent_class' => 'border-sky-200 bg-sky-50 text-sky-800', 'sort_order' => 30],
            ['slug' => 'financial_review', 'name' => 'Financial Review Goals', 'description' => 'FNA, policy reviews, and client recommendations.', 'icon' => 'document', 'accent_class' => 'border-indigo-200 bg-indigo-50 text-indigo-800', 'sort_order' => 40],
            ['slug' => 'fap', 'name' => 'FAP Goals', 'description' => 'Field Apprenticeship Program milestones.', 'icon' => 'academic', 'accent_class' => 'border-violet-200 bg-violet-50 text-violet-800', 'sort_order' => 50],
            ['slug' => 'licensing', 'name' => 'Licensing Goals', 'description' => 'Courses, exams, and provincial licensing.', 'icon' => 'badge', 'accent_class' => 'border-amber-200 bg-amber-50 text-amber-800', 'sort_order' => 60],
            ['slug' => 'cfm_development', 'name' => 'CFM Development Goals', 'description' => 'Mentoring, trainee licensing, and coaching.', 'icon' => 'mentor', 'accent_class' => 'border-teal-200 bg-teal-50 text-teal-800', 'sort_order' => 70],
            ['slug' => 'leadership', 'name' => 'Leadership Goals', 'description' => 'Team growth, promotions, and leadership pipeline.', 'icon' => 'leader', 'accent_class' => 'border-gw-primary/20 bg-slate-100 text-gw-primary', 'sort_order' => 80],
            ['slug' => 'training', 'name' => 'Training Goals', 'description' => 'Resources, webinars, and certifications.', 'icon' => 'book', 'accent_class' => 'border-cyan-200 bg-cyan-50 text-cyan-800', 'sort_order' => 90],
            ['slug' => 'rank_advancement', 'name' => 'Rank Advancement Goals', 'description' => 'Promotion requirements and target dates.', 'icon' => 'rank', 'accent_class' => 'border-rose-200 bg-rose-50 text-rose-800', 'sort_order' => 100],
            ['slug' => 'income', 'name' => 'Income Goals', 'description' => 'Monthly, annual, and passive income targets.', 'icon' => 'income', 'accent_class' => 'border-lime-200 bg-lime-50 text-lime-800', 'sort_order' => 110],
            ['slug' => 'personal_development', 'name' => 'Personal Development Goals', 'description' => 'Books, speaking, and personal growth.', 'icon' => 'star', 'accent_class' => 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-800', 'sort_order' => 120],
        ];

        foreach ($categories as $category) {
            GoalCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
