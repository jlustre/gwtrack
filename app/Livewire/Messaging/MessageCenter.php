<?php

namespace App\Livewire\Messaging;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Messaging\MessagingAuthorizationService;
use App\Services\Messaging\MessagingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Messages')]
class MessageCenter extends Component
{
    #[Url(as: 'c', except: null)]
    public ?int $conversationId = null;

    #[Url(as: 'filter', except: 'all')]
    public string $listFilter = 'all';

    public string $search = '';

    public bool $showNewConversation = false;

    public ?int $newRecipientId = null;

    public string $recipientSearch = '';

    public string $groupName = '';

    /** @var array<int> */
    public array $groupMemberIds = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermissionTo('view conversations'), 403);
    }

    public function selectConversation(int $conversationId): void
    {
        $this->conversationId = $conversationId;

        $this->dispatch('conversation-selected', conversationId: $conversationId);
    }

    #[On('conversation-archived')]
    public function handleConversationArchived(): void
    {
        $this->conversationId = null;
    }

    public function startDirectConversation(MessagingService $messaging): void
    {
        abort_unless(app(MessagingAuthorizationService::class)->canUseMessaging(auth()->user()), 403);

        abort_unless($this->newRecipientId, 422);

        $recipient = User::query()->findOrFail($this->newRecipientId);
        $conversation = $messaging->findOrCreateDirectConversation(auth()->user(), $recipient);

        $this->conversationId = $conversation->id;
        $this->showNewConversation = false;
        $this->newRecipientId = null;
        $this->recipientSearch = '';

        $this->dispatch('conversation-selected', conversationId: $conversation->id);
    }

    public function createGroup(MessagingService $messaging): void
    {
        abort_unless(app(MessagingAuthorizationService::class)->canUseMessaging(auth()->user()), 403);

        $this->validate([
            'groupName' => ['required', 'string', 'max:120'],
            'groupMemberIds' => ['required', 'array', 'min:1'],
        ]);

        $conversation = $messaging->createGroupConversation(
            auth()->user(),
            $this->groupName,
            $this->groupMemberIds,
        );

        $this->conversationId = $conversation->id;
        $this->showNewConversation = false;
        $this->groupName = '';
        $this->groupMemberIds = [];

        $this->dispatch('conversation-selected', conversationId: $conversation->id);
    }

    public function render(MessagingService $messaging, MessagingAuthorizationService $authorization): View
    {
        $user = auth()->user();

        $conversations = $messaging->conversationSummaries($user, $this->listFilter, $this->search ?: null);
        $selectedConversation = $this->conversationId
            ? Conversation::query()->with('activeMembers.user.rank')->find($this->conversationId)
            : null;

        $context = $selectedConversation
            ? $messaging->contextPanel($user, $selectedConversation)
            : ['members' => [], 'focus_user' => null, 'progress' => null];

        return view('livewire.messaging.message-center', [
            'stats' => $messaging->dashboardStats($user),
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'context' => $context,
            'messageableUsers' => $this->showNewConversation
                ? $messaging->messageableUsers($user, $this->recipientSearch ?: null)
                : collect(),
            'canManageGroups' => $user->hasPermissionTo('manage message groups'),
            'canBroadcast' => $authorization->canSendBroadcast($user),
            'isMessagingSuspended' => ! $authorization->canUseMessaging($user),
            'messagingSuspensionReason' => $user->messaging_suspension_reason,
            'usagePolicyNotice' => config('messaging.usage_policy_notice'),
        ])->layout('layouts.app');
    }
}
