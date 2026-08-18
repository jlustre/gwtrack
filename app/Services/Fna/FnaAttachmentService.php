<?php

namespace App\Services\Fna;

use App\Models\FnaAttachment;
use App\Models\FnaRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FnaAttachmentService
{
    /** @var list<string> */
    public const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public const MAX_BYTES = 10 * 1024 * 1024;

    public function __construct(
        private FnaRecordService $records,
    ) {}

    public function upload(FnaRecord $fna, User $user, UploadedFile $file, ?string $category = null): FnaAttachment
    {
        if ($file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages([
                'attachment' => 'Attachments may not exceed 10 MB.',
            ]);
        }

        $mime = $file->getMimeType() ?? $file->getClientMimeType();

        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'attachment' => 'File type not allowed. Use PDF, images, or Word documents.',
            ]);
        }

        $directory = 'fna-attachments/'.$fna->id;
        $path = $file->store($directory, 'local');

        $attachment = $fna->attachments()->create([
            'uploaded_by_user_id' => $user->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'size_bytes' => (int) $file->getSize(),
            'category' => $category,
        ]);

        $this->records->logActivity(
            $fna,
            $user,
            'attachment_uploaded',
            'Attachment uploaded: '.$attachment->original_name,
            ['attachment_id' => $attachment->id],
        );

        return $attachment;
    }

    public function delete(FnaAttachment $attachment, User $user): void
    {
        $fna = $attachment->fnaRecord;

        if ($attachment->disk && $attachment->path) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }

        $name = $attachment->original_name;
        $attachment->delete();

        $this->records->logActivity(
            $fna,
            $user,
            'attachment_deleted',
            'Attachment removed: '.$name,
        );
    }
}
