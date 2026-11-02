<?php

namespace Database\Seeders;

use App\Models\CfmEffectiveness\CfmFeedbackQuestion;
use App\Models\CfmEffectiveness\CfmRecognitionBadge;
use App\Models\CfmEffectiveness\CfmReviewCycle;
use Illuminate\Database\Seeder;

class CfmEffectivenessSeeder extends Seeder
{
    public function run(): void
    {
        $cycles = [
            ['code' => '30_day', 'name' => '30-Day Review', 'trigger_type' => '30_day', 'days_after_assignment' => 30, 'sort_order' => 10],
            ['code' => '60_day', 'name' => '60-Day Review', 'trigger_type' => '60_day', 'days_after_assignment' => 60, 'sort_order' => 20],
            ['code' => '90_day', 'name' => '90-Day Review', 'trigger_type' => '90_day', 'days_after_assignment' => 90, 'sort_order' => 30],
            ['code' => 'fap_completion', 'name' => 'FAP Completion', 'trigger_type' => 'fap_completion', 'days_after_assignment' => null, 'sort_order' => 40],
            ['code' => 'licensing_completion', 'name' => 'Licensing Completion', 'trigger_type' => 'licensing_completion', 'days_after_assignment' => null, 'sort_order' => 50],
            ['code' => 'promotion', 'name' => 'Promotion Achievement', 'trigger_type' => 'promotion', 'days_after_assignment' => null, 'sort_order' => 60],
            ['code' => 'ao_requested', 'name' => 'AO Requested Review', 'trigger_type' => 'ao_requested', 'days_after_assignment' => null, 'sort_order' => 70],
            ['code' => 'trainee_requested', 'name' => 'Trainee CFM Evaluation', 'trigger_type' => 'trainee_requested', 'days_after_assignment' => null, 'sort_order' => 80],
        ];

        foreach ($cycles as $cycle) {
            CfmReviewCycle::query()->updateOrCreate(['code' => $cycle['code']], $cycle);
        }

        $questions = [
            // Onboarding & Orientation
            ['key' => 'explained_expectations', 'question' => 'Explained role expectations clearly from day one', 'category' => 'onboarding', 'sort_order' => 10],
            ['key' => 'welcomed_professionally', 'question' => 'Welcomed me professionally into the team', 'category' => 'onboarding', 'sort_order' => 20],
            ['key' => 'outlined_first_30_days', 'question' => 'Outlined a clear first-30-days plan', 'category' => 'onboarding', 'sort_order' => 30],
            ['key' => 'introduced_key_contacts', 'question' => 'Introduced me to key people and resources', 'category' => 'onboarding', 'sort_order' => 40],

            // Communication & Availability
            ['key' => 'responded_when_needed', 'question' => 'Responded when I needed help', 'category' => 'communication', 'sort_order' => 50],
            ['key' => 'availability', 'question' => 'Made themselves available for questions', 'category' => 'communication', 'sort_order' => 60],
            ['key' => 'clear_communication', 'question' => 'Communicated clearly and respectfully', 'category' => 'communication', 'sort_order' => 70],
            ['key' => 'kept_me_informed', 'question' => 'Kept me informed of schedule and next steps', 'category' => 'communication', 'sort_order' => 80],

            // Coaching & Skill Building
            ['key' => 'useful_coaching', 'question' => 'Provided useful coaching and practical guidance', 'category' => 'coaching', 'sort_order' => 90],
            ['key' => 'roleplay_practice', 'question' => 'Practiced presentations / role-play with me', 'category' => 'coaching', 'sort_order' => 100],
            ['key' => 'field_readiness', 'question' => 'Prepared me for real client conversations', 'category' => 'coaching', 'sort_order' => 110],
            ['key' => 'constructive_feedback', 'question' => 'Provided constructive, actionable feedback', 'category' => 'coaching', 'sort_order' => 120],

            // Accountability & Follow-Through
            ['key' => 'followed_up_consistently', 'question' => 'Followed up consistently on commitments', 'category' => 'accountability', 'sort_order' => 130],
            ['key' => 'held_accountable', 'question' => 'Held me accountable professionally', 'category' => 'accountability', 'sort_order' => 140],
            ['key' => 'tracked_milestones', 'question' => 'Tracked my milestones and progress', 'category' => 'accountability', 'sort_order' => 150],
            ['key' => 'helped_fap', 'question' => 'Helped me complete FAP requirements', 'category' => 'accountability', 'sort_order' => 160],

            // Systems & GWTracks
            ['key' => 'understood_gwtracks', 'question' => 'Helped me understand GWTracks', 'category' => 'systems', 'sort_order' => 170],
            ['key' => 'taught_workflows', 'question' => 'Taught me agency workflows and best practices', 'category' => 'systems', 'sort_order' => 180],
            ['key' => 'licensing_guidance', 'question' => 'Guided me through licensing steps', 'category' => 'systems', 'sort_order' => 190],
            ['key' => 'resource_navigation', 'question' => 'Showed me where to find training and resources', 'category' => 'systems', 'sort_order' => 200],

            // Personal Development & Leadership
            ['key' => 'developed_confidence', 'question' => 'Helped me develop confidence', 'category' => 'development', 'sort_order' => 210],
            ['key' => 'demonstrated_leadership', 'question' => 'Demonstrated leadership by example', 'category' => 'development', 'sort_order' => 220],
            ['key' => 'goal_setting_support', 'question' => 'Helped me set meaningful activity goals', 'category' => 'development', 'sort_order' => 230],
            ['key' => 'career_path_guidance', 'question' => 'Discussed my growth and advancement path', 'category' => 'development', 'sort_order' => 240],

            // Support & Care
            ['key' => 'encouraged_progress', 'question' => 'Encouraged my progress', 'category' => 'support', 'sort_order' => 250],
            ['key' => 'listened_with_care', 'question' => 'Listened to my concerns with care', 'category' => 'support', 'sort_order' => 260],
            ['key' => 'problem_solving_help', 'question' => 'Helped me solve problems without judgment', 'category' => 'support', 'sort_order' => 270],
            ['key' => 'celebrated_wins', 'question' => 'Recognized and celebrated my wins', 'category' => 'support', 'sort_order' => 280],
        ];

        foreach ($questions as $question) {
            CfmFeedbackQuestion::query()->updateOrCreate(
                ['key' => $question['key']],
                $question + ['is_active' => true],
            );
        }

        $badges = [
            ['code' => 'mentor_of_month', 'name' => 'Mentor of the Month', 'description' => 'Top overall effectiveness for the month.', 'criteria_key' => 'overall_effectiveness', 'sort_order' => 10],
            ['code' => 'fap_champion', 'name' => 'FAP Champion', 'description' => 'Highest FAP completion rate among CFMs.', 'criteria_key' => 'fap_completion_rate', 'sort_order' => 20],
            ['code' => 'licensing_champion', 'name' => 'Licensing Champion', 'description' => 'Highest licensing completion rate.', 'criteria_key' => 'licensing_completion_rate', 'sort_order' => 30],
            ['code' => 'retention_champion', 'name' => 'Retention Champion', 'description' => 'Best trainee retention outcomes.', 'criteria_key' => 'retention_rate', 'sort_order' => 40],
            ['code' => 'leadership_builder', 'name' => 'Leadership Builder', 'description' => 'Strong promotion development results.', 'criteria_key' => 'promotion_development_score', 'sort_order' => 50],
            ['code' => 'fast_track_mentor', 'name' => 'Fast Track Mentor', 'description' => 'Second-highest overall effectiveness for the month.', 'criteria_key' => 'overall_effectiveness', 'sort_order' => 60],
            ['code' => 'top_coach', 'name' => 'Top Coach', 'description' => 'Excellent coaching activity and engagement.', 'criteria_key' => 'coaching_activity_score', 'sort_order' => 70],
            ['code' => 'rising_mentor', 'name' => 'Rising Mentor', 'description' => 'Most improved effectiveness score month-over-month.', 'criteria_key' => 'overall_effectiveness', 'sort_order' => 80],
        ];

        foreach ($badges as $badge) {
            CfmRecognitionBadge::query()->updateOrCreate(['code' => $badge['code']], $badge);
        }
    }
}
