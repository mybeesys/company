<?php

namespace Modules\Accounting\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class JournalEntryImportStorage
{
    public const DIRECTORY = 'journal-import';

    public static function store(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'xls');
        if (! in_array($extension, ['xls', 'xlsx'], true)) {
            throw new RuntimeException('Invalid file extension.');
        }

        Storage::disk('local')->makeDirectory(self::DIRECTORY);

        $relativePath = self::DIRECTORY.'/'.Str::uuid().'.'.$extension;
        $stored = $file->storeAs(self::DIRECTORY, basename($relativePath), 'local');

        if (! is_string($stored) || $stored === '') {
            throw new RuntimeException('Could not store uploaded file.');
        }

        return $stored;
    }

    public static function fullPath(string $relativePath): string
    {
        return Storage::disk('local')->path($relativePath);
    }

    public static function exists(string $relativePath): bool
    {
        return Storage::disk('local')->exists($relativePath);
    }

    public static function delete(string $relativePath): void
    {
        if ($relativePath !== '') {
            Storage::disk('local')->delete($relativePath);
        }
    }
}
