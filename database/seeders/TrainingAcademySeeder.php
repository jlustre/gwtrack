<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Answer;
use App\Models\CalendarCategory;
use App\Models\CalendarEventType;
use App\Models\Question;
use App\Models\TrainingBadge;
use App\Models\TrainingCategory;
use App\Models\TrainingCertification;
use App\Models\TrainingLesson;
use App\Models\TrainingModule;
use App\Models\TrainingPath;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TrainingAcademySeeder extends Seeder
{
    public function run(): void
    {
        $paths = collect(config('training-academy.default_paths', []))
            ->values()
            ->map(function (array $path, int $index): TrainingPath {
                return TrainingPath::query()->updateOrCreate(
                    ['code' => $path['code']],
                    [
                        'name' => $path['name'],
                        'description' => $path['description'],
                        'audience' => $path['audience'],
                        'sort_order' => ($index + 1) * 10,
                        'is_active' => true,
                    ],
                );
            });

        $categories = [
            ['name' => 'Prospecting', 'slug' => 'prospecting', 'description' => 'Prospecting fundamentals and activity systems.'],
            ['name' => 'Presentation Skills', 'slug' => 'presentation-skills', 'description' => 'Presentation structure, delivery, and follow-up.'],
            ['name' => 'Leadership', 'slug' => 'leadership', 'description' => 'Leadership, coaching, and team development.'],
            ['name' => 'Compliance', 'slug' => 'compliance', 'description' => 'Compliance, ethics, and professional standards.'],
        ];

        $createdCategories = collect($categories)->mapWithKeys(function (array $category): array {
            $record = TrainingCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'sort_order' => 0,
                ],
            );

            return [$category['slug'] => $record];
        });

        $courses = [
            [
                'category' => 'prospecting',
                'path' => 'new-associate',
                'title' => 'Prospecting Fundamentals',
                'slug' => 'prospecting-fundamentals',
                'description' => 'Build a consistent prospecting rhythm and activity pipeline.',
                'course_type' => 'video',
                'difficulty' => 'beginner',
                'duration_minutes' => 90,
                'is_featured' => true,
                'lessons' => [
                    ['title' => 'Welcome to Prospecting', 'lesson_type' => 'video'],
                    ['title' => 'Daily Activity Planning', 'lesson_type' => 'document'],
                    ['title' => 'Prospecting Worksheet', 'lesson_type' => 'interactive'],
                ],
            ],
            [
                'category' => 'presentation-skills',
                'path' => 'new-associate',
                'title' => 'Presentation Mastery',
                'slug' => 'presentation-mastery',
                'description' => 'Structure compelling client presentations and follow-up.',
                'course_type' => 'interactive',
                'difficulty' => 'intermediate',
                'duration_minutes' => 120,
                'is_featured' => true,
                'lessons' => [
                    ['title' => 'Presentation Framework', 'lesson_type' => 'video'],
                    ['title' => 'Objection Handling Basics', 'lesson_type' => 'article'],
                ],
            ],
            [
                'category' => 'leadership',
                'path' => 'agency-owner',
                'title' => 'Leadership Essentials',
                'slug' => 'leadership-essentials',
                'description' => 'Lead teams, retain talent, and build agency culture.',
                'course_type' => 'certification',
                'difficulty' => 'advanced',
                'duration_minutes' => 180,
                'is_featured' => true,
                'lessons' => [
                    ['title' => 'Leadership Mindset', 'lesson_type' => 'video'],
                    ['title' => 'Team Accountability Systems', 'lesson_type' => 'document'],
                ],
            ],
            [
                'category' => 'compliance',
                'path' => 'licensing',
                'title' => 'Compliance Foundations',
                'slug' => 'compliance-foundations',
                'description' => 'Core compliance expectations for financial professionals.',
                'course_type' => 'document',
                'difficulty' => 'beginner',
                'duration_minutes' => 60,
                'is_featured' => false,
                'lessons' => [
                    ['title' => 'Ethics and Suitability', 'lesson_type' => 'document'],
                ],
            ],
        ];

        foreach ($courses as $index => $courseData) {
            $category = $createdCategories->get($courseData['category']);
            abort_unless($category, 500);

            $module = TrainingModule::query()->updateOrCreate(
                ['slug' => $courseData['slug']],
                [
                    'training_category_id' => $category->id,
                    'title' => $courseData['title'],
                    'description' => $courseData['description'],
                    'sort_order' => ($index + 1) * 10,
                    'is_published' => true,
                    'status' => 'published',
                    'course_type' => $courseData['course_type'],
                    'difficulty' => $courseData['difficulty'],
                    'duration_minutes' => $courseData['duration_minutes'],
                    'is_featured' => $courseData['is_featured'],
                    'sequential_required' => true,
                ],
            );

            foreach ($courseData['lessons'] as $lessonIndex => $lessonData) {
                TrainingLesson::query()->updateOrCreate(
                    [
                        'training_module_id' => $module->id,
                        'title' => $lessonData['title'],
                    ],
                    [
                        'lesson_type' => $lessonData['lesson_type'],
                        'sort_order' => ($lessonIndex + 1) * 10,
                        'is_required' => true,
                        'content' => 'Academy lesson content placeholder for '.$lessonData['title'].'.',
                    ],
                );
            }

            $path = $paths->firstWhere('code', $courseData['path']);
            if ($path) {
                $path->modules()->syncWithoutDetaching([
                    $module->id => [
                        'sort_order' => ($index + 1) * 10,
                        'is_required' => true,
                    ],
                ]);
            }

            $assessment = Assessment::query()->updateOrCreate(
                [
                    'training_module_id' => $module->id,
                    'title' => $module->title.' Assessment',
                ],
                [
                    'description' => 'Knowledge check for '.$module->title.'.',
                    'passing_score' => 80,
                    'is_published' => true,
                ],
            );

            $this->seedAssessmentQuestions($assessment, $courseData['slug']);
        }

        $badges = [
            ['code' => 'first-course', 'name' => 'First Course Completed', 'level' => 'bronze', 'points' => 10],
            ['code' => 'prospecting-certified', 'name' => 'Prospecting Certified', 'level' => 'silver', 'points' => 25],
            ['code' => 'presentation-expert', 'name' => 'Presentation Expert', 'level' => 'gold', 'points' => 50],
            ['code' => 'cfm-certified', 'name' => 'CFM Certified', 'level' => 'platinum', 'points' => 100],
            ['code' => 'dedicated-learner', 'name' => 'Dedicated Learner', 'level' => 'silver', 'points' => 30, 'description' => 'Completed three academy courses.'],
            ['code' => 'assessment-ace', 'name' => 'Assessment Ace', 'level' => 'gold', 'points' => 40, 'description' => 'Scored 100% on an academy assessment.'],
            ['code' => 'path-graduate', 'name' => 'Path Graduate', 'level' => 'gold', 'points' => 35, 'description' => 'Completed a full learning path.'],
            ['code' => 'learning-streak-3', 'name' => '3-Day Learning Streak', 'level' => 'bronze', 'points' => 15, 'description' => 'Completed lessons three days in a row.'],
            ['code' => 'learning-streak-7', 'name' => '7-Day Learning Streak', 'level' => 'silver', 'points' => 30, 'description' => 'Completed lessons seven days in a row.'],
            ['code' => 'learning-streak-14', 'name' => '14-Day Learning Streak', 'level' => 'gold', 'points' => 60, 'description' => 'Completed lessons fourteen days in a row.'],
        ];

        foreach ($badges as $badge) {
            TrainingBadge::query()->updateOrCreate(
                ['code' => $badge['code']],
                [
                    'name' => $badge['name'],
                    'description' => $badge['description'] ?? $badge['name'].' achievement badge.',
                    'level' => $badge['level'],
                    'points' => $badge['points'],
                    'is_active' => true,
                ],
            );
        }

        $prospectingModule = TrainingModule::query()->where('slug', 'prospecting-fundamentals')->first();
        if ($prospectingModule) {
            $prospectingAssessment = Assessment::query()
                ->where('training_module_id', $prospectingModule->id)
                ->first();

            TrainingCertification::query()->updateOrCreate(
                ['code' => 'prospecting-certification'],
                [
                    'name' => 'Prospecting Certification',
                    'description' => 'Awarded after completing Prospecting Fundamentals and passing the assessment.',
                    'training_module_id' => $prospectingModule->id,
                    'assessment_id' => $prospectingAssessment?->id,
                    'required_score' => 80,
                    'mentor_approval_required' => false,
                    'is_active' => true,
                ],
            );
        }

        $leadershipModule = TrainingModule::query()->where('slug', 'leadership-essentials')->first();
        if ($leadershipModule) {
            $leadershipAssessment = Assessment::query()
                ->where('training_module_id', $leadershipModule->id)
                ->first();

            TrainingCertification::query()->updateOrCreate(
                ['code' => 'leadership-certification'],
                [
                    'name' => 'Leadership Certification',
                    'description' => 'Awarded after completing Leadership Essentials with mentor sign-off.',
                    'training_module_id' => $leadershipModule->id,
                    'assessment_id' => $leadershipAssessment?->id,
                    'required_score' => 80,
                    'mentor_approval_required' => true,
                    'is_active' => true,
                ],
            );
        }

        $this->seedCoachingSessions();
    }

    private function seedCoachingSessions(): void
    {
        $this->ensureTrainingCalendarTypes();

        $instructor = \App\Models\User::query()->role('certified-field-mentor')->first()
            ?? \App\Models\User::query()->role('super-admin')->first();

        if (! $instructor) {
            $instructor = \App\Models\User::factory()->create([
                'name' => 'Academy CFM',
                'email' => 'academy-cfm@gwtracks.test',
            ]);
            $instructor->assignRole('certified-field-mentor');
        }

        $calendar = app(\App\Services\Training\TrainingCalendarService::class);

        $sessions = [
            [
                'title' => 'Weekly FAP Coaching Lab',
                'description' => 'Group coaching session for field activity planning, debriefs, and accountability.',
                'session_type' => 'live',
                'starts_at' => now()->addDays(7)->setTime(18, 0),
                'ends_at' => now()->addDays(7)->setTime(19, 0),
                'capacity' => 20,
            ],
            [
                'title' => 'Field Observation Debrief',
                'description' => 'Review field observation outcomes and next-step coaching plans.',
                'session_type' => 'field',
                'starts_at' => now()->addDays(14)->setTime(12, 0),
                'ends_at' => now()->addDays(14)->setTime(13, 0),
                'capacity' => 12,
            ],
        ];

        foreach ($sessions as $payload) {
            $session = \App\Models\TrainingSession::query()->updateOrCreate(
                ['title' => $payload['title']],
                [
                    'description' => $payload['description'],
                    'session_type' => $payload['session_type'],
                    'instructor_id' => $instructor->id,
                    'starts_at' => $payload['starts_at'],
                    'ends_at' => $payload['ends_at'],
                    'capacity' => $payload['capacity'],
                    'is_active' => true,
                ],
            );

            $calendar->syncSessionToCalendar($session);
        }
    }

    private function ensureTrainingCalendarTypes(): void
    {
        $trainingCategory = CalendarCategory::query()->updateOrCreate(
            ['slug' => 'training'],
            [
                'name' => 'Training',
                'color' => '#2563EB',
                'icon' => 'graduation-cap',
                'sort_order' => 30,
            ],
        );

        $fieldCategory = CalendarCategory::query()->updateOrCreate(
            ['slug' => 'field-apprenticeship'],
            [
                'name' => 'Field Apprenticeship',
                'color' => '#9333EA',
                'icon' => 'briefcase',
                'sort_order' => 60,
            ],
        );

        $types = [
            ['name' => 'Training Session', 'slug' => 'training-session', 'category' => $trainingCategory, 'color' => '#2563EB'],
            ['name' => 'Recorded Webinar Review', 'slug' => 'recorded-webinar-review', 'category' => $trainingCategory, 'color' => '#1D4ED8'],
            ['name' => 'Field Observation', 'slug' => 'field-observation', 'category' => $fieldCategory, 'color' => '#7E22CE'],
        ];

        foreach ($types as $index => $type) {
            CalendarEventType::query()->updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'calendar_category_id' => $type['category']->id,
                    'name' => $type['name'],
                    'color' => $type['color'],
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedAssessmentQuestions(Assessment $assessment, string $courseSlug): void
    {
        $banks = [
            'prospecting-fundamentals' => [
                [
                    'question' => 'What is the primary goal of daily activity planning?',
                    'type' => 'multiple_choice',
                    'answers' => [
                        ['answer' => 'Building consistent prospecting activity', 'is_correct' => true],
                        ['answer' => 'Waiting for warm referrals only', 'is_correct' => false],
                        ['answer' => 'Skipping follow-up to save time', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Prospecting should be scheduled as a daily habit.',
                    'type' => 'true_false',
                    'answers' => [
                        ['answer' => 'True', 'is_correct' => true],
                        ['answer' => 'False', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'Which activity belongs in a prospecting rhythm?',
                    'type' => 'multiple_choice',
                    'answers' => [
                        ['answer' => 'Consistent outreach and follow-up', 'is_correct' => true],
                        ['answer' => 'Only working when leads appear', 'is_correct' => false],
                        ['answer' => 'Avoiding new conversations', 'is_correct' => false],
                    ],
                ],
            ],
            'compliance-foundations' => [
                [
                    'question' => 'Ethics and suitability require putting the client\'s interest first.',
                    'type' => 'true_false',
                    'answers' => [
                        ['answer' => 'True', 'is_correct' => true],
                        ['answer' => 'False', 'is_correct' => false],
                    ],
                ],
                [
                    'question' => 'What should guide product recommendations?',
                    'type' => 'multiple_choice',
                    'answers' => [
                        ['answer' => 'Client needs and suitability', 'is_correct' => true],
                        ['answer' => 'Highest commission products', 'is_correct' => false],
                        ['answer' => 'Whatever is easiest to sell', 'is_correct' => false],
                    ],
                ],
            ],
        ];

        $questions = $banks[$courseSlug] ?? [
            [
                'question' => 'I understand the key concepts covered in this course.',
                'type' => 'true_false',
                'answers' => [
                    ['answer' => 'True', 'is_correct' => true],
                    ['answer' => 'False', 'is_correct' => false],
                ],
            ],
        ];

        foreach ($questions as $index => $questionData) {
            $question = Question::query()->updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'question' => $questionData['question'],
                ],
                [
                    'type' => $questionData['type'],
                    'sort_order' => ($index + 1) * 10,
                ],
            );

            foreach ($questionData['answers'] as $answerData) {
                Answer::query()->updateOrCreate(
                    [
                        'question_id' => $question->id,
                        'answer' => $answerData['answer'],
                    ],
                    [
                        'is_correct' => $answerData['is_correct'],
                    ],
                );
            }
        }
    }
}
