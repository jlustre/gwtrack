<?php

namespace Database\Seeders;

use App\Models\PortalResource;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResourceVideoSeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = User::query()->value('id');

        $videos = [
            [
                'title' => 'Welcome to GWTracks',
                'description' => 'A quick portal orientation covering navigation, profile setup, and your first-week priorities.',
                'category' => 'onboarding',
                'sort_order' => 10,
                'url' => 'https://www.youtube.com/watch?v=LXb3EKWsInQ',
                'is_featured' => true,
            ],
            [
                'title' => 'Prospecting Fundamentals',
                'description' => 'Core prospecting habits, daily activity targets, and how to use the CRM pipeline effectively.',
                'category' => 'training',
                'sort_order' => 20,
                'url' => 'https://www.youtube.com/watch?v=ysz5S6PUM-U',
                'is_featured' => true,
            ],
            [
                'title' => 'Leadership Message: Building Momentum',
                'description' => 'Agency leadership shares how to stay consistent, coach your team, and celebrate small wins.',
                'category' => 'leadership',
                'sort_order' => 30,
                'url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                'is_featured' => false,
            ],
            [
                'title' => 'Whole Life Product Overview',
                'description' => 'Product education clip covering positioning, client conversations, and common objections.',
                'category' => 'product',
                'sort_order' => 40,
                'url' => 'https://www.youtube.com/watch?v=ScMzIvxBSi4',
                'is_featured' => false,
            ],
            [
                'title' => 'Opportunity Presentation Walkthrough',
                'description' => 'Recruiting presentation flow for inviting candidates to explore the associate opportunity.',
                'category' => 'recruiting',
                'sort_order' => 50,
                'url' => 'https://www.youtube.com/watch?v=EngW7tLk6Rs',
                'is_featured' => true,
            ],
            [
                'title' => 'Compliance Essentials',
                'description' => 'Short refresher on documentation, client privacy, and field compliance expectations.',
                'category' => 'training',
                'sort_order' => 60,
                'url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                'is_featured' => false,
            ],
        ];

        foreach ($videos as $video) {
            PortalResource::query()->updateOrCreate(
                ['title' => $video['title'], 'type' => 'video'],
                [
                    'created_by' => $creatorId,
                    'description' => $video['description'],
                    'category' => $video['category'],
                    'sort_order' => $video['sort_order'],
                    'url' => $video['url'],
                    'type' => 'video',
                    'is_featured' => $video['is_featured'],
                    'is_published' => true,
                ],
            );
        }
    }
}
