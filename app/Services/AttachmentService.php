<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    // Security: allowlist of permitted MIME types
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'text/plain',
        'application/zip',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    // Security: max file size in bytes (10 MB)
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024;

    // Security: disk that stores files outside of web root
    private const STORAGE_DISK = 'local';

    // Security: directory outside public/
    private const STORAGE_PATH = 'attachments';

    public function upload(UploadedFile $file, Task $task, User $uploader): Attachment
    {
        // Security: validate MIME type against server-side allowlist (not client-provided content type)
        $detectedMime = $file->getMimeType();
        if (! in_array($detectedMime, self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException("File type '{$detectedMime}' is not allowed.");
        }

        // Security: enforce file size limit
        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            throw new \InvalidArgumentException('File size exceeds the 10MB limit.');
        }

        // Security: generate a random UUID filename to prevent path traversal and filename collisions
        $extension  = $file->getClientOriginalExtension();
        $storedName = Str::uuid()->toString() . '.' . strtolower($extension);

        // Security: store outside web root (storage/app/attachments, not public/)
        $path = $file->storeAs(self::STORAGE_PATH, $storedName, self::STORAGE_DISK);

        // Security: store original filename in DB for display only, never use for file operations
        $originalName = basename($file->getClientOriginalName());

        return Attachment::create([
            'task_id'       => $task->id,
            'uploaded_by'   => $uploader->id,
            'original_name' => $originalName,
            'stored_name'   => $storedName,
            'disk'          => self::STORAGE_DISK,
            'path'          => $path,
            'mime_type'     => $detectedMime,
            'size_bytes'    => $file->getSize(),
        ]);
    }

    /**
     * Stream file download to authorized user.
     * Security: always use stored path from DB, never user-provided path.
     */
    public function download(Attachment $attachment): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Security: use path from DB, sanitized at upload time
        if (! Storage::disk(self::STORAGE_DISK)->exists($attachment->path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk(self::STORAGE_DISK)->download(
            $attachment->path,
            $attachment->original_name,
            [
                // Security: force download instead of inline display
                'Content-Disposition' => 'attachment; filename="' . addslashes($attachment->original_name) . '"',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Type'           => $attachment->mime_type,
            ]
        );
    }

    public function delete(Attachment $attachment): void
    {
        Storage::disk(self::STORAGE_DISK)->delete($attachment->path);
        $attachment->delete();
    }
}
