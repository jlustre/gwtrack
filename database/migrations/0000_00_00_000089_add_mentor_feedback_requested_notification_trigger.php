<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $typeId = DB::table('notification_types')
            ->where('code', 'cfm_assignment')
            ->value('id');

        if (! $typeId) {
            $typeId = DB::table('notification_types')->insertGetId([
                'code' => 'cfm_effectiveness',
                'name' => 'CFM Effectiveness',
                'group' => 'mentorship',
                'icon' => 'star',
                'color' => '#C8A24A',
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $triggerExists = DB::table('notification_triggers')
            ->where('code', 'mentor_feedback_requested')
            ->exists();

        if ($triggerExists) {
            return;
        }

        DB::table('notification_triggers')->insert([
            'notification_type_id' => $typeId,
            'code' => 'mentor_feedback_requested',
            'name' => 'Mentor Feedback Requested',
            'event_key' => 'cfm_effectiveness.feedback_requested',
            'sort_order' => 30,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $triggerId = DB::table('notification_triggers')
            ->where('code', 'mentor_feedback_requested')
            ->value('id');

        DB::table('notification_templates')->insert([
            'notification_trigger_id' => $triggerId,
            'name' => 'Default Mentor Feedback Request',
            'subject' => 'Share feedback about your CFM',
            'body' => '{{ trainee_name }}, please complete your {{ review_label }} for {{ cfm_name }}. Your responses are anonymous and help improve mentorship quality.',
            'channels' => json_encode(['in_app', 'email']),
            'placeholders' => json_encode(['trainee_name', 'cfm_name', 'review_label', 'due_date']),
            'is_default' => true,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $triggerId = DB::table('notification_triggers')
            ->where('code', 'mentor_feedback_requested')
            ->value('id');

        if ($triggerId) {
            DB::table('notification_templates')->where('notification_trigger_id', $triggerId)->delete();
            DB::table('notification_triggers')->where('id', $triggerId)->delete();
        }
    }
};
