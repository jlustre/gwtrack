<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationPushSmsEnhancementSeeder extends Seeder
{
    public function run(): void
    {
        $triggerIds = DB::table('notification_triggers')->pluck('id', 'code');

        $enhancements = [
            'mentor_assigned' => [
                'channels' => ['in_app', 'email', 'push', 'sms'],
                'sms_body' => '{{ member_name }}, {{ mentor_name }} is now your CFM. Log into GWTracks to connect.',
                'push_title' => 'Mentor assigned',
                'push_body' => '{{ mentor_name }} is now your Certified Field Mentor.',
            ],
            'training_assigned' => [
                'channels' => ['in_app', 'push'],
                'push_title' => 'Training assigned',
                'push_body' => 'New course ready: {{ module_title }}',
            ],
            'task_assigned' => [
                'channels' => ['in_app', 'push'],
                'push_title' => 'New task assigned',
                'push_body' => '{{ task_name }} was assigned to you.',
            ],
            'task_overdue' => [
                'channels' => ['in_app', 'email', 'push', 'sms'],
                'sms_body' => 'GWTracks: Task overdue - {{ task_name }}. Due {{ deadline }}.',
                'push_title' => 'Task overdue',
                'push_body' => '{{ task_name }} is past due.',
            ],
            'calendar_event_reminder' => [
                'channels' => ['in_app', 'push', 'sms'],
                'sms_body' => 'Reminder: {{ event_title }} at {{ session_time }}.',
                'push_title' => 'Event reminder',
                'push_body' => '{{ event_title }} starts at {{ session_time }}.',
            ],
            'booking_confirmed' => [
                'channels' => ['in_app', 'push'],
                'push_title' => 'Session confirmed',
                'push_body' => 'Your session with {{ mentor_name }} is confirmed for {{ session_time }}.',
            ],
            'cfm_assignment_pending_confirm' => [
                'channels' => ['in_app', 'email', 'push', 'sms'],
                'sms_body' => 'GWTracks: Confirm trainee assignment for {{ member_name }}.',
                'push_title' => 'Confirm trainee assignment',
                'push_body' => '{{ member_name }} was assigned to you. Please confirm.',
            ],
            'goal_off_track' => [
                'channels' => ['in_app', 'push'],
                'push_title' => 'Goal off track',
                'push_body' => '{{ goal_name }} is behind schedule ({{ progress }}%).',
            ],
            'goal_reminder' => [
                'channels' => ['in_app', 'push'],
                'push_title' => 'Goal reminder',
                'push_body' => '{{ message }}',
            ],
            'prospect_follow_up_overdue' => [
                'channels' => ['in_app', 'push'],
                'push_title' => 'Follow-up overdue',
                'push_body' => '{{ prospect_name }} needs follow-up today.',
            ],
            'support_ticket_urgent' => [
                'channels' => ['in_app', 'email', 'push', 'sms'],
                'sms_body' => 'Urgent support ticket {{ ticket_number }} submitted.',
                'push_title' => 'Urgent support ticket',
                'push_body' => 'Ticket {{ ticket_number }} needs immediate attention.',
            ],
        ];

        foreach ($enhancements as $triggerCode => $data) {
            $triggerId = $triggerIds[$triggerCode] ?? null;

            if (! $triggerId) {
                continue;
            }

            DB::table('notification_templates')
                ->where('notification_trigger_id', $triggerId)
                ->where('is_default', true)
                ->update(array_filter([
                    'channels' => json_encode($data['channels']),
                    'sms_body' => $data['sms_body'] ?? null,
                    'push_title' => $data['push_title'] ?? null,
                    'push_body' => $data['push_body'] ?? null,
                    'updated_at' => now(),
                ], fn ($value) => $value !== null));
        }
    }
}
