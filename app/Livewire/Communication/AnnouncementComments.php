<?php

namespace App\Livewire\Communication;

use App\Models\AnnouncementComment;
use App\Models\MessageCenterAnnouncement;
use App\Services\Communication\AnnouncementEngagementService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AnnouncementComments extends Component
{
    public MessageCenterAnnouncement $announcement;

    public string $body = '';

    public ?int $replyToId = null;

    public function mount(MessageCenterAnnouncement $announcement): void
    {
        $this->authorize('view', $announcement);
        $this->announcement = $announcement;
    }

    public function startReply(int $commentId): void
    {
        $this->replyToId = $commentId;
        $this->resetErrorBag();
    }

    public function cancelReply(): void
    {
        $this->replyToId = null;
        $this->body = '';
    }

    public function postComment(AnnouncementEngagementService $engagement): void
    {
        $this->authorize('view', $this->announcement);

        $validated = $this->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $engagement->addComment(
            auth()->user(),
            $this->announcement,
            $validated['body'],
            $this->replyToId,
        );

        $this->body = '';
        $this->replyToId = null;
    }

    public function deleteComment(int $commentId, AnnouncementEngagementService $engagement): void
    {
        $comment = AnnouncementComment::query()
            ->where('announcement_id', $this->announcement->id)
            ->findOrFail($commentId);

        $engagement->deleteComment(auth()->user(), $comment);
    }

    public function render(AnnouncementEngagementService $engagement): View
    {
        return view('livewire.communication.announcement-comments', [
            'comments' => $engagement->commentsFor($this->announcement),
            'commentCount' => $engagement->commentCountFor($this->announcement),
        ]);
    }
}
