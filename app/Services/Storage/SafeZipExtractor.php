<?php

namespace App\Services\Storage;

use RuntimeException;
use ZipArchive;

final class SafeZipExtractor
{
    private const ALLOWED_EXTENSIONS = ['xml', 'json'];

    public function extract(string $archivePath, string $destination): array
    {
        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('Unable to open ZIP archive.');
        }

        $files = [];
        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = str_replace('\\', '/', $zip->getNameIndex($index));
                if ($name === '' || str_ends_with($name, '/')) {
                    continue;
                }
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (str_contains($name, '../') || str_starts_with($name, '/') || preg_match('/^[a-z]:/i', $name)
                    || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    throw new RuntimeException("Unsafe ZIP entry: {$name}");
                }
                $target = $destination.DIRECTORY_SEPARATOR.$name;
                if (! is_dir(dirname($target))) {
                    mkdir(dirname($target), 0750, true);
                }
                $stream = $zip->getStream($zip->getNameIndex($index));
                $output = fopen($target, 'wb');
                if ($stream === false || $output === false) {
                    throw new RuntimeException("Unable to extract ZIP entry: {$name}");
                }
                stream_copy_to_stream($stream, $output);
                fclose($stream);
                fclose($output);
                $files[] = $target;
            }
        } finally {
            $zip->close();
        }

        return $files;
    }
}
