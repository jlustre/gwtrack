<?php

namespace Tests\Feature;

use App\Models\PortalResource;
use App\Models\User;
use Database\Seeders\RankSeeder;
use Database\Seeders\ResourceVideoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceVideosPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            ResourceVideoSeeder::class,
        ]);
    }

    public function test_member_can_view_video_library(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('resources.videos'))
            ->assertOk()
            ->assertSee('Video library', false)
            ->assertSee('Welcome to GWTrack', false)
            ->assertSee('Prospecting Fundamentals', false)
            ->assertDontSee('Video library coming soon', false);
    }

    public function test_video_preview_returns_embed_payload(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $video = PortalResource::query()->where('type', 'video')->where('title', 'Welcome to GWTrack')->firstOrFail();

        $this->actingAs($user)
            ->getJson(route('resources.videos.preview', $video))
            ->assertOk()
            ->assertJsonPath('title', 'Welcome to GWTrack')
            ->assertJsonPath('provider', 'youtube')
            ->assertJsonStructure(['embed_url', 'thumbnail_url']);
    }

    public function test_member_can_favorite_video(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $video = PortalResource::query()->where('type', 'video')->firstOrFail();

        $this->actingAs($user)
            ->post(route('resources.videos.favorite', $video))
            ->assertRedirect(route('resources.videos'))
            ->assertSessionHas('status', 'favorite-added');

        $this->assertTrue(
            $user->favoritePortalResources()->where('resources.id', $video->id)->exists()
        );
    }

    public function test_video_library_supports_category_filter(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('resources.videos', ['category' => 'recruiting']))
            ->assertOk()
            ->assertSee('Opportunity Presentation Walkthrough', false)
            ->assertDontSee('Prospecting Fundamentals', false);
    }
}
