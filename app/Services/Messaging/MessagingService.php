<?php

namespace App\Services\Messaging;

use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Message;
use App\Models\MessageDelete;
use App\Models\MessageRead;
use App\Models\MessageReaction;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Models\TaskUser;
use App\Support\TaskUserAttributes;
use App\Services\ChecklistService;
use App\Services\Notifications\NotificationOrchestrator;
use App\Services\Notifications\NotificationRecipientResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MessagingService
{
    public function __construct(
        private readonly MessagingAuthorizationService $authorization,
        private readonly ChecklistService $checklists,
        private readonly NotificationOrchestrator $notifications,
        private readonly NotificationRecipientResolver $notificationRecipients,
    ) {}

    /**
     * @return array<string, int>
     */
    public function dashboardStats(User $user): array
    {
        $membershipQuery = ConversationMember::query()->where('user_id', $user->id)->whereNull('left_at');

        $conversationIds = (clone $membershipQuery)->pluck('conversation_id');

        $unread = Message::query()
            ->whereIn('conversation_id', $conversationIds)
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn (Builder $q) => $q->where('user_id', $user->id))
            ->whereDoesntHave('deletes', fn (Builder $q) => $q->where('user_id', $user->id))
            ->count();

        return [
            'unread' => $unread,
            'active' => (clone $membershipQuery)->where('is_archived', false)->count(),
            'today' => Message::query()
                ->whereIn('conversation_id', $conversationIds)
                ->whereDate('created_at', today())
                ->count(),
            'groups' => Conversation::query()
                ->whereIn('id', $conversationIds)
                ->where('type', 'group')
                ->count(),
            'archived' => (clone $membershipQuery)->where('is_archived', true)->count(),
            'flagged' => (clone $membershipQuery)->where('is_flagged', true)->count(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function conversationSummaries(User $user, string $filter = 'all', ?string $search = null): Collection
    {
        $query = ConversationMember::query()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->with([
                'conversation.activeMembers.user.rank',
                'conversation.messages' => fn ($q) => $q->latest()->limit(1)->with(['sender:id,name', 'reads']),
            ]);

        $query = match ($filter) {
            'direct' => $query->whereHas('conversation', fn (Builder $q) => $q->where('type', 'direct')),
            'group' => $query->whereHas('conversation', fn (Builder $q) => $q->where('type', 'group')),
            'archived' => $query->where('is_archived', true),
            'flagged' => $query->where('is_flagged', true),
            default => $query->where('is_archived', false),
        };

        if ($search) {
            $query->where(function (Builder $q) use ($user, $search): void {
                $q->whereHas('conversation', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('conversation.activeMembers.user', function (Builder $q) use ($user, $search): void {
                        $q->where('users.id', '!=', $user->id)
                            ->where('users.name', 'like', "%{$search}%");
                    });
            });
        }

        return $query
            ->get()
            ->sortByDesc(fn (ConversationMember $member): int => (int) ($member->conversation?->last_message_at?->timestamp ?? 0))
            ->map(fn (ConversationMember $member) => $this->formatConversationSummary($user, $member))
            ->values();
    }

    public function findOrCreateDirectConversation(User $initiator, User $recipient): Conversation
    {
        abort_unless($this->authorization->canMessage($initiator, $recipient), 403);

        $existing = Conversation::query()
            ->where('type', 'direct')
            ->whereHas('members', fn (Builder $q) => $q->where('user_id', $initiator->id)->whereNull('left_at'))
            ->whereHas('members', fn (Builder $q) => $q->where('user_id', $recipient->id)->whereNull('left_at'))
            ->first();

        if ($existing) {
            return $existing->load('activeMembers.user');
        }

        return DB::transaction(function () use ($initiator, $recipient): Conversation {
            $conversation = Conversation::create([
                'type' => 'direct',
                'created_by' => $initiator->id,
                'last_message_at' => now(),
            ]);

            foreach ([$initiator, $recipient] as $member) {
                ConversationMember::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $member->id,
                    'member_role' => $member->id === $initiator->id ? 'owner' : 'member',
                    'joined_at' => now(),
                ]);
            }

            return $conversation->load('activeMembers.user');
        });
    }

    public function createGroupConversation(User $creator, string $name, array $memberIds): Conversation
    {
        $this->ensureCanUseMessaging($creator);
        abort_unless($creator->hasPermissionTo('manage message groups'), 403);

        return DB::transaction(function () use ($creator, $name, $memberIds): Conversation {
            $conversation = Conversation::create([
                'type' => 'group',
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
                'created_by' => $creator->id,
                'last_message_at' => now(),
            ]);

            $ids = collect($memberIds)->push($creator->id)->unique()->values();

            foreach ($ids as $userId) {
                ConversationMember::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                    'member_role' => (int) $userId === (int) $creator->id ? 'owner' : 'member',
                    'joined_at' => now(),
                ]);
            }

            return $conversation->load('activeMembers.user');
        });
    }

    /**
     * @return Collection<int, Message>
     */
    public function messagesFor(User $user, Conversation $conversation, ?int $threadParentId = null): Collection
    {
        $this->ensureMember($user, $conversation);

        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->when($threadParentId, fn (Builder $q) => $q->where('parent_id', $threadParentId), fn (Builder $q) => $q->whereNull('parent_id'))
            ->with(['sender:id,name,email', 'attachments', 'reactions.user:id,name', 'reads'])
            ->whereDoesntHave('deletes', fn (Builder $q) => $q->where('user_id', $user->id))
            ->orderBy('created_at')
            ->get()
            ->each(fn (Message $message) => $this->markDelivered($message, $user));
    }

    public function sendMessage(
        User $sender,
        Conversation $conversation,
        string $body,
        ?int $parentId = null,
        string $messageType = 'text',
    ): Message {
        $this->ensureCanUseMessaging($sender);
        abort_unless($sender->hasPermissionTo('send messages'), 403);
        $this->ensureMember($sender, $conversation);

        $body = trim($body);
        if ($body === '') {
            throw ValidationException::withMessages(['body' => 'Enter a message before sending.']);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $sender->id,
            'parent_id' => $parentId,
            'body' => $body,
            'message_type' => $messageType,
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);
        $this->markRead($sender, $conversation, $message);

        $recipientIds = $this->notificationRecipients->conversationRecipients($sender, $conversation->id);

        if ($recipientIds !== []) {
            $preview = Str::limit(strip_tags($body), 120);

            $this->notifications->dispatch('message_received', [
                'queue' => true,
                'sender' => $sender,
                'recipients' => ['user_ids' => $recipientIds],
                'module' => 'message',
                'priority' => 'medium',
                'related' => ['type' => Conversation::class, 'id' => $conversation->id],
                'template_data' => [
                    'sender_name' => $sender->name,
                    'message_preview' => $preview,
                ],
                'action_link' => [
                    'route' => 'messages.index',
                    'params' => ['conversation' => $conversation->id],
                    'label' => 'Open conversation',
                ],
            ]);
        }

        return $message->load(['sender:id,name,email', 'attachments', 'reactions']);
    }

    public function markRead(User $user, Conversation $conversation, ?Message $throughMessage = null): void
    {
        $membership = $this->membership($user, $conversation);
        if (! $membership) {
            return;
        }

        $membership->update(['last_read_at' => now()]);

        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->when($throughMessage, fn (Builder $q) => $q->where('id', '<=', $throughMessage->id))
            ->where('user_id', '!=', $user->id)
            ->pluck('id');

        foreach ($messages as $messageId) {
            MessageRead::query()->firstOrCreate(
                ['message_id' => $messageId, 'user_id' => $user->id],
                ['read_at' => now()],
            );
        }
    }

    public function toggleReaction(User $user, Message $message, string $reaction): void
    {
        $this->ensureCanUseMessaging($user);
        $this->ensureMember($user, $message->conversation);

        $existing = MessageReaction::query()
            ->where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('reaction', $reaction)
            ->first();

        if ($existing) {
            $existing->delete();

            return;
        }

        MessageReaction::create([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'reaction' => $reaction,
        ]);
    }

    public function deleteForMe(User $user, Message $message): void
    {
        abort_unless($user->can('delete own messages'), 403);
        $this->ensureMember($user, $message->conversation);

        MessageDelete::query()->updateOrCreate(
            ['message_id' => $message->id, 'user_id' => $user->id],
            ['delete_scope' => 'me'],
        );
    }

    public function deleteForEveryone(User $user, Message $message): void
    {
        abort_unless((int) $message->user_id === (int) $user->id, 403);
        $this->ensureMember($user, $message->conversation);

        $message->loadMissing('conversation');
        abort_unless(
            $message->conversation?->isGroup()
                ? $user->can('delete group messages')
                : $user->can('delete own messages'),
            403
        );

        $windowMinutes = config('messaging.delete_for_everyone_minutes', 60);
        if ($message->created_at->lt(now()->subMinutes($windowMinutes))) {
            throw ValidationException::withMessages([
                'message' => "Messages can only be deleted for everyone within {$windowMinutes} minutes.",
            ]);
        }

        $message->delete();
    }

    public function editMessage(User $user, Message $message, string $body): Message
    {
        abort_unless((int) $message->user_id === (int) $user->id, 403);
        $this->ensureMember($user, $message->conversation);

        $message->update([
            'body' => trim($body),
            'edited_at' => now(),
        ]);

        return $message->refresh();
    }

    public function archiveConversation(User $user, Conversation $conversation, bool $archived = true): void
    {
        abort_unless(
            $user->can($archived ? 'archive conversations' : 'restore conversations'),
            403
        );

        $membership = $this->membership($user, $conversation);
        abort_unless($membership, 403);

        $membership->update(['is_archived' => $archived]);
    }

    public function togglePinConversation(User $user, Conversation $conversation): void
    {
        $membership = $this->membership($user, $conversation);
        abort_unless($membership, 403);

        $membership->update(['is_pinned' => ! $membership->is_pinned]);
    }

    /**
     * @return Collection<int, User>
     */
    public function messageableUsers(User $user, ?string $search = null): Collection
    {
        $query = User::query()
            ->where('id', '!=', $user->id)
            ->whereNull('deleted_at')
            ->with(['rank', 'profile'])
            ->orderBy('name')
            ->limit(50);

        if ($search) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->get()->filter(fn (User $candidate) => $this->authorization->canMessage($user, $candidate))->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function contextPanel(User $viewer, Conversation $conversation): array
    {
        $this->ensureMember($viewer, $conversation);
        $conversation->loadMissing(['activeMembers.user.rank', 'activeMembers.user.profile', 'activeMembers.user.sponsor', 'activeMembers.user.mentor']);

        $otherMembers = $conversation->activeMembers
            ->reject(fn (ConversationMember $member): bool => (int) $member->user_id === (int) $viewer->id)
            ->values();

        $focusUser = $otherMembers->count() === 1 ? $otherMembers->first()?->user : null;

        $progress = null;
        if ($focusUser) {
            $progress = [
                'onboarding' => $this->checklistProgressSnapshot($focusUser, 'onboarding'),
                'licensing' => $this->checklistProgressSnapshot($focusUser, 'licensing'),
                'fap' => $this->checklistProgressSnapshot($focusUser, 'fap'),
            ];
        }

        return [
            'members' => $otherMembers->map(fn (ConversationMember $member) => [
                'id' => $member->user_id,
                'name' => $member->user?->name,
                'role' => $member->user?->topbarRankRoleLabel('Member'),
                'photo_url' => $member->user?->profilePhotoUrl(),
                'sponsor' => $member->user?->sponsor?->name,
                'mentor' => $member->user?->mentor?->name,
                'joined_at' => $member->user?->joined_at?->format('M j, Y'),
                'profile_url' => route('team.member.profile', $member->user_id),
            ])->all(),
            'focus_user' => $focusUser ? [
                'id' => $focusUser->id,
                'name' => $focusUser->name,
                'role' => $focusUser->topbarRankRoleLabel('Member'),
                'photo_url' => $focusUser->profilePhotoUrl(),
            ] : null,
            'progress' => $progress,
        ];
    }

    public function createTaskFromMessage(User $creator, Message $message, array $data): TaskUser
    {
        $this->ensureMember($creator, $message->conversation);

        $priority = $data['priority'] ?? 'medium';
        if ($priority === 'normal') {
            $priority = 'medium';
        }

        $task = TaskUser::create(TaskUserAttributes::forTask(
            $data['category'] ?? 'CFM Mentorship',
            $data['title'],
            [
                'assignee_id' => $data['assigned_to_user_id'] ?? $data['assignee_id'] ?? $creator->id,
                'assignor_id' => $creator->id,
                'additional_notes' => $data['additional_notes'] ?? null,
                'priority' => $priority,
                'status' => 'to_do',
                'related_module' => 'messages',
                'due_date' => $data['due_date'] ?? null,
            ],
            $data['description'] ?? Str::limit($message->body, 500),
            $priority,
        ));

        DB::table('message_tasks')->insert([
            'message_id' => $message->id,
            'task_user_id' => $task->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $task;
    }

    /**
     * @return Collection<int, MessageTemplate>
     */
    public function activeTemplates(): Collection
    {
        return MessageTemplate::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('title')
            ->get();
    }

    private function checklistProgressSnapshot(User $user, string $typeCode): ?array
    {
        if (! $this->checklists->hasTypeStarted($user, $typeCode)) {
            return ['label' => str($typeCode)->headline()->toString(), 'percent' => 0, 'status' => 'Not started'];
        }

        $steps = $this->checklists->activeSteps($typeCode, $user->profile?->country);
        $progress = $this->checklists->userProgressFor($user->id, $steps->pluck('id'));
        $total = $steps->count();
        $completed = $steps->filter(fn ($step) => ($progress->get($step->id)?->status ?? '') === 'completed')->count();
        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return [
            'label' => str($typeCode)->headline()->toString(),
            'percent' => $percent,
            'status' => $percent >= 100 ? 'Complete' : ($percent > 0 ? 'In progress' : 'Started'),
        ];
    }

    private function formatConversationSummary(User $user, ConversationMember $member): array
    {
        $conversation = $member->conversation;
        $lastMessage = $conversation?->messages->first();
        $unread = $lastMessage
            && (int) $lastMessage->user_id !== (int) $user->id
            && ! $lastMessage->reads->contains('user_id', $user->id);

        $otherMember = $conversation?->activeMembers
            ->first(fn (ConversationMember $m): bool => (int) $m->user_id !== (int) $user->id);

        return [
            'id' => $conversation?->id,
            'type' => $conversation?->type,
            'name' => $conversation?->displayNameFor($user),
            'role' => $otherMember?->user?->topbarRankRoleLabel('Member'),
            'photo_url' => $otherMember?->user?->profilePhotoUrl(),
            'last_message' => $lastMessage?->body ? Str::limit($lastMessage->body, 80) : 'No messages yet',
            'last_message_at' => $conversation?->last_message_at?->diffForHumans(),
            'unread' => $unread,
            'is_pinned' => $member->is_pinned,
            'is_archived' => $member->is_archived,
            'is_flagged' => $member->is_flagged,
        ];
    }

    private function membership(User $user, Conversation $conversation): ?ConversationMember
    {
        return ConversationMember::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();
    }

    private function ensureMember(User $user, Conversation $conversation): ConversationMember
    {
        $membership = $this->membership($user, $conversation);
        abort_unless($membership, 403);

        return $membership;
    }

    private function ensureCanUseMessaging(User $user): void
    {
        abort_unless(
            $this->authorization->canUseMessaging($user),
            403,
            'Your messaging access has been suspended. Contact your team administrator if you believe this is an error.',
        );
    }

    private function markDelivered(Message $message, User $viewer): Message
    {
        if ((int) $message->user_id !== (int) $viewer->id) {
            return $message;
        }

        return $message;
    }
}
