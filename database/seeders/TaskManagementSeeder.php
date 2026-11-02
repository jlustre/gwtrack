<?php

namespace Database\Seeders;

use App\Models\TaskSuggestion;
use App\Models\TaskUser;
use App\Models\User;
use App\Support\TaskLibrary;
use Illuminate\Database\Seeder;

class TaskManagementSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSuggestions();

        $agencyOwner = User::where('email', 'agency-owner@gwtracks.com')->first();
        $cfm = User::where('email', 'cfm@gwtracks.com')->first();
        $sponsor = User::where('email', 'sponsor@gwtracks.com')->first();
        $trainer = User::where('email', 'trainer@gwtracks.com')->first();

        if (! $agencyOwner) {
            return;
        }

        $this->seedAgencyOwnerTasks($agencyOwner, $cfm, $sponsor);
        $this->seedExtendedDemoTasks($agencyOwner, $cfm, $sponsor, $trainer);
    }

    private function seedAgencyOwnerTasks(User $agencyOwner, ?User $cfm, ?User $sponsor): void
    {
        $this->seedTask(
            $agencyOwner,
            $agencyOwner,
            [
                'title' => 'Follow up with David Kim on life insurance application',
                'description' => 'Review submitted docs and schedule call to finalize coverage options.',
                'priority' => 'urgent',
                'status' => 'overdue',
                'category' => 'Prospect Follow-Up',
                'related_module' => 'Prospects',
                'related_person' => 'David Kim',
                'due_date' => now()->subDay()->toDateString(),
                'progress' => 30,
            ],
            [
                ['text' => 'Review client file and previous notes', 'is_done' => true],
                ['text' => 'Prepare talking points for the call', 'is_done' => true],
                ['text' => 'Send pre-call email with agenda', 'is_done' => false],
                ['text' => 'Log outcome in CRM after call', 'is_done' => false],
            ],
            [
                ['author' => $agencyOwner, 'body' => 'Left a voicemail — will try again tomorrow morning.', 'created_at' => now()->subHours(2)],
                ['author' => $cfm, 'body' => 'Email sent with application status summary. Waiting on client response.', 'created_at' => now()->subDay()],
            ]
        );

        if ($cfm) {
            $this->seedTask(
                $agencyOwner,
                $cfm,
                [
                    'title' => 'Schedule CFM mentor review session with Marcus',
                    'description' => 'Monthly mentorship review — cover field performance and advancement criteria.',
                    'priority' => 'medium',
                    'status' => 'to_do',
                    'category' => 'CFM Mentorship',
                    'related_module' => 'Team',
                    'related_person' => 'Marcus Reed',
                    'due_date' => now()->addDays(8)->toDateString(),
                    'progress' => 0,
                ]
            );
        }

        if ($sponsor) {
            $this->seedTask(
                $agencyOwner,
                $sponsor,
                [
                    'title' => 'Send team meeting agenda for Thursday call',
                    'description' => 'Compile action items, highlight field metrics and recognition awards.',
                    'priority' => 'low',
                    'status' => 'to_do',
                    'category' => 'Team Meeting',
                    'related_module' => 'Team',
                    'due_date' => now()->addDays(4)->toDateString(),
                    'progress' => 10,
                ]
            );
        }

        $this->seedTask(
            $agencyOwner,
            $agencyOwner,
            [
                'title' => 'Review Q2 field apprenticeship progress reports',
                'description' => 'Compile scores and submit to regional director by end of week.',
                'priority' => 'high',
                'status' => 'waiting',
                'category' => 'Field Apprenticeship',
                'related_module' => 'Training',
                'related_person' => 'Apprentice Cohort',
                'due_date' => now()->addDays(10)->toDateString(),
                'progress' => 45,
            ]
        );

        $this->seedTask(
            $agencyOwner,
            $agencyOwner,
            [
                'title' => 'Prepare rank advancement documentation for Alex Torres',
                'description' => 'Gather production history, client letters and field supervisor sign-offs.',
                'priority' => 'medium',
                'status' => 'in_progress',
                'category' => 'Rank Advancement',
                'related_module' => 'Rank Advancement',
                'related_person' => 'Alex Torres',
                'due_date' => now()->addDays(12)->toDateString(),
                'progress' => 75,
            ]
        );

        $this->seedTask(
            $agencyOwner,
            $agencyOwner,
            [
                'title' => 'Complete Series 6 exam prep modules for Sandra',
                'description' => 'Assign exam prep material and set weekly check-in schedule.',
                'priority' => 'high',
                'status' => 'in_progress',
                'category' => 'Licensing',
                'related_module' => 'Licensing',
                'related_person' => 'Sandra M.',
                'due_date' => now()->addDays(6)->toDateString(),
                'progress' => 60,
            ]
        );

        $this->seedTask(
            $agencyOwner,
            $agencyOwner,
            [
                'title' => 'Upload updated training materials to resource library',
                'description' => 'New compliance slides from HQ — replace outdated versions.',
                'priority' => 'low',
                'status' => 'completed',
                'category' => 'Resource Review',
                'related_module' => 'Training',
                'related_person' => 'HQ Compliance',
                'due_date' => now()->subDays(2)->toDateString(),
                'progress' => 100,
                'completed_at' => now()->subDays(1),
            ]
        );

        $this->seedTask(
            $agencyOwner,
            $agencyOwner,
            [
                'title' => 'Onboard new prospect referral from regional network',
                'description' => 'Initial outreach call, gather info, enter into CRM pipeline.',
                'priority' => 'high',
                'status' => 'to_do',
                'category' => 'Prospect Follow-Up',
                'related_module' => 'Prospects',
                'related_person' => 'Referral #4421',
                'due_date' => now()->addDay()->toDateString(),
                'progress' => 0,
            ]
        );
    }

    private function seedExtendedDemoTasks(
        User $agencyOwner,
        ?User $cfm,
        ?User $sponsor,
        ?User $trainer,
    ): void {
        $dana = User::where('email', 'dana.foster@example.com')->first();
        $jordan = User::where('email', 'jordan.ellis@example.com')->first();
        $priya = User::where('email', 'priya.sharma@example.com')->first();
        $taylor = User::where('email', 'taylor.kim@example.com')->first();
        $licensingMember = User::where('email', 'leo.licensing@example.com')->first();
        $fapMember = User::where('email', 'maya.fap@example.com')->first();

        if ($dana) {
            $this->seedTask($agencyOwner, $dana, [
                'title' => 'Call back warm prospect Elena Vasquez',
                'description' => 'She requested a quote comparison before Friday.',
                'priority' => 'urgent',
                'status' => 'overdue',
                'category' => 'Prospect Follow-Up',
                'related_module' => 'Prospects',
                'related_person' => 'Elena Vasquez',
                'due_date' => now()->subDays(2)->toDateString(),
                'progress' => 15,
            ], [
                ['text' => 'Review last conversation notes', 'is_done' => true],
                ['text' => 'Prepare two coverage options', 'is_done' => false],
            ]);

            $this->seedTask($sponsor ?? $agencyOwner, $dana, [
                'title' => 'Submit FNA presentation notes for Chen family',
                'description' => 'Upload final slides and client summary before mentor review.',
                'priority' => 'high',
                'status' => 'in_progress',
                'category' => 'FNA',
                'related_module' => 'FNA',
                'related_person' => 'Chen Family',
                'due_date' => now()->toDateString(),
                'progress' => 70,
            ]);

            $this->seedTask($agencyOwner, $dana, [
                'title' => 'Complete weekly production log',
                'description' => 'Enter appointments, applications, and follow-up outcomes.',
                'priority' => 'medium',
                'status' => 'to_do',
                'category' => 'Personal',
                'related_module' => 'Profile',
                'due_date' => now()->addDay()->toDateString(),
                'progress' => 0,
            ]);

            $this->seedTask($cfm ?? $agencyOwner, $dana, [
                'title' => 'Prepare rank advancement evidence packet',
                'description' => 'Gather production history and client references for SM review.',
                'priority' => 'high',
                'status' => 'waiting',
                'category' => 'Rank Advancement',
                'related_module' => 'Rank Advancement',
                'related_person' => 'Dana Foster',
                'due_date' => now()->addDays(5)->toDateString(),
                'progress' => 55,
            ]);
        }

        if ($jordan) {
            $this->seedTask($cfm ?? $agencyOwner, $jordan, [
                'title' => 'Complete field ride-along reflection form',
                'description' => 'Document observations from yesterday\'s joint appointment.',
                'priority' => 'medium',
                'status' => 'to_do',
                'category' => 'CFM Mentorship',
                'related_module' => 'Training',
                'related_person' => 'Celeste Navarro',
                'due_date' => now()->addDays(3)->toDateString(),
                'progress' => 0,
            ]);

            $this->seedTask($agencyOwner, $jordan, [
                'title' => 'Review updated compliance video in resource library',
                'description' => 'Watch the Q3 compliance briefing and acknowledge completion.',
                'priority' => 'low',
                'status' => 'completed',
                'category' => 'Resource Review',
                'related_module' => 'Training',
                'due_date' => now()->subDay()->toDateString(),
                'progress' => 100,
                'completed_at' => now()->subHours(6),
            ]);

            $this->seedTask($sponsor ?? $agencyOwner, $jordan, [
                'title' => 'Confirm attendance for Thursday team huddle',
                'description' => 'Reply in team chat and add any discussion topics.',
                'priority' => 'low',
                'status' => 'to_do',
                'category' => 'Team Meeting',
                'related_module' => 'Team',
                'due_date' => now()->addDays(2)->toDateString(),
                'progress' => 0,
            ]);
        }

        if ($priya) {
            $this->seedTask($agencyOwner, $priya, [
                'title' => 'Finish FAP shadowing checklist for week 3',
                'description' => 'Log three shadow appointments and submit mentor sign-off request.',
                'priority' => 'high',
                'status' => 'in_progress',
                'category' => 'Field Apprenticeship',
                'related_module' => 'Training',
                'related_person' => 'Priya Sharma',
                'due_date' => now()->addDays(4)->toDateString(),
                'progress' => 65,
            ]);

            $this->seedTask($cfm ?? $agencyOwner, $priya, [
                'title' => 'Draft FNA intake for Rodriguez household',
                'description' => 'Collect income, debt, and protection goals before first meeting.',
                'priority' => 'medium',
                'status' => 'to_do',
                'category' => 'FNA',
                'related_module' => 'FNA',
                'related_person' => 'Rodriguez Household',
                'due_date' => now()->addDays(6)->toDateString(),
                'progress' => 10,
            ]);
        }

        if ($taylor) {
            $this->seedTask($agencyOwner, $taylor, [
                'title' => 'Complete new member onboarding profile',
                'description' => 'Upload photo, verify contact info, and finish welcome checklist.',
                'priority' => 'high',
                'status' => 'to_do',
                'category' => 'Training',
                'related_module' => 'Onboarding',
                'related_person' => 'Taylor Kim',
                'due_date' => now()->toDateString(),
                'progress' => 25,
            ]);

            $this->seedTask($sponsor ?? $agencyOwner, $taylor, [
                'title' => 'Schedule intro call with assigned sponsor',
                'description' => 'Book a 30-minute welcome call within the first week.',
                'priority' => 'medium',
                'status' => 'waiting',
                'category' => 'Personal',
                'related_module' => 'Profile',
                'related_person' => 'Marcus Rivera',
                'due_date' => now()->addDays(2)->toDateString(),
                'progress' => 40,
            ]);
        }

        if ($licensingMember) {
            $this->seedTask($agencyOwner, $licensingMember, [
                'title' => 'Book provincial licensing exam date',
                'description' => 'Select exam window and upload confirmation to licensing tracker.',
                'priority' => 'urgent',
                'status' => 'overdue',
                'category' => 'Licensing',
                'related_module' => 'Licensing',
                'related_person' => 'Leo Grant',
                'due_date' => now()->subDay()->toDateString(),
                'progress' => 20,
            ], [], [
                ['author' => $agencyOwner, 'body' => 'Exam booking portal closes this week — please prioritize.', 'created_at' => now()->subHours(4)],
            ]);
        }

        if ($fapMember) {
            $this->seedTask($cfm ?? $agencyOwner, $fapMember, [
                'title' => 'Submit week 4 field activity journal',
                'description' => 'Include client meetings, observations, and mentor feedback notes.',
                'priority' => 'medium',
                'status' => 'in_progress',
                'category' => 'Field Apprenticeship',
                'related_module' => 'Training',
                'related_person' => 'Maya Chen',
                'due_date' => now()->addDays(3)->toDateString(),
                'progress' => 80,
            ]);
        }

        if ($cfm) {
            $this->seedTask($agencyOwner, $cfm, [
                'title' => 'Review apprentice journals for Wealth Legacy Alliance',
                'description' => 'Approve or return three pending FAP submissions.',
                'priority' => 'high',
                'status' => 'to_do',
                'category' => 'CFM Mentorship',
                'related_module' => 'Training',
                'related_person' => 'Apprentice Group',
                'due_date' => now()->toDateString(),
                'progress' => 0,
            ]);

            $this->seedTask($agencyOwner, $cfm, [
                'title' => 'Sign off on FNA review for trainee submission #118',
                'description' => 'Validate client data completeness before approval.',
                'priority' => 'high',
                'status' => 'in_progress',
                'category' => 'FNA',
                'related_module' => 'FNA',
                'related_person' => 'Trainee Submission #118',
                'due_date' => now()->addDay()->toDateString(),
                'progress' => 50,
            ]);
        }

        if ($sponsor) {
            $this->seedTask($agencyOwner, $sponsor, [
                'title' => 'Follow up on two stalled prospect invitations',
                'description' => 'Resend invitation emails and log outreach attempts.',
                'priority' => 'medium',
                'status' => 'to_do',
                'category' => 'Prospect Follow-Up',
                'related_module' => 'Prospects',
                'due_date' => now()->addDays(2)->toDateString(),
                'progress' => 0,
            ]);

            $this->seedTask($agencyOwner, $sponsor, [
                'title' => 'Review downline licensing progress dashboard',
                'description' => 'Flag associates who are more than 7 days behind schedule.',
                'priority' => 'high',
                'status' => 'waiting',
                'category' => 'Licensing',
                'related_module' => 'Licensing',
                'due_date' => now()->addDays(5)->toDateString(),
                'progress' => 35,
            ]);
        }

        if ($trainer) {
            $this->seedTask($agencyOwner, $trainer, [
                'title' => 'Update training module quiz for Prospecting Fundamentals',
                'description' => 'Refresh scenario questions based on latest field playbook.',
                'priority' => 'medium',
                'status' => 'in_progress',
                'category' => 'Training',
                'related_module' => 'Training',
                'due_date' => now()->addDays(7)->toDateString(),
                'progress' => 45,
            ]);
        }

        $this->seedTask($agencyOwner, $agencyOwner, [
            'title' => 'Audit overdue admin tasks across the team',
            'description' => 'Review task queue and reassign items older than 5 days.',
            'priority' => 'medium',
            'status' => 'to_do',
            'category' => 'Admin',
            'related_module' => 'Team',
            'due_date' => now()->addDays(3)->toDateString(),
            'progress' => 0,
        ]);
    }

    private function seedSuggestions(): void
    {
        $suggestions = [
            ['icon' => '🔥', 'text' => 'Follow up with hot prospects approaching close window', 'sort_order' => 1],
            ['icon' => '📋', 'text' => 'Check overdue licensing steps for associates', 'sort_order' => 2],
            ['icon' => '🎯', 'text' => 'Schedule mentor review for associates nearing milestones', 'sort_order' => 3],
            ['icon' => '📈', 'text' => 'Review apprenticeship progress before month-end close', 'sort_order' => 4],
            ['icon' => '💼', 'text' => 'Invite inactive associates to upcoming training events', 'sort_order' => 5],
        ];

        foreach ($suggestions as $suggestion) {
            TaskSuggestion::updateOrCreate(
                ['text' => $suggestion['text']],
                [
                    'icon' => $suggestion['icon'],
                    'is_active' => true,
                    'sort_order' => $suggestion['sort_order'],
                ]
            );
        }
    }

    private function seedTask(
        User $creator,
        User $assignee,
        array $attributes,
        array $checklist = [],
        array $comments = []
    ): TaskUser {
        $libraryTask = TaskLibrary::findOrCreate(
            $attributes['title'],
            $attributes['category'],
            $attributes['description'] ?? null,
            $attributes['priority'] ?? null,
        );

        $task = TaskUser::updateOrCreate(
            [
                'assignee_id' => $assignee->id,
                'task_id' => $libraryTask->id,
                'related_person' => $attributes['related_person'] ?? null,
            ],
            [
                'task_category_id' => $libraryTask->task_category_id,
                'assignor_id' => $creator->id,
                'additional_notes' => $attributes['additional_notes'] ?? null,
                'priority' => $attributes['priority'],
                'status' => $attributes['status'],
                'related_module' => $attributes['related_module'] ?? null,
                'due_date' => $attributes['due_date'] ?? null,
                'progress' => $attributes['progress'] ?? 0,
                'reminder' => $attributes['reminder'] ?? null,
                'completed_at' => $attributes['completed_at'] ?? null,
            ]
        );

        $task->checklistItems()->delete();
        foreach ($checklist as $index => $item) {
            $task->checklistItems()->create([
                'text' => $item['text'],
                'is_done' => $item['is_done'] ?? false,
                'sort_order' => $index + 1,
            ]);
        }

        $task->comments()->delete();
        foreach ($comments as $comment) {
            $task->comments()->create([
                'user_id' => $comment['author']->id,
                'body' => $comment['body'],
                'created_at' => $comment['created_at'] ?? now(),
                'updated_at' => $comment['created_at'] ?? now(),
            ]);
        }

        return $task;
    }
}
