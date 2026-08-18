<?php

namespace App\Livewire\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\Messaging\MessagingAuthorizationService;
use App\Services\Messaging\MessagingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ConversationPanel extends Component
{
    public int $conversationId;

    public bool $isMessagingSuspended = false;

    public string $messageBody = '';

    public ?int $replyToMessageId = null;

    public bool $showTemplates = false;

    public bool $showTaskModal = false;

    public ?int $taskMessageId = null;

    public string $taskTitle = '';

    public string $taskPriority = 'normal';

    public ?string $taskDueDate = null;

    public function mount(MessagingService $messaging): void
    {
        $conversation = Conversation::query()->findOrFail($this->conversationId);
        $messaging->markRead(auth()->user(), $conversation);
    }

    #[On('conversation-selected')]
    public function refreshThread(int $conversationId): void
    {
        if ($conversationId !== $this->conversationId) {
            return;
        }

        $this->replyToMessageId = null;
        $this->messageBody = '';

        app(MessagingService::class)->markRead(auth()->user(), Conversation::query()->findOrFail($conversationId));
    }

    public function sendMessage(MessagingService $messaging): void
    {
        $this->validate([
            'messageBody' => ['required', 'string', 'max:5000'],
        ]);

        $conversation = Conversation::query()->findOrFail($this->conversationId);

        $messaging->sendMessage(
            auth()->user(),
            $conversation,
            $this->messageBody,
            $this->replyToMessageId,
        );

        $this->messageBody = '';
        $this->replyToMessageId = null;

        $this->dispatch('scroll-to-latest-message');
    }

    public function setReply(int $messageId): void
    {
        $this->replyToMessageId = $messageId;
        $this->dispatch('scroll-to-latest-message');
    }

    public function cancelReply(): void
    {
        $this->replyToMessageId = null;
    }

    public function reactToMessage(int $messageId, string $reaction, MessagingService $messaging): void
    {
        if (! in_array($reaction, config('messaging.reactions', []), true)) {
            return;
        }

        $message = Message::query()->findOrFail($messageId);
        $messaging->toggleReaction(auth()->user(), $message, $reaction);
    }

    public function deleteMessageForMe(int $messageId, MessagingService $messaging): void
    {
        $message = Message::query()->findOrFail($messageId);
        $messaging->deleteForMe(auth()->user(), $message);
    }

    public function deleteMessageForEveryone(int $messageId, MessagingService $messaging): void
    {
        $message = Message::query()->findOrFail($messageId);
        $messaging->deleteForEveryone(auth()->user(), $message);
    }

    public function archiveSelected(MessagingService $messaging): void
    {
        $conversation = Conversation::query()->findOrFail($this->conversationId);
        $messaging->archiveConversation(auth()->user(), $conversation, true);

        $this->dispatch('conversation-archived');
    }

    public function applyTemplate(string $body): void
    {
        $this->messageBody = $body;
        $this->showTemplates = false;
    }

    public function openTaskModal(int $messageId): void
    {
        $this->taskMessageId = $messageId;
        $this->showTaskModal = true;
    }

    public function createTaskFromMessage(MessagingService $messaging): void
    {
        $this->validate([
            'taskTitle' => ['required', 'string', 'max:255'],
            'taskPriority' => ['required', 'in:low,normal,high,urgent'],
            'taskDueDate' => ['nullable', 'date'],
        ]);

        $message = Message::query()->findOrFail($this->taskMessageId);

        $messaging->createTaskFromMessage(auth()->user(), $message, [
            'title' => $this->taskTitle,
            'priority' => $this->taskPriority,
            'due_date' => $this->taskDueDate,
            'assigned_to_user_id' => auth()->id(),
        ]);

        $this->showTaskModal = false;
        $this->taskMessageId = null;
        $this->taskTitle = '';
        $this->taskDueDate = null;

        session()->flash('message_status', 'Task created from message.');
    }

    public function render(MessagingService $messaging): View
    {
        $user = auth()->user();
        $selectedConversation = Conversation::query()
            ->with('activeMembers.user.rank')
            ->findOrFail($this->conversationId);

        return view('livewire.messaging.conversation-panel', [
            'selectedConversation' => $selectedConversation,
            'messages' => $messaging->messagesFor($user, $selectedConversation),
            'templates' => $messaging->activeTemplates(),
            'reactions' => config('messaging.reactions', []),
            'canManageGroups' => $user->hasPermissionTo('manage message groups'),
            'deleteWindowMinutes' => config('messaging.delete_for_everyone_minutes', 60),
            'isMessagingSuspended' => $this->isMessagingSuspended || ! app(MessagingAuthorizationService::class)->canUseMessaging($user),
            'messagingSuspensionReason' => $user->messaging_suspension_reason,
        ]);
    }
}
