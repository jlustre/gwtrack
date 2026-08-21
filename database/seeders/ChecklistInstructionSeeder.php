<?php

namespace Database\Seeders;

use App\Models\ChecklistInstruction;
use App\Models\ChecklistType;
use Illuminate\Database\Seeder;

class ChecklistInstructionSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            'onboarding' => [
                'instructions' => '<p><strong>Welcome to onboarding.</strong></p><ul><li>Complete your profile and upload required documents.</li><li>Review the welcome packet with your sponsor.</li><li>Schedule your orientation session before moving to licensing.</li></ul>',
                'doc_link' => '/resources/documents/welcome-packet',
                'other_link' => 'https://example.com/onboarding-video',
                'sort_order' => 10,
            ],
            'licensing' => [
                'instructions' => '<p>Use this checklist to track provincial or state licensing milestones.</p><p>Contact your agency owner if a course or exam step is missing from your list.</p>',
                'doc_link' => '/resources/documents/licensing-guide',
                'other_link' => null,
                'sort_order' => 10,
            ],
            'contracting' => [
                'instructions' => '<p><strong>Carrier contracting</strong></p><p>Complete contracting after licensing is approved. Work with your sponsor and agency contracting desk to select carriers, submit packages, finish product training, and confirm appointments before submitting business.</p><ul><li>Keep license, E&amp;O, and hierarchy details current.</li><li>Record agent or producer numbers when appointments are approved.</li><li>Do not submit production until the selected carriers show as ready.</li></ul>',
                'doc_link' => '/resources/documents/contracting-guide',
                'other_link' => '/crm/funnels/contracting',
                'sort_order' => 10,
            ],
            'fap' => [
                'instructions' => '<p><strong>Field Apprenticeship Program</strong></p><p>Work through each step with your assigned CFM. Mark items complete only after the mentor confirms the activity.</p>',
                'doc_link' => null,
                'other_link' => '/resources/fap-overview.pdf',
                'sort_order' => 10,
            ],
            'cfm-training' => [
                'instructions' => '<p>Complete all required CFM training modules before requesting certification review.</p>',
                'doc_link' => '/cfm-training',
                'other_link' => null,
                'sort_order' => 10,
            ],
            'cfm-mentoring' => [
                'instructions' => '<p>Track mentoring milestones for each assigned trainee. Use the full checklist page to update progress and add notes.</p>',
                'doc_link' => null,
                'other_link' => '/cfm/portal',
                'sort_order' => 10,
            ],
        ];

        foreach ($samples as $code => $payload) {
            $type = ChecklistType::query()->where('code', $code)->first();

            if (! $type) {
                continue;
            }

            ChecklistInstruction::query()->updateOrCreate(
                [
                    'checklist_type_id' => $type->id,
                    'sort_order' => $payload['sort_order'],
                ],
                [
                    'instructions' => $payload['instructions'],
                    'doc_link' => $payload['doc_link'],
                    'other_link' => $payload['other_link'],
                    'is_active' => true,
                ],
            );
        }
    }
}
