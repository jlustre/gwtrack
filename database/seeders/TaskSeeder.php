<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['category' => 'Prospect Follow-Up', 'title' => 'Follow up with a prospect', 'description' => 'Reach out to an active prospect and log the activity in CRM.', 'priority' => 'high', 'module' => 'Prospects'],
            ['category' => 'Prospect Follow-Up', 'title' => 'Schedule a prospect appointment', 'description' => 'Book the next meeting or call with a warm lead.', 'priority' => 'medium', 'module' => 'Prospects'],
            ['category' => 'FNA', 'title' => 'Send FNA client invite', 'description' => 'Invite a client to complete their financial needs analysis.', 'priority' => 'high', 'module' => 'FNA'],
            ['category' => 'Licensing', 'title' => 'Complete licensing milestone', 'description' => 'Finish the next licensing checklist item or exam prep step.', 'priority' => 'high', 'module' => 'Licensing'],
            ['category' => 'Onboarding', 'title' => 'Schedule onboarding start date', 'description' => 'Open My Onboarding tracker and choose your Day 1 start date to begin your checklist.', 'priority' => 'high', 'module' => 'Onboarding'],
            ['category' => 'Training', 'title' => 'Complete next training lesson', 'description' => 'Continue academy lessons or assigned certification work.', 'priority' => 'medium', 'module' => 'Training'],
            ['category' => 'Training', 'title' => 'Enroll in training path', 'description' => 'Open the Training Center and enroll in the learning path assigned by your CFM or trainer.', 'priority' => 'high', 'module' => 'Training'],
            ['category' => 'CFM Mentorship', 'title' => 'Schedule mentor review session', 'description' => 'Book a mentorship check-in with your trainee or CFM.', 'priority' => 'medium', 'module' => 'Team'],
            ['category' => 'Assign a CFM', 'title' => 'Assign a CFM to a new associate', 'description' => 'Match a new member with a Certified Field Mentor.', 'priority' => 'high', 'module' => 'Team'],
            ['category' => 'Field Apprenticeship', 'title' => 'Review apprenticeship progress', 'description' => 'Check FAP milestones and confirm next field steps.', 'priority' => 'medium', 'module' => 'Training'],
            ['category' => 'Rank Advancement', 'title' => 'Prepare rank advancement package', 'description' => 'Gather requirements and submit promotion documentation.', 'priority' => 'medium', 'module' => 'Rank Advancement'],
            ['category' => 'Team Meeting', 'title' => 'Prepare team meeting agenda', 'description' => 'Outline topics, metrics, and recognition for the next team call.', 'priority' => 'low', 'module' => 'Team'],
            ['category' => 'Resource Review', 'title' => 'Review assigned resource', 'description' => 'Read or watch a library item assigned to your workflow.', 'priority' => 'low', 'module' => 'Resources'],
            ['category' => 'Personal', 'title' => 'Update member profile', 'description' => 'Complete profile fields needed for team visibility.', 'priority' => 'low', 'module' => 'Profile'],
            ['category' => 'Personal', 'title' => 'Complete your member profile', 'description' => 'Fill in the required profile fields so your team can support your onboarding.', 'priority' => 'high', 'module' => 'Profile'],
            ['category' => 'Admin', 'title' => 'Complete administrative follow-up', 'description' => 'Handle an operational or admin task from your queue.', 'priority' => 'medium', 'module' => 'Admin'],
        ];

        foreach ($definitions as $index => $definition) {
            $category = TaskCategory::query()->where('name', $definition['category'])->first();

            if (! $category) {
                continue;
            }

            $slug = Str::slug($definition['title']);

            Task::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'task_category_id' => $category->id,
                    'title' => $definition['title'],
                    'description' => $definition['description'],
                    'default_priority' => $definition['priority'],
                    'related_module' => $definition['module'],
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ],
            );
        }
    }
}
