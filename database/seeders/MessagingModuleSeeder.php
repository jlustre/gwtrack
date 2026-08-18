<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use Illuminate\Database\Seeder;

class MessagingModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTemplates();

        $member = User::where('email', 'calendar-member@gwtrack.com')->first();
        $cfm = User::where('email', 'calendar-cfm@gwtrack.com')->first();

        if (! $member || ! $cfm) {
            return;
        }

        $member->update(['mentor_id' => $cfm->id]);

        $conversation = app(MessagingService::class)->findOrCreateDirectConversation($cfm, $member);

        if ($conversation->messages()->exists()) {
            return;
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $cfm->id,
            'body' => 'Welcome to GWTrack messaging! Use this thread for mentoring questions, licensing support, and FAP coaching.',
            'message_type' => 'text',
            'created_at' => now()->subHour(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
            'body' => 'Thank you! I will keep my onboarding and licensing updates here.',
            'message_type' => 'text',
        ]);

        $conversation->update(['last_message_at' => now()]);

        $group = Conversation::query()->firstOrCreate(
            ['slug' => 'demo-agency-team-chat'],
            [
                'type' => 'group',
                'name' => 'Agency Team Chat',
                'created_by' => $cfm->id,
                'last_message_at' => now()->subMinutes(30),
            ],
        );

        foreach ([$member, $cfm] as $user) {
            ConversationMember::query()->firstOrCreate(
                ['conversation_id' => $group->id, 'user_id' => $user->id],
                ['member_role' => 'member', 'joined_at' => now()->subDays(2)],
            );
        }

        if (! $group->messages()->exists()) {
            Message::create([
                'conversation_id' => $group->id,
                'user_id' => $cfm->id,
                'body' => 'Team reminder: review this week FAP field activity before Friday.',
                'message_type' => 'text',
            ]);
        }
    }

    private function seedTemplates(): void
    {
        $templates = [
            ['welcome-message', 'Welcome Message', 'welcome', "Welcome to GWTrack! I'm excited to support you through onboarding, licensing, and your Field Apprenticeship journey."],
            ['mentorship-intro', 'Mentorship Introduction', 'mentoring', "Hi! I'm your Certified Field Mentor. Let's schedule our first coaching session and review your onboarding checklist."],
            ['licensing-reminder', 'Licensing Reminder', 'licensing', 'Friendly reminder to complete your next licensing milestone. Let me know if you need study support.'],
            ['fap-reminder', 'FAP Reminder', 'fap', 'Please update your Field Apprenticeship activity log and share any blockers before our next review.'],
            ['follow-up-reminder', 'Follow-Up Reminder', 'general', 'Following up on our last conversation. Do you have any questions or updates to share?'],
        ];

        foreach ($templates as [$slug, $title, $category, $body]) {
            MessageTemplate::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'category' => $category,
                    'body' => $body,
                    'is_system' => true,
                    'is_active' => true,
                ],
            );
        }
    }
}
