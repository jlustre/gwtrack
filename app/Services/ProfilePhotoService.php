<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfilePhotoService
{
    public const DISK = 'public';

    public function update(User $user, UploadedFile $file): Profile
    {
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);
        $previousPath = $profile->profile_photo_path;

        $path = $file->store(
            'profile-photos/'.$user->id,
            self::DISK
        );

        $profile->forceFill(['profile_photo_path' => $path])->save();

        if ($previousPath && $previousPath !== $path) {
            $this->deleteStoredFile($previousPath);
        }

        return $profile->fresh();
    }

    public function delete(User $user): void
    {
        $profile = $user->profile;

        if (! $profile?->profile_photo_path) {
            return;
        }

        $this->deleteStoredFile($profile->profile_photo_path);

        $profile->forceFill(['profile_photo_path' => null])->save();
    }

    public function deleteStoredFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);

        $directory = Str::beforeLast($path, '/');
        if ($directory && $directory !== 'profile-photos' && empty(Storage::disk(self::DISK)->files($directory))) {
            Storage::disk(self::DISK)->deleteDirectory($directory);
        }
    }

    public function stageForPreview(User $user, UploadedFile $file): string
    {
        $this->clearStagedPreview($user);

        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
        $path = $file->storeAs(
            $this->stagingDirectory($user),
            'pending.'.$extension,
            self::DISK,
        );

        session()->put('profile_completion_staged_photo_path', $path);

        return $this->stagedPreviewUrl($user) ?? '';
    }

    public function hasStagedPreview(User $user): bool
    {
        $path = session('profile_completion_staged_photo_path');

        if (! is_string($path) || ! str_starts_with($path, $this->stagingDirectory($user))) {
            return false;
        }

        return Storage::disk(self::DISK)->exists($path);
    }

    public function stagedPreviewUrl(User $user): ?string
    {
        if (! $this->hasStagedPreview($user)) {
            return null;
        }

        $path = session('profile_completion_staged_photo_path');

        return route('profile.completion-photo-preview', [
            'v' => crc32($path.(string) Storage::disk(self::DISK)->lastModified($path)),
        ]);
    }

    public function stagedPreviewPath(User $user): ?string
    {
        $path = session('profile_completion_staged_photo_path');

        if (! is_string($path) || ! str_starts_with($path, $this->stagingDirectory($user))) {
            return null;
        }

        if (! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return $path;
    }

    public function clearStagedPreview(User $user): void
    {
        Storage::disk(self::DISK)->deleteDirectory($this->stagingDirectory($user));
        session()->forget('profile_completion_staged_photo_path');
    }

    public function applyStagedPreview(User $user, string $stagedPath): Profile
    {
        if (! str_starts_with($stagedPath, $this->stagingDirectory($user))) {
            return $user->profile()->firstOrCreate(['user_id' => $user->id]);
        }

        if (! Storage::disk(self::DISK)->exists($stagedPath)) {
            return $user->profile()->firstOrCreate(['user_id' => $user->id]);
        }

        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);
        $previousPath = $profile->profile_photo_path;

        $extension = pathinfo($stagedPath, PATHINFO_EXTENSION) ?: 'jpg';
        $finalPath = 'profile-photos/'.$user->id.'/'.Str::uuid().'.'.$extension;

        Storage::disk(self::DISK)->move($stagedPath, $finalPath);

        $profile->forceFill(['profile_photo_path' => $finalPath])->save();

        if ($previousPath && $previousPath !== $finalPath) {
            $this->deleteStoredFile($previousPath);
        }

        return $profile->fresh();
    }

    private function stagingDirectory(User $user): string
    {
        return 'profile-photo-staging/'.$user->id;
    }
}
