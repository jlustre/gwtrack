<?php

namespace App\Services\CfmPortal;

use App\Models\CfmNote;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CfmCoachingNoteService
{
    public function __construct(
        private readonly CfmTraineeCenterService $centers,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function centerFor(User $cfm, int $traineeId, ?string $categoryFilter = null): ?array
    {
        $trainee = $this->centers->resolveTrainee($cfm, $traineeId);

        if (! $trainee) {
            return null;
        }

        $notes = CfmNote::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->with('author')
            ->latest()
            ->get();

        $grouped = $notes->groupBy('category')->map(fn (Collection $rows) => $rows->count());
        $visible = $notes;

        if ($categoryFilter && $categoryFilter !== 'all') {
            $visible = $notes->where('category', $categoryFilter)->values();
        }

        return [
            'key' => 'notes',
            'title' => 'Coaching Notes',
            'description' => 'Private mentor notes — strengths, weaknesses, opportunities, challenges, and recommendations.',
            'stats' => [
                'total' => $notes->count(),
                'strengths' => $grouped->get('strength', 0),
                'weaknesses' => $grouped->get('weakness', 0),
                'recommendations' => $grouped->get('recommendation', 0),
            ],
            'notes' => $visible->map(fn (CfmNote $note) => $this->noteRow($note))->values()->all(),
            'categories' => CfmNote::CATEGORIES,
            'category_filter' => $categoryFilter ?? 'all',
            'member_profile_url' => route('team.member.profile', $trainee),
        ];
    }

    public function create(User $cfm, User $trainee, User $actor, array $data): CfmNote
    {
        $this->assertTraineeAccess($cfm, $trainee);
        $this->validateCategory($data['category'] ?? 'general');

        return CfmNote::query()->create([
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'category' => $data['category'] ?? 'general',
            'body' => $data['body'],
            'tags' => $data['tags'] ?? null,
            'is_private' => true,
            'created_by' => $actor->id,
        ]);
    }

    public function update(User $cfm, CfmNote $note, User $actor, array $data): CfmNote
    {
        $this->assertNoteAccess($cfm, $note);
        $this->validateCategory($data['category'] ?? $note->category);

        $note->update([
            'category' => $data['category'] ?? $note->category,
            'body' => $data['body'] ?? $note->body,
        ]);

        return $note->refresh();
    }

    public function delete(User $cfm, CfmNote $note): void
    {
        $this->assertNoteAccess($cfm, $note);
        $note->delete();
    }

    public function findForCfm(User $cfm, int $noteId): CfmNote
    {
        return CfmNote::query()
            ->where('cfm_id', $cfm->id)
            ->whereKey($noteId)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function noteRow(CfmNote $note): array
    {
        return [
            'id' => $note->id,
            'category' => $note->category,
            'category_label' => $note->categoryLabel(),
            'body' => $note->body,
            'author' => $note->author?->name ?? '—',
            'created_at' => $note->created_at?->format('M j, Y g:i A'),
            'updated_at' => $note->updated_at?->format('M j, Y g:i A'),
        ];
    }

    private function validateCategory(string $category): void
    {
        if (! in_array($category, CfmNote::CATEGORIES, true)) {
            throw ValidationException::withMessages(['category' => 'Invalid note category.']);
        }
    }

    private function assertTraineeAccess(User $cfm, User $trainee): void
    {
        if (! $this->centers->resolveTrainee($cfm, $trainee->id)) {
            abort(403);
        }
    }

    private function assertNoteAccess(User $cfm, CfmNote $note): void
    {
        if ((int) $note->cfm_id !== (int) $cfm->id) {
            abort(403);
        }
    }
}
