<?php

namespace Database\Seeders;

use App\Models\NotificationDeviceToken;
use App\Models\NotificationDigestSetting;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sample in-app notifications, delivery logs, and device tokens for local testing.
 *
 * Run after config seeders:
 *   php artisan db:seed --class=NotificationsSeeder
 *
 * Primary inbox: super-admin@gwtracks.com (or first available user)
 */
class NotificationsSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::table('notification_triggers')->exists()) {
            $this->command?->info('Seeding notification config first…');
            $this->call(NotificationConfigSeeder::class);
        }

        $primary = $this->userByEmail('super-admin@gwtracks.com')
            ?? $this->userByEmail('member@gwtracks.com')
            ?? User::query()->whereNull('deleted_at')->orderBy('id')->first();

        if (! $primary) {
            $this->command?->warn('NotificationsSeeder skipped: no users available.');

            return;
        }

        $cfm = $this->userByEmail('cfm@gwtracks.com') ?? $primary;
        $admin = $this->userByEmail('super-admin@gwtracks.com') ?? $primary;

        $this->command?->info("Seeding notifications for {$primary->email}…");

        $this->seedInboxForUser($primary, $cfm, [
            [
                'trigger' => 'training_assigned',
                'tokens' => ['member_name' => $primary->name, 'module_title' => 'Product Knowledge Foundations'],
                'action' => ['label' => 'Open training', 'url' => '/training'],
                'priority' => 'medium',
                'module' => 'training',
                'read_at' => null,
                'hours_ago' => 1,
            ],
            [
                'trigger' => 'mentor_assigned',
                'tokens' => ['member_name' => $primary->name, 'mentor_name' => $cfm->name],
                'action' => ['label' => 'View CFM portal', 'url' => '/cfm/portal'],
                'priority' => 'high',
                'module' => 'onboarding',
                'sender_type' => 'user',
                'sender_user_id' => $cfm->id,
                'read_at' => null,
                'hours_ago' => 3,
            ],
            [
                'trigger' => 'task_assigned',
                'tokens' => ['task_name' => 'Complete onboarding profile', 'cfm_name' => $cfm->name, 'deadline' => 'Friday'],
                'action' => ['label' => 'View tasks', 'url' => '/tasks'],
                'priority' => 'medium',
                'module' => 'task',
                'read_at' => null,
                'hours_ago' => 5,
            ],
            [
                'trigger' => 'goal_reminder',
                'tokens' => ['member_name' => $primary->name],
                'action' => ['label' => 'Review goals', 'url' => '/goals'],
                'priority' => 'medium',
                'module' => 'goal',
                'read_at' => null,
                'hours_ago' => 8,
            ],
            [
                'trigger' => 'message_received',
                'tokens' => ['sender_name' => $cfm->name, 'message_preview' => 'Great progress this week — let us review your FAP checklist.'],
                'action' => ['label' => 'Open messages', 'url' => '/messages'],
                'priority' => 'low',
                'module' => 'message',
                'read_at' => null,
                'hours_ago' => 12,
            ],
            [
                'trigger' => 'calendar_event_reminder',
                'tokens' => ['event_title' => 'CFM coaching session', 'session_time' => 'Tomorrow at 10:00 AM'],
                'action' => ['label' => 'View calendar', 'url' => '/calendar'],
                'priority' => 'high',
                'module' => 'calendar',
                'read_at' => null,
                'hours_ago' => 18,
            ],
            [
                'trigger' => 'announcement_published',
                'tokens' => ['announcement_title' => 'Portal maintenance this weekend'],
                'action' => ['label' => 'View dashboard', 'url' => '/dashboard'],
                'priority' => 'critical',
                'module' => 'system',
                'read_at' => null,
                'hours_ago' => 2,
            ],
            [
                'trigger' => 'prospect_follow_up_overdue',
                'tokens' => ['prospect_name' => 'Jordan Ellis', 'message' => 'Follow-up with Jordan Ellis is 2 days overdue.'],
                'action' => ['label' => 'Open prospects', 'url' => '/prospects'],
                'priority' => 'urgent',
                'module' => 'prospect',
                'read_at' => null,
                'hours_ago' => 6,
            ],
            [
                'trigger' => 'licensing_step_approved',
                'tokens' => ['member_name' => $primary->name, 'step_title' => 'Pass the provincial exam'],
                'action' => ['label' => 'Licensing checklist', 'url' => '/tracker/licensing'],
                'priority' => 'medium',
                'module' => 'licensing',
                'read_at' => now()->subDay(),
                'hours_ago' => 30,
            ],
            [
                'trigger' => 'booking_confirmed',
                'tokens' => ['member_name' => $primary->name, 'booking_title' => 'Intro call with CFM'],
                'action' => ['label' => 'View booking', 'url' => '/bookings'],
                'priority' => 'info',
                'module' => 'booking',
                'read_at' => now()->subHours(6),
                'hours_ago' => 48,
            ],
            [
                'trigger' => 'goal_off_track',
                'tokens' => ['member_name' => $primary->name],
                'action' => ['label' => 'Adjust goals', 'url' => '/goals'],
                'priority' => 'high',
                'module' => 'goal',
                'read_at' => now()->subDays(2),
                'archived_at' => now()->subDay(),
                'hours_ago' => 72,
            ],
            [
                'trigger' => 'rank_advanced',
                'tokens' => ['member_name' => $primary->name, 'rank_name' => 'Associate'],
                'action' => ['label' => 'View profile', 'url' => '/profile'],
                'priority' => 'medium',
                'module' => 'rank_advancement',
                'read_at' => now()->subDays(3),
                'hours_ago' => 96,
            ],
        ]);

        if ($cfm->id !== $primary->id) {
            $this->seedInboxForUser($cfm, $primary, [
                [
                    'trigger' => 'fna_submitted',
                    'tokens' => ['trainee_name' => $primary->name, 'member_name' => $primary->name],
                    'action' => ['label' => 'Review FNA', 'url' => '/team/fna'],
                    'priority' => 'medium',
                    'module' => 'fna',
                    'read_at' => null,
                    'hours_ago' => 4,
                    'related_user_id' => $primary->id,
                ],
                [
                    'trigger' => 'trainee_inactivity_cfm',
                    'tokens' => ['trainee_name' => $primary->name, 'inactive_days' => '7'],
                    'action' => ['label' => 'Open CFM portal', 'url' => '/cfm/portal'],
                    'priority' => 'high',
                    'module' => 'onboarding',
                    'read_at' => null,
                    'hours_ago' => 10,
                    'related_user_id' => $primary->id,
                ],
                [
                    'trigger' => 'task_overdue',
                    'tokens' => ['task_name' => 'Submit licensing documents', 'deadline' => 'Yesterday'],
                    'action' => ['label' => 'View trainee', 'url' => '/cfm/portal'],
                    'priority' => 'urgent',
                    'module' => 'task',
                    'read_at' => null,
                    'hours_ago' => 20,
                ],
            ]);
        }

        if ($admin->id !== $primary->id) {
            $this->seedInboxForUser($admin, $primary, [
                [
                    'trigger' => 'support_ticket_urgent',
                    'tokens' => ['ticket_number' => 'SUP-1042', 'subject' => 'Unable to upload licensing document'],
                    'action' => ['label' => 'Support queue', 'url' => '/admin/support'],
                    'priority' => 'critical',
                    'module' => 'support',
                    'read_at' => null,
                    'hours_ago' => 1,
                ],
            ]);
        }

        $this->seedDeliveryLogs($primary, $cfm);
        $this->seedDeviceToken($primary);
        $this->seedDigestSetting($primary);

        $total = DB::table('notifications')->count();
        $unread = DB::table('notifications')->whereNull('read_at')->whereNull('archived_at')->count();

        $this->command?->info("NotificationsSeeder complete: {$total} notifications ({$unread} unread).");
        $this->command?->line('  Login as '.$primary->email.' and open /notifications');
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function seedInboxForUser(User $recipient, User $contextUser, array $items): void
    {
        foreach ($items as $item) {
            $this->seedNotification($recipient, $item, $contextUser);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function seedNotification(User $recipient, array $item, User $contextUser): void
    {
        $triggerCode = $item['trigger'];
        $trigger = DB::table('notification_triggers')->where('code', $triggerCode)->first();

        if (! $trigger) {
            $this->command?->warn("Skipping unknown trigger [{$triggerCode}].");

            return;
        }

        $template = NotificationTemplate::query()
            ->where('notification_trigger_id', $trigger->id)
            ->where('is_default', true)
            ->first();

        $tokens = $item['tokens'] ?? ['member_name' => $recipient->name];
        $subject = $template?->renderSubject($tokens) ?? Str::headline(str_replace('_', ' ', $triggerCode));
        $body = $template?->renderBody($tokens) ?? 'Sample notification for testing.';
        $typeName = DB::table('notification_types')->where('id', $trigger->notification_type_id)->value('name');
        $hoursAgo = (int) ($item['hours_ago'] ?? 2);
        $createdAt = now()->subHours($hoursAgo);

        $data = [
            'trigger' => $triggerCode,
            'title' => $subject,
            'message' => $body,
            'category' => $typeName,
            'priority' => $item['priority'] ?? 'medium',
        ];

        $payload = [
            'notification_type_id' => $trigger->notification_type_id,
            'trigger_id' => $trigger->id,
            'sender_type' => $item['sender_type'] ?? 'system',
            'sender_user_id' => $item['sender_user_id'] ?? null,
            'recipients' => json_encode(['user_ids' => [$recipient->id]]),
            'notification_template' => json_encode($template?->snapshot()),
            'action_link' => json_encode($item['action'] ?? ['label' => 'View', 'url' => '/dashboard']),
            'priority' => $item['priority'] ?? 'medium',
            'module' => $item['module'] ?? null,
            'related_type' => isset($item['related_user_id']) ? User::class : null,
            'related_id' => $item['related_user_id'] ?? null,
            'related_user_id' => $item['related_user_id'] ?? null,
            'metadata' => json_encode([
                'seeded' => true,
                'ai_summary' => Str::limit("{$subject}: {$body}", 200),
                'suggested_actions' => ['Open related record', 'Mark as read'],
            ]),
            'type' => 'database',
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
            'data' => json_encode($data),
            'read_at' => $item['read_at'] ?? null,
            'archived_at' => $item['archived_at'] ?? null,
            'snoozed_until' => $item['snoozed_until'] ?? null,
            'deleted_at' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];

        $existing = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $recipient->id)
            ->where('trigger_id', $trigger->id)
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            DB::table('notifications')->where('id', $existing->id)->update($payload);

            return;
        }

        DB::table('notifications')->insert(array_merge($payload, [
            'id' => (string) Str::uuid(),
        ]));
    }

    private function seedDeliveryLogs(User $primary, User $cfm): void
    {
        $samples = [
            ['user_id' => $primary->id, 'trigger_code' => 'training_assigned', 'channel' => 'in_app', 'status' => 'sent', 'hours_ago' => 1],
            ['user_id' => $primary->id, 'trigger_code' => 'training_assigned', 'channel' => 'email', 'status' => 'sent', 'hours_ago' => 1],
            ['user_id' => $primary->id, 'trigger_code' => 'mentor_assigned', 'channel' => 'email', 'status' => 'failed', 'hours_ago' => 3, 'failure_reason' => 'SMTP connection timed out'],
            ['user_id' => $primary->id, 'trigger_code' => 'announcement_published', 'channel' => 'in_app', 'status' => 'sent', 'hours_ago' => 2],
            ['user_id' => $cfm->id, 'trigger_code' => 'fna_submitted', 'channel' => 'in_app', 'status' => 'sent', 'hours_ago' => 4],
            ['user_id' => $primary->id, 'trigger_code' => 'goal_reminder', 'channel' => 'email', 'status' => 'suppressed', 'hours_ago' => 8, 'failure_reason' => 'User preference disabled email'],
            ['user_id' => $primary->id, 'trigger_code' => 'calendar_event_reminder', 'channel' => 'push', 'status' => 'skipped', 'hours_ago' => 18, 'failure_reason' => 'Push channel disabled'],
        ];

        foreach ($samples as $sample) {
            $attemptedAt = now()->subHours($sample['hours_ago']);

            DB::table('notification_delivery_logs')->updateOrInsert(
                [
                    'user_id' => $sample['user_id'],
                    'trigger_code' => $sample['trigger_code'],
                    'channel' => $sample['channel'],
                    'status' => $sample['status'],
                    'attempted_at' => $attemptedAt,
                ],
                [
                    'notification_id' => null,
                    'failure_reason' => $sample['failure_reason'] ?? null,
                    'delivered_at' => in_array($sample['status'], ['sent'], true) ? $attemptedAt : null,
                    'created_at' => $attemptedAt,
                    'updated_at' => $attemptedAt,
                ],
            );
        }
    }

    private function seedDeviceToken(User $user): void
    {
        NotificationDeviceToken::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'token' => 'demo-web-push-token-'.Str::slug($user->email),
            ],
            [
                'platform' => 'web',
                'device_name' => 'Demo browser',
                'is_active' => true,
                'last_used_at' => now(),
            ],
        );
    }

    private function seedDigestSetting(User $user): void
    {
        if (! DB::getSchemaBuilder()->hasTable('notification_digest_settings')) {
            return;
        }

        NotificationDigestSetting::query()->updateOrCreate(
            ['user_id' => $user->id, 'digest_type' => 'daily'],
            [
                'send_at' => '07:00:00',
                'timezone_id' => $user->profile?->timezone_id,
                'enabled' => true,
                'last_sent_at' => now()->subDay(),
            ],
        );
    }

    private function userByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->whereNull('deleted_at')->first();
    }
}
