<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Models\SupportTicketAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupportAttachmentService
{
    /**
     * @return array{path: string, file_name: string, file_type: string, file_size: int}
     */
    public function store(UploadedFile $file, string $subdirectory): array
    {
        $maxBytes = (int) config('support.attachment.max_bytes', 10 * 1024 * 1024);
        $allowedMimes = config('support.attachment.allowed_mimes', []);

        if ($file->getSize() > $maxBytes) {
            throw ValidationException::withMessages([
                'attachments' => 'Each file must be 10MB or smaller.',
            ]);
        }

        if (! in_array($file->getMimeType(), $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'attachments' => 'Unsupported file type uploaded.',
            ]);
        }

        $disk = (string) config('support.attachment.disk', 'local');
        $directory = trim((string) config('support.attachment.directory', 'support-attachments'), '/');
        $safeName = Str::uuid()->toString().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = $file->getClientOriginalExtension();
        $storedName = $extension !== '' ? "{$safeName}.{$extension}" : $safeName;
        $path = $file->storeAs("{$directory}/{$subdirectory}", $storedName, $disk);

        return [
            'path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => (string) $file->getMimeType(),
            'file_size' => (int) $file->getSize(),
        ];
    }

    public function attachToModel(object $model, UploadedFile $file): SupportTicketAttachment
    {
        $stored = $this->store($file, class_basename($model)."/{$model->getKey()}");

        return $model->attachments()->create([
            'file_path' => $stored['path'],
            'file_name' => $stored['file_name'],
            'file_type' => $stored['file_type'],
            'file_size' => $stored['file_size'],
        ]);
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function attachMany(object $model, array $files): void
    {
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $this->attachToModel($model, $file);
            }
        }
    }
}
