<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationEscalationSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'code' => 'trainee_inactivity',
                'name' => 'Trainee Inactivity Escalation',
                'module' => 'mentorship',
                'condition_type' => 'trainee_inactivity_days',
                'condition_config' => json_encode(['source' => 'last_login_at']),
                'escalation_steps' => json_encode([
                    [
                        'after_days' => 7,
                        'notify' => ['cfm'],
                        'priority' => 'medium',
                        'trigger_code' => 'trainee_inactivity_cfm',
                    ],
                    [
                        'after_days' => 14,
                        'notify' => ['sponsor', 'agency_owner'],
                        'priority' => 'high',
                        'trigger_code' => 'trainee_inactivity_leadership',
                    ],
                    [
                        'after_days' => 21,
                        'notify' => ['agency_owner'],
                        'priority' => 'critical',
                        'trigger_code' => 'trainee_inactivity_risk',
                        'create_risk_alert' => true,
                    ],
                ]),
                'cooldown_hours' => 24,
                'is_active' => true,
            ],
            [
                'code' => 'prospect_follow_up_overdue',
                'name' => 'Prospect Follow-Up Overdue',
                'module' => 'prospect',
                'condition_type' => 'prospect_follow_up_overdue',
                'condition_config' => null,
                'escalation_steps' => json_encode([
                    [
                        'trigger_code' => 'prospect_follow_up_overdue',
                        'priority' => 'high',
                    ],
                ]),
                'cooldown_hours' => 24,
                'is_active' => true,
            ],
            [
                'code' => 'task_overdue',
                'name' => 'Overdue Task Reminder',
                'module' => 'task',
                'condition_type' => 'task_overdue',
                'condition_config' => null,
                'escalation_steps' => json_encode([
                    [
                        'trigger_code' => 'task_overdue',
                        'priority' => 'high',
                    ],
                ]),
                'cooldown_hours' => 24,
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            DB::table('notification_escalation_rules')->updateOrInsert(
                ['code' => $rule['code']],
                array_merge($rule, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }
    }
}
