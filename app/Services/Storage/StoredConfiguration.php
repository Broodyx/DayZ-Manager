<?php

namespace App\Services\Storage;

final readonly class StoredConfiguration
{
    public function __construct(
        public string $path,
        public string $sha256,
        public string $originalFilename,
    ) {}
}
