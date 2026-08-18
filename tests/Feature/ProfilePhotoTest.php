<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use App\Services\ProfilePhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_upload_update_and_remove_profile_photo_without_orphans(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['name' => 'Photo Member']);

        $first = UploadedFile::fake()->image('first.jpg', 400, 400);
        $this->actingAs($user)
            ->post(route('profile.photo.update'), ['photo' => $first])
            ->assertRedirect(route('profile.edit', ['tab' => 'profile']));

        $user->refresh();
        $firstPath = $user->profile?->profile_photo_path;
        $this->assertNotNull($firstPath);
        Storage::disk('public')->assertExists($firstPath);

        $photoUrl = $user->fresh(['profile'])->profilePhotoUrl();
        $this->assertNotNull($photoUrl);
        $this->assertStringContainsString('storage/profile-photos/', $photoUrl);

        $this->actingAs($user)
            ->get(route('profile.edit', ['tab' => 'profile']))
            ->assertOk()
            ->assertSee('Update Profile Photo', false)
            ->assertSee('Remove Photo', false)
            ->assertSee($photoUrl, false);

        $second = UploadedFile::fake()->image('second.jpg', 400, 400);
        $this->actingAs($user)
            ->post(route('profile.photo.update'), ['photo' => $second])
            ->assertRedirect(route('profile.edit', ['tab' => 'profile']));

        $user->refresh();
        $secondPath = $user->profile?->profile_photo_path;
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        $this->actingAs($user)
            ->delete(route('profile.photo.destroy'))
            ->assertRedirect(route('profile.edit', ['tab' => 'profile']));

        $user->refresh();
        $this->assertNull($user->profile?->profile_photo_path);
        Storage::disk('public')->assertMissing($secondPath);
    }

    public function test_profile_deletion_removes_stored_photo_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('avatar.jpg')->store('profile-photos/'.$user->id, 'public');

        $profile = Profile::query()->create([
            'user_id' => $user->id,
            'profile_photo_path' => $path,
        ]);

        Storage::disk('public')->assertExists($path);

        app(ProfilePhotoService::class)->delete($user);

        Storage::disk('public')->assertMissing($path);
        $this->assertNull($profile->fresh()->profile_photo_path);
    }

    public function test_invalid_profile_photo_is_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.photo.update'), [
                'photo' => UploadedFile::fake()->create('notes.txt', 100, 'text/plain'),
            ])
            ->assertSessionHasErrors('photo');
    }
}
