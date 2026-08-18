<?php

namespace App\Services\Notifications;

use App\Models\Checklist;
use App\Models\ChecklistProgress;
use App\Models\ChecklistType;
use App\Models\User;

class ChecklistNotificationDispatcher
{
    public function __construct(
        private readonly NotificationOrchestrator $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    public function typeStarted(User $member, ChecklistType $type, User $startedBy): void
    {
        $recipientIds = array_values(array_unique(array_filter([
            $member->id,
            $member->mentor_id,
            $member->sponsor_id,
        ])));

        if ($recipientIds === []) {
            return;
        }

        $this->notifications->dispatch('checklist_type_started', [
            'queue' => true,
            'sender' => $startedBy,
            'recipients' => ['user_ids' => $recipientIds],
            'module' => $this->moduleForTypeCode($type->code),
            'priority' => 'info',
            'related_user_id' => $member->id,
            'template_data' => [
                'member_name' => $member->name,
                'checklist_type' => $type->name,
            ],
            'action_link' => $this->actionLinkForType($type->code, $member),
        ]);
    }

    public function itemSubmitted(User $member, Checklist $checklist): void
    {
        $checklist->loadMissing('type');
        $reviewerIds = $this->recipients->checklistReviewers($member, $checklist);

        if ($reviewerIds === []) {
            return;
        }

        $this->notifications->dispatch('checklist_item_submitted', [
            'queue' => true,
            'sender' => $member,
            'recipients' => ['user_ids' => $reviewerIds],
            'module' => $this->moduleForTypeCode($checklist->type?->code),
            'priority' => 'medium',
            'related' => ['type' => Checklist::class, 'id' => $checklist->id],
            'related_user_id' => $member->id,
            'template_data' => [
                'member_name' => $member->name,
                'step_title' => $checklist->title,
                'checklist_type' => $checklist->type?->name ?? 'Checklist',
            ],
            'action_link' => [
                'route' => 'tasks.index',
                'label' => 'Review',
            ],
        ]);
    }

    public function itemReviewed(ChecklistProgress $progress, User $reviewer, bool $approved): void
    {
        $progress->loadMissing(['user', 'checklist.type']);
        $member = $progress->user;
        $checklist = $progress->checklist;

        if (! $member || ! $checklist) {
            return;
        }

        $typeCode = $checklist->type?->code;
        $trigger = $approved
            ? ($typeCode === 'licensing' ? 'licensing_step_approved' : 'checklist_item_approved')
            : 'checklist_item_rejected';

        $this->notifications->dispatch($trigger, [
            'queue' => true,
            'sender' => $reviewer,
            'recipients' => [$member->id],
            'module' => $this->moduleForTypeCode($typeCode),
            'priority' => $approved ? 'info' : 'medium',
            'related' => ['type' => Checklist::class, 'id' => $checklist->id],
            'related_user_id' => $member->id,
            'template_data' => [
                'member_name' => $member->name,
                'step_title' => $checklist->title,
                'reviewer_name' => $reviewer->name,
                'checklist_type' => $checklist->type?->name ?? 'Checklist',
            ],
            'action_link' => $this->actionLinkForType($typeCode, $member),
        ]);
    }

    private function moduleForTypeCode(?string $typeCode): string
    {
        return match ($typeCode) {
            'licensing' => 'licensing',
            'fap' => 'fap',
            'cfm-training', 'training' => 'training',
            'cfm-mentoring' => 'fap',
            default => 'onboarding',
        };
    }

    /**
     * @return array{route: string, params?: array<string, mixed>, label?: string}|null
     */
    private function actionLinkForType(?string $typeCode, User $member): ?array
    {
        return match ($typeCode) {
            'onboarding' => ['route' => 'onboarding.index', 'label' => 'View onboarding'],
            'licensing' => ['route' => 'licensing.index', 'label' => 'View licensing'],
            'fap' => ['route' => 'apprenticeship.index', 'label' => 'View FAP'],
            'cfm-training' => ['route' => 'cfm-training.index', 'label' => 'View training'],
            default => ['route' => 'dashboard', 'label' => 'View dashboard'],
        };
    }
}
