<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class AnnouncementCategoryCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            ['code' => 'general', 'name' => 'General Announcement', 'icon' => 'megaphone', 'color' => '#475569', 'default_priority' => 'informational', 'requires_acknowledgement_default' => false, 'sort_order' => 10],
            ['code' => 'leadership', 'name' => 'Leadership Message', 'icon' => 'building-office', 'color' => '#2D3E50', 'default_priority' => 'important', 'requires_acknowledgement_default' => false, 'sort_order' => 20],
            ['code' => 'training', 'name' => 'Training Announcement', 'icon' => 'book-open', 'color' => '#2563EB', 'default_priority' => 'important', 'requires_acknowledgement_default' => false, 'sort_order' => 30],
            ['code' => 'licensing', 'name' => 'Licensing Announcement', 'icon' => 'document-check', 'color' => '#B45309', 'default_priority' => 'important', 'requires_acknowledgement_default' => true, 'sort_order' => 40],
            ['code' => 'fap', 'name' => 'FAP Announcement', 'icon' => 'academic-cap', 'color' => '#0F766E', 'default_priority' => 'important', 'requires_acknowledgement_default' => false, 'sort_order' => 50],
            ['code' => 'compliance', 'name' => 'Compliance Announcement', 'icon' => 'shield-exclamation', 'color' => '#DC2626', 'default_priority' => 'critical', 'requires_acknowledgement_default' => true, 'sort_order' => 60],
            ['code' => 'recognition', 'name' => 'Recognition', 'icon' => 'star', 'color' => '#84BB41', 'default_priority' => 'informational', 'requires_acknowledgement_default' => false, 'sort_order' => 70],
            ['code' => 'event', 'name' => 'Event Announcement', 'icon' => 'calendar-days', 'color' => '#0891B2', 'default_priority' => 'important', 'requires_acknowledgement_default' => false, 'sort_order' => 80],
            ['code' => 'product', 'name' => 'Product Announcement', 'icon' => 'cube', 'color' => '#7C3AED', 'default_priority' => 'informational', 'requires_acknowledgement_default' => false, 'sort_order' => 90],
            ['code' => 'campaign', 'name' => 'Campaign Announcement', 'icon' => 'flag', 'color' => '#84BB41', 'default_priority' => 'high', 'requires_acknowledgement_default' => false, 'sort_order' => 100],
            ['code' => 'emergency', 'name' => 'Emergency', 'icon' => 'exclamation-triangle', 'color' => '#991B1B', 'default_priority' => 'emergency', 'requires_acknowledgement_default' => true, 'sort_order' => 110],
        ];
    }

    public static function seed(): void
    {
        foreach (self::definitions() as $category) {
            DB::table('announcement_categories')->updateOrInsert(
                ['code' => $category['code']],
                array_merge($category, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }
    }
}
