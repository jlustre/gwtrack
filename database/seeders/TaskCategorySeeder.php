<?php

namespace Database\Seeders;

use App\Models\TaskCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaskCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories =         [
          0 => 
          [
            'name' => 'Prospect Follow-Up',
            'slug' => 'prospect-follow-up',
            'description' => 'Calls, follow-ups, and CRM activity for active prospects.',
            'action_route' => 'team.prospects',
            'action_url' => NULL,
            'action_label' => 'Open Prospects',
            'icon' => 'prospects',
            'accent_class' => 'bg-sky-50 text-sky-700 border-sky-100',
            'sort_order' => 10,
            'is_active' => true,
          ],
          1 => 
          [
            'name' => 'FNA',
            'slug' => 'fna',
            'description' => 'Financial needs analysis records, invites, and reviews.',
            'action_route' => 'team.fna.dashboard',
            'action_url' => NULL,
            'action_label' => 'Open FNA',
            'icon' => 'fna',
            'accent_class' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
            'sort_order' => 20,
            'is_active' => true,
          ],
          2 => 
          [
            'name' => 'Licensing',
            'slug' => 'licensing',
            'description' => 'Licensing milestones, exams, and compliance steps.',
            'action_route' => 'licensing.index',
            'action_url' => NULL,
            'action_label' => 'Open Licensing',
            'icon' => 'license',
            'accent_class' => 'bg-amber-50 text-amber-700 border-amber-100',
            'sort_order' => 30,
            'is_active' => true,
          ],
          3 => 
          [
            'name' => 'Onboarding',
            'slug' => 'onboarding',
            'description' => 'Day 1 scheduling and new-member onboarding checklist steps.',
            'action_route' => 'onboarding.index',
            'action_url' => NULL,
            'action_label' => 'Open My Onboarding',
            'icon' => 'onboarding',
            'accent_class' => 'bg-blue-50 text-blue-700 border-blue-100',
            'sort_order' => 35,
            'is_active' => true,
          ],
          4 => 
          [
            'name' => 'Training',
            'slug' => 'training',
            'description' => 'Courses, lessons, certifications, and academy progress.',
            'action_route' => 'training.index',
            'action_url' => NULL,
            'action_label' => 'Open Training',
            'icon' => 'training',
            'accent_class' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'sort_order' => 40,
            'is_active' => true,
          ],
          5 => 
          [
            'name' => 'CFM Mentorship',
            'slug' => 'cfm-mentorship',
            'description' => 'Mentor sessions, trainee check-ins, and CFM workflows.',
            'action_route' => 'cfm.portal',
            'action_url' => NULL,
            'action_label' => 'Open CFM Portal',
            'icon' => 'mentor',
            'accent_class' => 'bg-violet-50 text-violet-700 border-violet-100',
            'sort_order' => 50,
            'is_active' => true,
          ],
          6 => 
          [
            'name' => 'Field Apprenticeship',
            'slug' => 'field-apprenticeship',
            'description' => 'Field apprenticeship milestones and confirmations.',
            'action_route' => 'apprenticeship.index',
            'action_url' => NULL,
            'action_label' => 'Open FAP',
            'icon' => 'field',
            'accent_class' => 'bg-blue-50 text-blue-700 border-blue-100',
            'sort_order' => 60,
            'is_active' => true,
          ],
          7 => 
          [
            'name' => 'Rank Advancement',
            'slug' => 'rank-advancement',
            'description' => 'Promotion requirements, reviews, and advancement tracking.',
            'action_route' => 'rank-advancement.index',
            'action_url' => NULL,
            'action_label' => 'Open Rank Advancement',
            'icon' => 'rank',
            'accent_class' => 'bg-purple-50 text-purple-700 border-purple-100',
            'sort_order' => 70,
            'is_active' => true,
          ],
          8 => 
          [
            'name' => 'Team Meeting',
            'slug' => 'team-meeting',
            'description' => 'Team huddles, meetings, and calendar events.',
            'action_route' => 'calendar.index',
            'action_url' => NULL,
            'action_label' => 'Open Calendar',
            'icon' => 'calendar',
            'accent_class' => 'bg-teal-50 text-teal-700 border-teal-100',
            'sort_order' => 80,
            'is_active' => true,
          ],
          9 => 
          [
            'name' => 'Resource Review',
            'slug' => 'resource-review',
            'description' => 'Documents, videos, guides, and resource library items.',
            'action_route' => 'resources.index',
            'action_url' => NULL,
            'action_label' => 'Open Resources',
            'icon' => 'resources',
            'accent_class' => 'bg-cyan-50 text-cyan-700 border-cyan-100',
            'sort_order' => 90,
            'is_active' => true,
          ],
          10 => 
          [
            'name' => 'Personal',
            'slug' => 'personal',
            'description' => 'Profile updates, personal goals, and individual planning.',
            'action_route' => 'profile.edit',
            'action_url' => NULL,
            'action_label' => 'Open Profile',
            'icon' => 'profile',
            'accent_class' => 'bg-slate-100 text-slate-700 border-slate-200',
            'sort_order' => 100,
            'is_active' => true,
          ],
          11 => 
          [
            'name' => 'Assign a CFM',
            'slug' => 'assign-a-cfm',
            'description' => 'Match new members with Certified Field Mentors.',
            'action_route' => 'team.cfms',
            'action_url' => NULL,
            'action_label' => 'Assign CFM',
            'icon' => 'mentor',
            'accent_class' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
            'sort_order' => 45,
            'is_active' => true,
          ],
          12 => 
          [
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Administrative follow-ups and operational tasks.',
            'action_route' => 'tasks.index',
            'action_url' => NULL,
            'action_label' => 'Open Task Manager',
            'icon' => 'tasks',
            'accent_class' => 'bg-rose-50 text-rose-700 border-rose-100',
            'sort_order' => 110,
            'is_active' => true,
          ],
        ];

        foreach ($categories as $category) {
            TaskCategory::query()->updateOrCreate(
                ['slug' => $category['slug'] ?? Str::slug($category['name'])],
                [
                    ...$category,
                    'slug' => $category['slug'] ?? Str::slug($category['name']),
                ],
            );
        }
    }
}
