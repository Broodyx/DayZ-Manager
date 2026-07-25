<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ConfigurationFileStorage
{
    private const ALLOWED = [
        'xml' => ['application/xml', 'text/xml', 'text/plain'],
        'json' => ['application/json', 'text/plain'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'cfg' => ['text/plain', 'application/octet-stream', 'application/x-config'],
        'txt' => ['text/plain', 'application/octet-stream'],
    ];

    public function store(UploadedFile $file, int $projectId): StoredConfiguration
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType() ?? '';
        $maxKilobytes = (int) config('dayz.max_upload_size', 10240);

        $isTextConfig = in_array($extension, ['cfg', 'txt'], true);
        if (! isset(self::ALLOWED[$extension]) || (! $isTextConfig && ! in_array($mime, self::ALLOWED[$extension], true))) {
            throw ValidationException::withMessages(['file' => 'Povolené jsou XML, JSON, CFG, TXT nebo ZIP soubory.']);
        }
        if (($file->getSize() ?: 0) > $maxKilobytes * 1024) {
            throw ValidationException::withMessages(['file' => 'The uploaded file is too large.']);
        }

        $hash = hash_file('sha256', $file->getRealPath());
        $path = "{$projectId}/imports/".Str::uuid().'.'.$extension;
        Storage::disk('dayz')->putFileAs(dirname($path), $file, basename($path));

        return new StoredConfiguration($path, $hash, basename($file->getClientOriginalName()));
    }
}
