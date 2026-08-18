<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProspectLookupSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLookup('prospect_sources', [
            'Warm Market',
            'Cold Market',
            'Social Media Lead',
            'Event/Webinar Lead',
            'Referral',
            'Family',
            'Friend',
            'Church/Community Contact',
            'Business Owner',
            'Professional Contact',
        ]);

        $this->seedLookup('prospect_types', [
            'Life Insurance Prospect',
            'Recruiting Prospect',
            'Business Opportunity Prospect',
            'Referral Partner',
            'Existing Client',
            'Warm Market',
            'Cold Market',
            'Social Media Lead',
            'Event/Webinar Lead',
            'Family',
            'Friend',
            'Church/Community Contact',
            'Business Owner',
            'Professional Contact',
        ]);

        $this->seedLookup('prospect_interests', [
            'Term Life Insurance',
            'Whole Life Insurance',
            'Universal Life Insurance',
            'Indexed Universal Life',
            'Final Expense',
            'Mortgage Protection',
            'Retirement Planning',
            'Annuities',
            'Critical Illness',
            'Disability Insurance',
            "Children's Protection",
            'Business Insurance',
            'Key Person Insurance',
            'Wealth Building',
            'Tax Strategies',
            'Estate Planning',
            'Becoming an Associate',
            'Career Opportunity',
        ]);

        $this->seedPipelineStages();
        $this->seedLookup('communication_types', [
            'Call',
            'Text Message',
            'Email',
            'Private Message',
            'Zoom Meeting',
            'In-Person Meeting',
            'Webinar Invite',
            'Presentation',
            'Follow-Up',
            'No Answer',
            'Voicemail Left',
            'Referral Received',
        ]);

        $this->seedLookup('appointment_types', [
            'Discovery Call',
            'Financial Needs Analysis',
            'Career Overview',
            'Product Presentation',
            'Application Review',
            'Follow-Up Meeting',
            'Mentor-Assisted Call',
        ]);

        $this->seedLookup('followup_statuses', [
            'Pending',
            'Completed',
            'Overdue',
            'Cancelled',
        ]);

        $this->seedSharePermissions();
        $this->seedTags();
    }

    private function seedLookup(string $table, array $names): void
    {
        foreach ($names as $index => $name) {
            DB::table($table)->updateOrInsert(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                    'deleted_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedPipelineStages(): void
    {
        $stages = [
            'New Lead',
            'Contacted',
            'Follow-Up Needed',
            'Appointment Scheduled',
            'Presentation Completed',
            'Application Started',
            'Application Submitted',
            'Pending Approval',
            'Approved',
            'Became Client',
            'Became Associate',
            'Not Interested',
            'Do Not Contact',
            'Archived',
        ];

        foreach ($stages as $index => $stage) {
            DB::table('pipeline_stages')->updateOrInsert(
                [
                    'user_id' => null,
                    'slug' => Str::slug($stage),
                ],
                [
                    'name' => $stage,
                    'sort_order' => ($index + 1) * 10,
                    'is_terminal' => in_array($stage, ['Became Client', 'Became Associate', 'Not Interested', 'Do Not Contact', 'Archived'], true),
                    'is_active' => true,
                    'deleted_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedSharePermissions(): void
    {
        $permissions = [
            ['view_only', 'View Only', ['can_view' => true]],
            ['add_notes', 'Add Notes', ['can_view' => true, 'can_add_notes' => true]],
            ['add_communication_logs', 'Add Communication Logs', ['can_view' => true, 'can_add_notes' => true, 'can_add_communications' => true]],
            ['schedule_followups', 'Schedule Follow-Ups', ['can_view' => true, 'can_add_notes' => true, 'can_schedule_followups' => true]],
            ['schedule_appointments', 'Schedule Appointments', ['can_view' => true, 'can_add_notes' => true, 'can_schedule_appointments' => true]],
            ['edit_limited_fields', 'Edit Limited Fields', ['can_view' => true, 'can_add_notes' => true, 'can_edit_limited_fields' => true]],
            ['full_collaboration', 'Full Collaboration Access', [
                'can_view' => true,
                'can_add_notes' => true,
                'can_add_communications' => true,
                'can_schedule_followups' => true,
                'can_schedule_appointments' => true,
                'can_edit_limited_fields' => true,
                'can_collaborate_fully' => true,
            ]],
        ];

        foreach ($permissions as $index => [$key, $name, $flags]) {
            DB::table('prospect_share_permissions')->updateOrInsert(
                ['key' => $key],
                [
                    'name' => $name,
                    'description' => $name.' for shared prospect collaboration.',
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                    ...$flags,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedTags(): void
    {
        $tags = [
            ['Hot Lead', '#DC2626'],
            ['Needs Mentor Call', '#7C3AED'],
            ['Referral', '#2563EB'],
            ['Follow Up This Week', '#D97706'],
            ['Potential Recruit', '#059669'],
        ];

        foreach ($tags as [$name, $color]) {
            DB::table('prospect_tags')->updateOrInsert(
                [
                    'user_id' => null,
                    'slug' => Str::slug($name),
                ],
                [
                    'name' => $name,
                    'color' => $color,
                    'deleted_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
