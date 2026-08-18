<?php

namespace App\Support;

class TrackerChecklistConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function for(string $key): array
    {
        return match ($key) {
            'onboarding' => [
                'key' => 'onboarding',
                'typeCode' => 'onboarding',
                'eyebrow' => 'My Onboarding',
                'title' => 'Start strong with a clear path forward',
                'description' => 'Track your setup, sponsor alignment, licensing readiness, training start, and first field milestones.',
                'checklistTitle' => 'Onboarding Checklist',
                'checklistDescription' => 'Check items off as you complete them. Country-specific items appear only when they apply to your profile.',
                'emptyTitle' => 'No onboarding items available',
                'emptyDescription' => 'Your onboarding checklist will appear when active onboarding steps are available.',
                'itemCountLabel' => 'applicable items',
                'completedLabel' => 'Items completed',
                'graphLabel' => 'Overall onboarding',
                'requiredLabel' => 'Required completion',
                'statusPrefix' => 'onboarding',
                'indexRoute' => 'onboarding.index',
                'usesCountryFilter' => true,
            ],
            'licensing' => [
                'key' => 'licensing',
                'typeCode' => 'licensing',
                'eyebrow' => 'Licensing Tracker',
                'title' => 'Move from preparation to approved field readiness',
                'description' => 'Track licensing milestones, course progress, exam readiness, application status, and leadership confirmation.',
                'checklistTitle' => 'Licensing Checklist',
                'checklistDescription' => 'Submit completed licensing milestones for sponsor, mentor, or agency confirmation.',
                'emptyTitle' => 'No licensing items available',
                'emptyDescription' => 'Your licensing checklist will appear when active licensing steps are available.',
                'itemCountLabel' => 'licensing items',
                'completedLabel' => 'licensing milestones complete',
                'graphLabel' => 'Overall licensing',
                'requiredLabel' => 'Required licensing',
                'statusPrefix' => 'licensing',
                'indexRoute' => 'licensing.index',
                'usesCountryFilter' => false,
            ],
            'apprenticeship' => [
                'key' => 'apprenticeship',
                'typeCode' => 'fap',
                'groupLabel' => 'Field Apprenticeship Program',
                'eyebrow' => 'Field Apprenticeship',
                'title' => 'Build confidence through guided field activity',
                'description' => 'Track apprentice readiness, mentor-guided practice, supervised appointments, and FAP completion approval.',
                'checklistTitle' => 'Field Apprenticeship Checklist',
                'checklistDescription' => 'Submit FAP milestones for sponsor, CFM, trainer, or agency confirmation.',
                'emptyTitle' => 'No apprenticeship items available',
                'emptyDescription' => 'Your FAP checklist will appear when active apprenticeship steps are available.',
                'itemCountLabel' => 'FAP items',
                'completedLabel' => 'FAP milestones complete',
                'graphLabel' => 'Overall apprenticeship',
                'requiredLabel' => 'Required FAP completion',
                'statusPrefix' => 'apprenticeship',
                'indexRoute' => 'apprenticeship.index',
                'usesCountryFilter' => false,
            ],
            'cfm-training' => [
                'key' => 'cfm-training',
                'typeCode' => 'cfm-training',
                'eyebrow' => 'CFM Training',
                'title' => 'Prepare to mentor apprentices with excellence',
                'description' => 'Track Certified Field Mentor training modules, coaching standards, mentor documentation, and certification readiness.',
                'checklistTitle' => 'CFM Training Checklist',
                'checklistDescription' => 'Submit CFM training modules for sponsor, trainer, or agency confirmation.',
                'emptyTitle' => 'No CFM training items available',
                'emptyDescription' => 'Your CFM training checklist will appear when active training modules are available.',
                'itemCountLabel' => 'CFM training items',
                'completedLabel' => 'CFM modules complete',
                'graphLabel' => 'Overall CFM training',
                'requiredLabel' => 'Required CFM training',
                'statusPrefix' => 'cfm-training',
                'indexRoute' => 'cfm-training.index',
                'usesCountryFilter' => false,
            ],
            default => abort(404),
        };
    }

    public static function keyFromRoute(?string $routeName): string
    {
        return match ($routeName) {
            'onboarding.index' => 'onboarding',
            'licensing.index' => 'licensing',
            'apprenticeship.index' => 'apprenticeship',
            'cfm-training.index' => 'cfm-training',
            default => abort(404),
        };
    }
}
