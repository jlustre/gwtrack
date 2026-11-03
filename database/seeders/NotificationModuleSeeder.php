<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedChannels();
        $this->seedTypes();
        $this->seedTriggers();
        $this->seedTemplates();
        $this->seedPreferenceDefaults();
    }

    private function seedChannels(): void
    {
        $channels = [
            ['code' => 'in_app', 'name' => 'In-App', 'sort_order' => 10],
            ['code' => 'email', 'name' => 'Email', 'sort_order' => 20],
            ['code' => 'sms', 'name' => 'SMS', 'sort_order' => 30, 'is_user_selectable' => true],
            ['code' => 'push', 'name' => 'Push', 'sort_order' => 40, 'is_user_selectable' => true],
        ];

        foreach ($channels as $channel) {
            DB::table('notification_channels')->updateOrInsert(
                ['code' => $channel['code']],
                array_merge([
                    'is_active' => true,
                    'is_user_selectable' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $channel),
            );
        }
    }

    private function seedTypes(): void
    {
        $types = [
            ['code' => 'system', 'name' => 'System Notification', 'group' => 'system', 'icon' => 'bell-alert', 'color' => '#475569', 'sort_order' => 1],
            ['code' => 'account', 'name' => 'Account Notification', 'group' => 'system', 'icon' => 'user-circle', 'color' => '#475569', 'sort_order' => 2],
            ['code' => 'registration', 'name' => 'Registration Notification', 'group' => 'onboarding', 'icon' => 'user-plus', 'color' => '#2563EB', 'sort_order' => 3],
            ['code' => 'onboarding', 'name' => 'Onboarding Notification', 'group' => 'onboarding', 'icon' => 'clipboard-document-check', 'color' => '#2563EB', 'sort_order' => 4],
            ['code' => 'cfm_assignment', 'name' => 'CFM Assignment Notification', 'group' => 'mentorship', 'icon' => 'user-group', 'color' => '#0F766E', 'sort_order' => 5],
            ['code' => 'fap', 'name' => 'FAP Notification', 'group' => 'mentorship', 'icon' => 'academic-cap', 'color' => '#0F766E', 'sort_order' => 6],
            ['code' => 'mentoring', 'name' => 'Mentoring', 'group' => 'mentorship', 'icon' => 'user-group', 'color' => '#0F766E', 'sort_order' => 7],
            ['code' => 'licensing', 'name' => 'Licensing', 'group' => 'compliance', 'icon' => 'document-check', 'color' => '#B45309', 'sort_order' => 8],
            ['code' => 'training', 'name' => 'Training', 'group' => 'training', 'icon' => 'book-open', 'color' => '#2563EB', 'sort_order' => 9],
            ['code' => 'prospect', 'name' => 'Prospect Notification', 'group' => 'sales', 'icon' => 'funnel', 'color' => '#7C3AED', 'sort_order' => 10],
            ['code' => 'fna', 'name' => 'FNA Management', 'group' => 'sales', 'icon' => 'chart-bar', 'color' => '#7C3AED', 'sort_order' => 11],
            ['code' => 'calendar', 'name' => 'Calendar Notification', 'group' => 'scheduling', 'icon' => 'calendar', 'color' => '#0891B2', 'sort_order' => 12],
            ['code' => 'booking', 'name' => 'Booking Notification', 'group' => 'scheduling', 'icon' => 'calendar-days', 'color' => '#0891B2', 'sort_order' => 13],
            ['code' => 'task', 'name' => 'Task Notification', 'group' => 'accountability', 'icon' => 'check-circle', 'color' => '#84BB41', 'sort_order' => 14],
            ['code' => 'goal', 'name' => 'Goals & Performance', 'group' => 'performance', 'icon' => 'flag', 'color' => '#84BB41', 'sort_order' => 15],
            ['code' => 'rank_advancement', 'name' => 'Rank Advancement Notification', 'group' => 'career', 'icon' => 'arrow-trending-up', 'color' => '#7C3AED', 'sort_order' => 16],
            ['code' => 'production', 'name' => 'Production Notification', 'group' => 'performance', 'icon' => 'currency-dollar', 'color' => '#84BB41', 'sort_order' => 17],
            ['code' => 'recruiting', 'name' => 'Recruiting Notification', 'group' => 'performance', 'icon' => 'users', 'color' => '#84BB41', 'sort_order' => 18],
            ['code' => 'message', 'name' => 'Message Notification', 'group' => 'communication', 'icon' => 'chat-bubble-left', 'color' => '#0F766E', 'sort_order' => 19],
            ['code' => 'resource', 'name' => 'Resource Notification', 'group' => 'content', 'icon' => 'folder-open', 'color' => '#475569', 'sort_order' => 20],
            ['code' => 'support_ticket', 'name' => 'Support', 'group' => 'support', 'icon' => 'lifebuoy', 'color' => '#DC2626', 'sort_order' => 21],
            ['code' => 'compliance', 'name' => 'Compliance Notification', 'group' => 'compliance', 'icon' => 'shield-exclamation', 'color' => '#B45309', 'sort_order' => 22],
            ['code' => 'announcement', 'name' => 'Announcement', 'group' => 'system', 'icon' => 'megaphone', 'color' => '#475569', 'sort_order' => 23],
            ['code' => 'recognition', 'name' => 'Recognition Notification', 'group' => 'engagement', 'icon' => 'star', 'color' => '#84BB41', 'sort_order' => 24],
            ['code' => 'risk_alert', 'name' => 'Risk Alert', 'group' => 'leadership', 'icon' => 'exclamation-triangle', 'color' => '#DC2626', 'sort_order' => 25],
            ['code' => 'escalation', 'name' => 'Escalation Alert', 'group' => 'leadership', 'icon' => 'arrow-up-circle', 'color' => '#DC2626', 'sort_order' => 26],
        ];

        foreach ($types as $type) {
            DB::table('notification_types')->updateOrInsert(
                ['code' => $type['code']],
                array_merge($type, [
                    'description' => $type['name'],
                    'is_active' => true,
                    'user_configurable' => true,
                    'digest_eligible' => true,
                    'deleted_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }
    }

    private function seedTriggers(): void
    {
        $typeIds = DB::table('notification_types')->pluck('id', 'code');

        $triggers = [
            // Legacy / core triggers (preserve codes used in tests)
            ['type' => 'training', 'code' => 'training_assigned', 'name' => 'Training Assigned', 'event_key' => 'training.assigned', 'sort_order' => 10],
            ['type' => 'training', 'code' => 'training_completed', 'name' => 'Training Completed', 'event_key' => 'training.completed', 'sort_order' => 20],
            ['type' => 'training', 'code' => 'assessment_result', 'name' => 'Assessment Result', 'event_key' => 'training.assessment_result', 'sort_order' => 30],
            ['type' => 'mentoring', 'code' => 'mentor_assigned', 'name' => 'Mentor Assigned', 'event_key' => 'mentor.assigned', 'sort_order' => 10],
            ['type' => 'mentoring', 'code' => 'mentor_assignment_declined', 'name' => 'Mentor Assignment Declined', 'event_key' => 'mentor.assignment_declined', 'sort_order' => 15],
            ['type' => 'mentoring', 'code' => 'apprenticeship_completed', 'name' => 'Apprenticeship Completed', 'event_key' => 'apprenticeship.completed', 'sort_order' => 20],
            ['type' => 'mentoring', 'code' => 'apprenticeship_ended', 'name' => 'Mentorship Assignment Ended', 'event_key' => 'apprenticeship.ended', 'sort_order' => 30],
            ['type' => 'mentoring', 'code' => 'cfm_certification_submitted', 'name' => 'CFM Certification Submitted', 'event_key' => 'cfm_certification.submitted', 'sort_order' => 40],
            ['type' => 'mentoring', 'code' => 'cfm_certification_decision', 'name' => 'CFM Certification Decision', 'event_key' => 'cfm_certification.decision', 'sort_order' => 50],
            ['type' => 'booking', 'code' => 'booking_confirmed', 'name' => 'Booking Confirmed', 'event_key' => 'booking.confirmed', 'sort_order' => 10],
            ['type' => 'booking', 'code' => 'booking_request_received', 'name' => 'Booking Request Received', 'event_key' => 'booking.request_received', 'sort_order' => 20],
            ['type' => 'booking', 'code' => 'booking_declined', 'name' => 'Booking Declined', 'event_key' => 'booking.declined', 'sort_order' => 30],
            ['type' => 'booking', 'code' => 'booking_cancelled', 'name' => 'Booking Cancelled', 'event_key' => 'booking.cancelled', 'sort_order' => 40],
            ['type' => 'booking', 'code' => 'booking_reschedule_requested', 'name' => 'Booking Reschedule Requested', 'event_key' => 'booking.reschedule_requested', 'sort_order' => 50],
            ['type' => 'booking', 'code' => 'booking_reschedule_decision', 'name' => 'Booking Reschedule Decision', 'event_key' => 'booking.reschedule_decision', 'sort_order' => 60],
            ['type' => 'licensing', 'code' => 'licensing_step_approved', 'name' => 'Licensing Step Approved', 'event_key' => 'licensing.step_approved', 'sort_order' => 10],
            ['type' => 'rank_advancement', 'code' => 'rank_advanced', 'name' => 'Rank Advanced', 'event_key' => 'rank.advanced', 'sort_order' => 10],
            ['type' => 'announcement', 'code' => 'announcement_published', 'name' => 'Announcement Published', 'event_key' => 'announcement.published', 'sort_order' => 10],

            // FNA
            ['type' => 'fna', 'code' => 'fna_submitted', 'name' => 'FNA Submitted', 'event_key' => 'fna.submitted', 'sort_order' => 10],
            ['type' => 'fna', 'code' => 'fna_approved', 'name' => 'FNA Approved', 'event_key' => 'fna.approved', 'sort_order' => 20],
            ['type' => 'fna', 'code' => 'fna_revision_requested', 'name' => 'FNA Revision Requested', 'event_key' => 'fna.revision_requested', 'sort_order' => 30],
            ['type' => 'fna', 'code' => 'fna_client_portal_submitted', 'name' => 'Client FNA Submitted', 'event_key' => 'fna.client_portal_submitted', 'sort_order' => 40],

            // Support
            ['type' => 'support_ticket', 'code' => 'support_ticket_created', 'name' => 'Support Ticket Created', 'event_key' => 'support.ticket_created', 'sort_order' => 10],
            ['type' => 'support_ticket', 'code' => 'support_ticket_status_changed', 'name' => 'Support Ticket Status Changed', 'event_key' => 'support.ticket_status_changed', 'sort_order' => 20],
            ['type' => 'support_ticket', 'code' => 'support_ticket_agent_reply', 'name' => 'Support Agent Reply', 'event_key' => 'support.agent_reply', 'sort_order' => 30],
            ['type' => 'support_ticket', 'code' => 'support_ticket_urgent', 'name' => 'Urgent Support Ticket', 'event_key' => 'support.ticket_urgent', 'sort_order' => 40],

            // Goals
            ['type' => 'goal', 'code' => 'goal_reminder', 'name' => 'Goal Reminder', 'event_key' => 'goal.reminder', 'sort_order' => 10],
            ['type' => 'goal', 'code' => 'goal_off_track', 'name' => 'Goal Off Track', 'event_key' => 'goal.off_track', 'sort_order' => 20],
            ['type' => 'goal', 'code' => 'goal_achievement', 'name' => 'Goal Achievement', 'event_key' => 'goal.achievement', 'sort_order' => 30],

            // Registration & CFM assignment
            ['type' => 'registration', 'code' => 'assign_cfm_reminder', 'name' => 'Assign CFM Reminder', 'event_key' => 'registration.assign_cfm', 'sort_order' => 10],
            ['type' => 'registration', 'code' => 'recommend_cfm_reminder', 'name' => 'Recommend CFM Reminder', 'event_key' => 'registration.recommend_cfm', 'sort_order' => 20],
            ['type' => 'cfm_assignment', 'code' => 'cfm_assignment_pending_confirm', 'name' => 'CFM Assignment Pending', 'event_key' => 'cfm_assignment.pending_confirm', 'sort_order' => 10],
            ['type' => 'cfm_assignment', 'code' => 'cfm_assignment_confirmed', 'name' => 'CFM Assignment Confirmed', 'event_key' => 'cfm_assignment.confirmed', 'sort_order' => 20],

            // Checklists
            ['type' => 'onboarding', 'code' => 'checklist_item_submitted', 'name' => 'Checklist Item Submitted', 'event_key' => 'checklist.item_submitted', 'sort_order' => 10],
            ['type' => 'onboarding', 'code' => 'checklist_item_approved', 'name' => 'Checklist Item Approved', 'event_key' => 'checklist.item_approved', 'sort_order' => 20],
            ['type' => 'onboarding', 'code' => 'checklist_item_rejected', 'name' => 'Checklist Item Rejected', 'event_key' => 'checklist.item_rejected', 'sort_order' => 30],
            ['type' => 'onboarding', 'code' => 'checklist_type_started', 'name' => 'Checklist Type Started', 'event_key' => 'checklist.type_started', 'sort_order' => 40],

            // Tasks
            ['type' => 'task', 'code' => 'task_assigned', 'name' => 'Task Assigned', 'event_key' => 'task.assigned', 'sort_order' => 10],
            ['type' => 'task', 'code' => 'task_completed', 'name' => 'Task Completed', 'event_key' => 'task.completed', 'sort_order' => 20],

            // Messaging
            ['type' => 'message', 'code' => 'message_received', 'name' => 'Message Received', 'event_key' => 'message.received', 'sort_order' => 10],

            // Prospects
            ['type' => 'prospect', 'code' => 'prospect_conversion', 'name' => 'Prospect Conversion', 'event_key' => 'prospect.conversion', 'sort_order' => 10],
            ['type' => 'prospect', 'code' => 'prospect_stage_changed', 'name' => 'Prospect Stage Changed', 'event_key' => 'prospect.stage_changed', 'sort_order' => 20],

            // Calendar
            ['type' => 'calendar', 'code' => 'calendar_event_invited', 'name' => 'Calendar Event Invitation', 'event_key' => 'calendar.event_invited', 'sort_order' => 10],

            // CFM portal
            ['type' => 'mentoring', 'code' => 'cfm_portal_notification', 'name' => 'CFM Portal Notification', 'event_key' => 'cfm_portal.notification', 'sort_order' => 10],

            // Escalation & accountability (Phase 4)
            ['type' => 'escalation', 'code' => 'trainee_inactivity_cfm', 'name' => 'Trainee Inactivity (CFM)', 'event_key' => 'escalation.trainee_inactivity_cfm', 'sort_order' => 10],
            ['type' => 'escalation', 'code' => 'trainee_inactivity_leadership', 'name' => 'Trainee Inactivity (Leadership)', 'event_key' => 'escalation.trainee_inactivity_leadership', 'sort_order' => 20],
            ['type' => 'risk_alert', 'code' => 'trainee_inactivity_risk', 'name' => 'Trainee Inactivity Risk Alert', 'event_key' => 'escalation.trainee_inactivity_risk', 'sort_order' => 10],
            ['type' => 'prospect', 'code' => 'prospect_follow_up_overdue', 'name' => 'Prospect Follow-Up Overdue', 'event_key' => 'prospect.follow_up_overdue', 'sort_order' => 30],
            ['type' => 'task', 'code' => 'task_overdue', 'name' => 'Task Overdue', 'event_key' => 'task.overdue', 'sort_order' => 30],
            ['type' => 'calendar', 'code' => 'calendar_event_reminder', 'name' => 'Calendar Event Reminder', 'event_key' => 'calendar.event_reminder', 'sort_order' => 20],
        ];

        foreach ($triggers as $trigger) {
            DB::table('notification_triggers')->updateOrInsert(
                ['code' => $trigger['code']],
                [
                    'notification_type_id' => $typeIds[$trigger['type']],
                    'name' => $trigger['name'],
                    'description' => $trigger['name'],
                    'event_key' => $trigger['event_key'],
                    'sort_order' => $trigger['sort_order'],
                    'is_active' => true,
                    'deleted_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedTemplates(): void
    {
        $triggerIds = DB::table('notification_triggers')->pluck('id', 'code');

        $templates = [
            // Legacy templates
            ['trigger' => 'training_assigned', 'name' => 'Default Training Assigned', 'subject' => 'New training assigned', 'body' => '{{ member_name }}, a new training module is ready: {{ module_title }}.', 'channels' => ['in_app'], 'placeholders' => ['member_name', 'module_title']],
            ['trigger' => 'training_completed', 'name' => 'Default Training Completed', 'subject' => 'Training completed', 'body' => '{{ member_name }} completed {{ module_title }}.', 'channels' => ['in_app'], 'placeholders' => ['member_name', 'module_title']],
            ['trigger' => 'assessment_result', 'name' => 'Assessment Result', 'subject' => 'Assessment result: {{ result }}', 'body' => '{{ member_name }}, you scored {{ score }}% on {{ assessment_title }}. Result: {{ result }}.', 'channels' => ['in_app'], 'placeholders' => ['member_name', 'score', 'assessment_title', 'result']],
            ['trigger' => 'mentor_assigned', 'name' => 'Default Mentor Assigned', 'subject' => 'Mentor assigned', 'body' => '{{ member_name }}, {{ mentor_name }} is now your Certified Field Mentor.', 'channels' => ['in_app', 'email'], 'placeholders' => ['member_name', 'mentor_name']],
            ['trigger' => 'mentor_assignment_declined', 'name' => 'Mentor Assignment Declined', 'subject' => 'Mentor assignment declined', 'body' => '{{ mentor_name }} declined the mentorship assignment for {{ member_name }}. Please choose another mentor.', 'channels' => ['in_app'], 'placeholders' => ['mentor_name', 'member_name']],
            ['trigger' => 'apprenticeship_completed', 'name' => 'Apprenticeship Completed', 'subject' => 'Field Apprenticeship completed', 'body' => '{{ member_name }} completed the Field Apprenticeship mentorship with {{ mentor_name }}.', 'channels' => ['in_app'], 'placeholders' => ['member_name', 'mentor_name']],
            ['trigger' => 'apprenticeship_ended', 'name' => 'Mentorship Assignment Ended', 'subject' => 'Mentorship assignment updated', 'body' => 'The mentorship assignment between {{ member_name }} and {{ mentor_name }} was ended by {{ actor_name }}.', 'channels' => ['in_app'], 'placeholders' => ['member_name', 'mentor_name', 'actor_name']],
            ['trigger' => 'cfm_certification_submitted', 'name' => 'CFM Certification Submitted', 'subject' => 'CFM certification request received', 'body' => '{{ member_name }}, your CFM certification request has been submitted for review.', 'channels' => ['in_app'], 'placeholders' => ['member_name']],
            ['trigger' => 'cfm_certification_decision', 'name' => 'CFM Certification Decision', 'subject' => 'CFM certification request {{ decision }}', 'body' => 'Your CFM certification request was {{ decision }}. {{ review_notes }}', 'channels' => ['in_app', 'email'], 'placeholders' => ['decision', 'review_notes']],
            ['trigger' => 'booking_confirmed', 'name' => 'Default Booking Confirmed', 'subject' => 'Mentor session confirmed', 'body' => 'Your session with {{ mentor_name }} is confirmed for {{ session_time }}.', 'channels' => ['in_app'], 'placeholders' => ['mentor_name', 'session_time']],
            ['trigger' => 'booking_request_received', 'name' => 'Default Booking Request Received', 'subject' => 'New mentor booking request', 'body' => '{{ trainee_name }} requested {{ event_title }} for {{ session_time }}.', 'channels' => ['in_app'], 'placeholders' => ['trainee_name', 'event_title', 'session_time']],
            ['trigger' => 'booking_declined', 'name' => 'Default Booking Declined', 'subject' => 'Mentor booking declined', 'body' => '{{ event_title }} for {{ session_time }} was declined by {{ mentor_name }}.', 'channels' => ['in_app'], 'placeholders' => ['event_title', 'session_time', 'mentor_name']],
            ['trigger' => 'booking_cancelled', 'name' => 'Default Booking Cancelled', 'subject' => 'Mentor booking cancelled', 'body' => '{{ event_title }} for {{ session_time }} was cancelled by {{ actor_name }}.', 'channels' => ['in_app'], 'placeholders' => ['event_title', 'session_time', 'actor_name']],
            ['trigger' => 'booking_reschedule_requested', 'name' => 'Default Booking Reschedule Requested', 'subject' => 'Mentor session reschedule requested', 'body' => '{{ participant_name }} requested a new time for {{ event_title }}: {{ session_time }}.', 'channels' => ['in_app'], 'placeholders' => ['participant_name', 'event_title', 'session_time']],
            ['trigger' => 'booking_reschedule_decision', 'name' => 'Default Booking Reschedule Decision', 'subject' => 'Mentor session reschedule update', 'body' => 'Your reschedule request for {{ event_title }} was {{ decision }}.', 'channels' => ['in_app'], 'placeholders' => ['event_title', 'decision']],
            ['trigger' => 'licensing_step_approved', 'name' => 'Default Licensing Step Approved', 'subject' => 'Licensing milestone approved', 'body' => '{{ step_title }} was approved for {{ member_name }}.', 'channels' => ['in_app'], 'placeholders' => ['step_title', 'member_name']],
            ['trigger' => 'rank_advanced', 'name' => 'Default Rank Advanced', 'subject' => 'Rank advancement', 'body' => 'Congratulations {{ member_name }} on advancing to {{ rank_name }}.', 'channels' => ['in_app'], 'placeholders' => ['member_name', 'rank_name']],
            ['trigger' => 'announcement_published', 'name' => 'Default Announcement Published', 'subject' => 'New announcement', 'body' => '{{ announcement_title }} is now available in the portal.', 'channels' => ['in_app'], 'placeholders' => ['announcement_title']],

            // FNA
            ['trigger' => 'fna_submitted', 'name' => 'FNA Submitted', 'subject' => 'FNA submitted for review', 'body' => '{{ submitted_by }} submitted an FNA for {{ client_name }} ({{ reference_code }}).', 'channels' => ['in_app'], 'placeholders' => ['submitted_by', 'client_name', 'reference_code']],
            ['trigger' => 'fna_approved', 'name' => 'FNA Approved', 'subject' => 'FNA approved by CFM', 'body' => '{{ reviewed_by }} approved your FNA for {{ client_name }}. You may schedule a client review meeting.', 'channels' => ['in_app'], 'placeholders' => ['reviewed_by', 'client_name']],
            ['trigger' => 'fna_revision_requested', 'name' => 'FNA Revision Requested', 'subject' => 'FNA revision requested', 'body' => '{{ reviewed_by }} requested revisions on your FNA for {{ client_name }}.', 'channels' => ['in_app'], 'placeholders' => ['reviewed_by', 'client_name']],
            ['trigger' => 'fna_client_portal_submitted', 'name' => 'Client FNA Submitted', 'subject' => 'Client FNA submitted', 'body' => '{{ client_name }} completed their FNA via the client portal.', 'channels' => ['in_app'], 'placeholders' => ['client_name']],

            // Support
            ['trigger' => 'support_ticket_created', 'name' => 'Support Ticket Created', 'subject' => 'Support ticket received', 'body' => 'Ticket {{ ticket_number }} was submitted successfully.', 'channels' => ['in_app', 'email'], 'placeholders' => ['ticket_number']],
            ['trigger' => 'support_ticket_status_changed', 'name' => 'Support Ticket Status Changed', 'subject' => 'Ticket status updated', 'body' => 'Ticket {{ ticket_number }} is now {{ status_name }}.', 'channels' => ['in_app', 'email'], 'placeholders' => ['ticket_number', 'status_name']],
            ['trigger' => 'support_ticket_agent_reply', 'name' => 'Support Agent Reply', 'subject' => 'Support team replied', 'body' => 'New reply on ticket {{ ticket_number }}.', 'channels' => ['in_app', 'email'], 'placeholders' => ['ticket_number']],
            ['trigger' => 'support_ticket_urgent', 'name' => 'Urgent Support Ticket', 'subject' => 'Urgent support ticket', 'body' => 'Urgent ticket {{ ticket_number }} was submitted by {{ submitter_name }}.', 'channels' => ['in_app', 'email'], 'placeholders' => ['ticket_number', 'submitter_name']],

            // Goals
            ['trigger' => 'goal_reminder', 'name' => 'Goal Reminder', 'subject' => 'Goal reminder', 'body' => '{{ message }}', 'channels' => ['in_app'], 'placeholders' => ['message']],
            ['trigger' => 'goal_off_track', 'name' => 'Goal Off Track', 'subject' => 'Goal off track', 'body' => 'Your goal "{{ goal_name }}" is behind schedule ({{ progress }}% complete).', 'channels' => ['in_app'], 'placeholders' => ['goal_name', 'progress']],
            ['trigger' => 'goal_achievement', 'name' => 'Goal Achievement', 'subject' => 'Achievement unlocked', 'body' => 'Achievement unlocked: {{ badge_name }}. {{ badge_description }}', 'channels' => ['in_app'], 'placeholders' => ['badge_name', 'badge_description']],

            // Registration & CFM assignment
            ['trigger' => 'assign_cfm_reminder', 'name' => 'Assign CFM Reminder', 'subject' => 'Assign a CFM for {{ member_name }}', 'body' => '{{ member_name }} just registered. Assign a Certified Field Mentor so onboarding can begin.', 'channels' => ['in_app'], 'placeholders' => ['member_name']],
            ['trigger' => 'recommend_cfm_reminder', 'name' => 'Recommend CFM Reminder', 'subject' => 'Recommend a CFM for {{ member_name }}', 'body' => '{{ member_name }} just registered under your sponsorship. You can recommend a Certified Field Mentor and remind {{ agency_owner_name }} to assign one.', 'channels' => ['in_app'], 'placeholders' => ['member_name', 'agency_owner_name']],
            ['trigger' => 'cfm_assignment_pending_confirm', 'name' => 'CFM Assignment Pending', 'subject' => 'Confirm trainee assignment', 'body' => '{{ member_name }} was assigned to you as a trainee. Please confirm the assignment to begin mentoring.', 'channels' => ['in_app', 'email'], 'placeholders' => ['member_name', 'cfm_name']],
            ['trigger' => 'cfm_assignment_confirmed', 'name' => 'CFM Assignment Confirmed', 'subject' => 'CFM assignment confirmed', 'body' => '{{ cfm_name }} is now the Certified Field Mentor for {{ member_name }}.', 'channels' => ['in_app'], 'placeholders' => ['member_name', 'cfm_name']],

            // Checklists
            ['trigger' => 'checklist_item_submitted', 'name' => 'Checklist Item Submitted', 'subject' => 'Checklist item awaiting review', 'body' => '{{ member_name }} submitted "{{ step_title }}" for your review.', 'channels' => ['in_app'], 'placeholders' => ['member_name', 'step_title', 'checklist_type']],
            ['trigger' => 'checklist_item_approved', 'name' => 'Checklist Item Approved', 'subject' => 'Checklist item approved', 'body' => '{{ step_title }} was approved by {{ reviewer_name }}.', 'channels' => ['in_app'], 'placeholders' => ['step_title', 'reviewer_name', 'checklist_type']],
            ['trigger' => 'checklist_item_rejected', 'name' => 'Checklist Item Rejected', 'subject' => 'Checklist item needs revision', 'body' => '{{ step_title }} was returned by {{ reviewer_name }}. Please review the feedback and resubmit.', 'channels' => ['in_app'], 'placeholders' => ['step_title', 'reviewer_name', 'checklist_type']],
            ['trigger' => 'checklist_type_started', 'name' => 'Checklist Type Started', 'subject' => 'Checklist started', 'body' => '{{ checklist_type }} checklist was started for {{ member_name }}.', 'channels' => ['in_app'], 'placeholders' => ['member_name', 'checklist_type']],

            // Tasks
            ['trigger' => 'task_assigned', 'name' => 'Task Assigned', 'subject' => 'New task assigned', 'body' => '{{ task_name }} was assigned to you by {{ cfm_name }}.', 'channels' => ['in_app'], 'placeholders' => ['task_name', 'cfm_name', 'deadline']],
            ['trigger' => 'task_completed', 'name' => 'Task Completed', 'subject' => 'Task completed', 'body' => '{{ trainee_name }} completed the task "{{ task_name }}".', 'channels' => ['in_app'], 'placeholders' => ['task_name', 'trainee_name']],

            // Messaging
            ['trigger' => 'message_received', 'name' => 'Message Received', 'subject' => 'New message from {{ sender_name }}', 'body' => '{{ sender_name }}: {{ message_preview }}', 'channels' => ['in_app'], 'placeholders' => ['sender_name', 'message_preview']],

            // Prospects
            ['trigger' => 'prospect_conversion', 'name' => 'Prospect Conversion', 'subject' => 'Prospect conversion update', 'body' => '{{ message }}', 'channels' => ['in_app'], 'placeholders' => ['message', 'prospect_name']],
            ['trigger' => 'prospect_stage_changed', 'name' => 'Prospect Stage Changed', 'subject' => 'Prospect moved to {{ stage_name }}', 'body' => '{{ prospect_name }} moved to {{ stage_name }} in your pipeline.', 'channels' => ['in_app'], 'placeholders' => ['prospect_name', 'stage_name']],

            // Calendar
            ['trigger' => 'calendar_event_invited', 'name' => 'Calendar Event Invitation', 'subject' => 'Event invitation: {{ event_title }}', 'body' => '{{ organizer_name }} invited you to "{{ event_title }}" on {{ session_time }}.', 'channels' => ['in_app'], 'placeholders' => ['event_title', 'organizer_name', 'session_time']],

            // CFM portal
            ['trigger' => 'cfm_portal_notification', 'name' => 'CFM Portal Notification', 'subject' => '{{ title }}', 'body' => '{{ message }}', 'channels' => ['in_app'], 'placeholders' => ['title', 'message', 'cfm_name']],

            // Escalation & accountability
            ['trigger' => 'trainee_inactivity_cfm', 'name' => 'Trainee Inactivity CFM', 'subject' => 'Trainee inactive {{ inactive_days }} days', 'body' => '{{ trainee_name }} has been inactive for {{ inactive_days }} days. Please reach out.', 'channels' => ['in_app', 'email'], 'placeholders' => ['trainee_name', 'inactive_days']],
            ['trigger' => 'trainee_inactivity_leadership', 'name' => 'Trainee Inactivity Leadership', 'subject' => 'Escalation: {{ trainee_name }} inactive', 'body' => '{{ trainee_name }} has been inactive for {{ inactive_days }} days. Leadership follow-up recommended.', 'channels' => ['in_app', 'email'], 'placeholders' => ['trainee_name', 'inactive_days']],
            ['trigger' => 'trainee_inactivity_risk', 'name' => 'Trainee Inactivity Risk', 'subject' => 'Risk alert: {{ trainee_name }}', 'body' => '{{ trainee_name }} has been inactive for {{ inactive_days }} days. Immediate attention required.', 'channels' => ['in_app', 'email'], 'placeholders' => ['trainee_name', 'inactive_days']],
            ['trigger' => 'prospect_follow_up_overdue', 'name' => 'Prospect Follow-Up Overdue', 'subject' => 'Follow-up overdue: {{ prospect_name }}', 'body' => '{{ message }}', 'channels' => ['in_app'], 'placeholders' => ['prospect_name', 'message']],
            ['trigger' => 'task_overdue', 'name' => 'Task Overdue', 'subject' => 'Task overdue: {{ task_name }}', 'body' => 'The task "{{ task_name }}" is past its due date ({{ deadline }}).', 'channels' => ['in_app', 'email'], 'placeholders' => ['task_name', 'deadline']],
            ['trigger' => 'calendar_event_reminder', 'name' => 'Calendar Event Reminder', 'subject' => 'Reminder: {{ event_title }}', 'body' => '"{{ event_title }}" starts at {{ session_time }}.', 'channels' => ['in_app'], 'placeholders' => ['event_title', 'session_time']],
        ];

        foreach ($templates as $template) {
            DB::table('notification_templates')->updateOrInsert(
                [
                    'notification_trigger_id' => $triggerIds[$template['trigger']],
                    'name' => $template['name'],
                ],
                [
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'in_app_title' => $template['subject'],
                    'in_app_message' => $template['body'],
                    'channels' => json_encode($template['channels']),
                    'placeholders' => json_encode($template['placeholders']),
                    'action_label' => 'View',
                    'is_default' => true,
                    'is_active' => true,
                    'deleted_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedPreferenceDefaults(): void
    {
        if (! Schema::hasTable('notification_preference_defaults')) {
            return;
        }

        $typeIds = DB::table('notification_types')->pluck('id', 'code');
        $channelIds = DB::table('notification_channels')->pluck('id', 'code');

        $roles = ['member', 'certified-field-mentor', 'agency-owner', 'admin', 'super-admin'];
        $types = [
            'registration', 'onboarding', 'cfm_assignment', 'fap', 'licensing', 'training',
            'task', 'prospect', 'calendar', 'booking', 'goal', 'fna', 'support_ticket',
            'announcement', 'message', 'mentoring',
        ];

        foreach ($roles as $role) {
            foreach ($types as $typeCode) {
                if (! isset($typeIds[$typeCode])) {
                    continue;
                }

                foreach (['in_app', 'email', 'push', 'sms'] as $channelCode) {
                    if (! isset($channelIds[$channelCode])) {
                        continue;
                    }

                    $enabled = match ($channelCode) {
                        'sms' => in_array($typeCode, ['calendar', 'task', 'cfm_assignment', 'booking', 'support_ticket'], true),
                        'push' => true,
                        default => true,
                    };

                    DB::table('notification_preference_defaults')->updateOrInsert(
                        [
                            'role' => $role,
                            'notification_type_id' => $typeIds[$typeCode],
                            'notification_channel_id' => $channelIds[$channelCode],
                        ],
                        [
                            'enabled' => $enabled,
                            'frequency' => 'immediate',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    );
                }
            }
        }
    }
}
