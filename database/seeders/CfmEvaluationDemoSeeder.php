<?php

namespace Database\Seeders;

use App\Models\CfmEffectiveness\CfmFeedbackQuestion;
use App\Models\CfmEffectiveness\CfmFeedbackResponse;
use App\Models\CfmEffectiveness\CfmReview;
use App\Models\MentorAssignment;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for Trainee CFM Evaluation + Admin CFM Performance Manager.
 *
 * Run:
 *   php artisan db:seed --class=CfmEvaluationDemoSeeder
 */
class CfmEvaluationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CfmEffectivenessSeeder::class);

        $agencyOwner = User::query()->where('email', 'agency-owner@gwtrack.com')->first()
            ?? User::query()->where('email', 'super-admin@gwtrack.com')->first();

        if (! $agencyOwner) {
            $this->command?->warn('CfmEvaluationDemoSeeder skipped: seed UsersSeeder first (agency-owner@gwtrack.com).');

            return;
        }

        $teamId = $agencyOwner->team_id;
        $rankFa = Rank::query()->where('code', 'FA')->value('id') ?? Rank::query()->value('id');
        $rankSfa = Rank::query()->where('code', 'SFA')->value('id') ?? $rankFa;

        $celeste = $this->user(
            'cfm@gwtrack.com',
            'CFM User',
            ['certified-field-mentor'],
            $teamId,
            $rankSfa,
            $agencyOwner->id,
        );

        $maria = $this->user(
            'maria.cfm@gwtrack.com',
            'Maria Santos',
            ['certified-field-mentor'],
            $teamId,
            $rankSfa,
            $agencyOwner->id,
        );

        $john = $this->user(
            'john.cfm@gwtrack.com',
            'John Reyes',
            ['certified-field-mentor'],
            $teamId,
            $rankSfa,
            $agencyOwner->id,
        );

        // Dedicated evaluation trainees (clear login accounts for testing).
        $evalPending = $this->user(
            'eval.trainee@gwtrack.com',
            'Eva Trainee (Pending Eval)',
            ['member'],
            $teamId,
            $rankFa,
            $agencyOwner->id,
            $celeste->id,
        );

        $evalReady = $this->user(
            'eval.ready@gwtrack.com',
            'Ryan Ready (Can Start Eval)',
            ['member'],
            $teamId,
            $rankFa,
            $agencyOwner->id,
            $maria->id,
        );

        $evalDone = $this->user(
            'eval.done@gwtrack.com',
            'Dana Done (Submitted Eval)',
            ['member'],
            $teamId,
            $rankFa,
            $agencyOwner->id,
            $john->id,
        );

        // Extra trainees for Maria/John so admin "Request Evaluation" list is populated.
        $mariaApprentices = [];
        foreach (range(1, 2) as $i) {
            $mariaApprentices[] = $this->user(
                "eval.maria{$i}@gwtrack.com",
                "Maria Eval Apprentice {$i}",
                ['member'],
                $teamId,
                $rankFa,
                $agencyOwner->id,
                $maria->id,
            );
        }

        $assignmentCeleste = $this->assignment($celeste, $evalPending, $agencyOwner);
        $assignmentMaria = $this->assignment($maria, $evalReady, $agencyOwner);
        $assignmentJohn = $this->assignment($john, $evalDone, $agencyOwner);

        foreach ($mariaApprentices as $index => $apprentice) {
            $this->assignment($maria, $apprentice, $agencyOwner, now()->subDays(25 + $index));
        }

        $questions = CfmFeedbackQuestion::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($questions->isEmpty()) {
            $this->command?->warn('No active mentoring aspects found after CfmEffectivenessSeeder.');

            return;
        }

        // 1) Pending evaluation for Eva → Celeste (open the star form).
        $pendingReview = CfmReview::query()->updateOrCreate(
            [
                'cfm_id' => $celeste->id,
                'trainee_id' => $evalPending->id,
                'trigger_type' => 'trainee_requested',
            ],
            [
                'mentor_assignment_id' => $assignmentCeleste->id,
                'status' => 'pending',
                'due_at' => now()->addDays(10),
                'requested_by' => $evalPending->id,
                'submitted_at' => null,
                'average_rating' => null,
            ]
        );

        // Clear any old responses if this review was previously submitted in a re-seed.
        if ($pendingReview->status === 'pending') {
            CfmFeedbackResponse::query()->where('cfm_review_id', $pendingReview->id)->delete();
            $pendingReview->update([
                'submitted_at' => null,
                'average_rating' => null,
                'helped_most' => null,
                'improvements' => null,
                'comments' => null,
                'suggestions' => null,
            ]);
        }

        // Also give Eva a milestone pending review for variety on the hub.
        CfmReview::query()->updateOrCreate(
            [
                'cfm_id' => $celeste->id,
                'trainee_id' => $evalPending->id,
                'trigger_type' => '30_day',
            ],
            [
                'mentor_assignment_id' => $assignmentCeleste->id,
                'status' => 'pending',
                'due_at' => now()->addDays(5),
                'requested_by' => $agencyOwner->id,
            ]
        );

        // 2) Ryan has an active mentor but no pending invite — use "Start Evaluation" on the hub.
        CfmReview::query()
            ->where('trainee_id', $evalReady->id)
            ->where('status', 'pending')
            ->delete();

        // 3) Submitted evaluations so Admin Scoreboard has stars.
        $this->submitReview(
            cfm: $john,
            trainee: $evalDone,
            assignment: $assignmentJohn,
            trigger: '60_day',
            questions: $questions,
            ratingPattern: fn (int $index) => min(5, 3 + ($index % 3)),
            open: [
                'helped_most' => 'Clear weekly coaching and great FAP follow-up.',
                'improvements' => 'Would like more role-play practice before client meetings.',
                'comments' => 'Overall a strong mentor experience.',
                'suggestions' => 'Share a simple weekly checklist in GWTrack.',
            ],
        );

        // More submitted history across CFMs for the scoreboard.
        foreach ($mariaApprentices as $index => $apprentice) {
            $assignment = MentorAssignment::query()
                ->where('mentor_id', $maria->id)
                ->where('apprentice_id', $apprentice->id)
                ->where('status', 'active')
                ->first();

            $this->submitReview(
                cfm: $maria,
                trainee: $apprentice,
                assignment: $assignment,
                trigger: $index === 0 ? '90_day' : 'fap_completion',
                questions: $questions,
                ratingPattern: fn (int $i) => $index === 0 ? 5 : max(1, 4 - ($i % 2)),
                open: [
                    'helped_most' => 'Availability and encouragement.',
                    'improvements' => $index === 0 ? null : 'Faster responses on weekends.',
                    'comments' => 'Seeded evaluation for CFM Performance Manager testing.',
                    'suggestions' => null,
                ],
            );
        }

        // One more submitted for Celeste so she appears on the scoreboard too.
        $celesteHistoryTrainee = $this->user(
            'eval.history@gwtrack.com',
            'Harper History (Submitted)',
            ['member'],
            $teamId,
            $rankFa,
            $agencyOwner->id,
            $celeste->id,
        );
        $celesteHistoryAssignment = $this->assignment($celeste, $celesteHistoryTrainee, $agencyOwner, now()->subDays(70));
        $this->submitReview(
            cfm: $celeste,
            trainee: $celesteHistoryTrainee,
            assignment: $celesteHistoryAssignment,
            trigger: 'ao_requested',
            questions: $questions,
            ratingPattern: fn (int $i) => [4, 5, 5, 3, 4, 5][$i % 6],
            open: [
                'helped_most' => 'GWTrack walkthrough and licensing guidance.',
                'improvements' => null,
                'comments' => 'Agency-owner-requested evaluation sample.',
                'suggestions' => 'Keep the weekly check-ins.',
            ],
        );

        $this->command?->info('CFM evaluation demo data ready.');
        $this->command?->newLine();
        $this->command?->line('Admin CFM Performance Manager:');
        $this->command?->line('  URL:    /admin/cfm-performance');
        $this->command?->line('  Login:  agency-owner@gwtrack.com / Password123');
        $this->command?->line('          (or super-admin@gwtrack.com / Password123)');
        $this->command?->newLine();
        $this->command?->line('Trainee CFM Evaluation (pending stars form):');
        $this->command?->line('  URL:    /cfm/effectiveness/reviews');
        $this->command?->line('  Login:  eval.trainee@gwtrack.com / Password123');
        $this->command?->line('          → open pending review / Continue with stars');
        $this->command?->newLine();
        $this->command?->line('Trainee who can Start Evaluation (no invite yet):');
        $this->command?->line('  Login:  eval.ready@gwtrack.com / Password123');
        $this->command?->newLine();
        $this->command?->line('Trainee with submitted history:');
        $this->command?->line('  Login:  eval.done@gwtrack.com / Password123');
    }

    /**
     * @param  list<string>  $roles
     */
    private function user(
        string $email,
        string $name,
        array $roles,
        ?int $teamId,
        ?int $rankId,
        ?int $sponsorId,
        ?int $mentorId = null,
    ): User {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('Password123'),
                'team_id' => $teamId,
                'rank_id' => $rankId,
                'sponsor_id' => $sponsorId,
                'mentor_id' => $mentorId,
                'is_active' => true,
                'joined_at' => now()->subMonths(2),
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles($roles);
        $user->profile()->firstOrCreate(['user_id' => $user->id]);

        return $user;
    }

    private function assignment(User $mentor, User $apprentice, User $assignedBy, $startedAt = null): MentorAssignment
    {
        $apprentice->update(['mentor_id' => $mentor->id]);

        return MentorAssignment::query()->updateOrCreate(
            [
                'mentor_id' => $mentor->id,
                'apprentice_id' => $apprentice->id,
                'status' => 'active',
            ],
            [
                'assigned_by' => $assignedBy->id,
                'started_at' => ($startedAt ?? now()->subDays(35))->toDateString(),
                'completed_at' => null,
            ]
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CfmFeedbackQuestion>  $questions
     * @param  callable(int): int  $ratingPattern
     * @param  array{helped_most?: ?string, improvements?: ?string, comments?: ?string, suggestions?: ?string}  $open
     */
    private function submitReview(
        User $cfm,
        User $trainee,
        ?MentorAssignment $assignment,
        string $trigger,
        $questions,
        callable $ratingPattern,
        array $open = [],
    ): void {
        $review = CfmReview::query()->updateOrCreate(
            [
                'cfm_id' => $cfm->id,
                'trainee_id' => $trainee->id,
                'trigger_type' => $trigger,
            ],
            [
                'mentor_assignment_id' => $assignment?->id,
                'status' => 'submitted',
                'due_at' => now()->subDays(3),
                'submitted_at' => now()->subDay(),
                'requested_by' => $trainee->id,
                'helped_most' => $open['helped_most'] ?? null,
                'improvements' => $open['improvements'] ?? null,
                'comments' => $open['comments'] ?? null,
                'suggestions' => $open['suggestions'] ?? null,
            ]
        );

        $sum = 0;
        foreach ($questions->values() as $index => $question) {
            $rating = max(1, min(5, (int) $ratingPattern($index)));
            $sum += $rating;

            CfmFeedbackResponse::query()->updateOrCreate(
                [
                    'cfm_review_id' => $review->id,
                    'question_id' => $question->id,
                ],
                ['rating' => $rating]
            );
        }

        $review->update([
            'status' => 'submitted',
            'submitted_at' => $review->submitted_at ?? now()->subDay(),
            'average_rating' => $questions->count() > 0 ? round($sum / $questions->count(), 2) : null,
        ]);
    }
}
