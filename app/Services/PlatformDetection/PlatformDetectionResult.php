<?php

namespace App\Services\PlatformDetection;

final readonly class PlatformDetectionResult
{
    public function __construct(
        public string $platform,
        public int $confidence,
        public array $reasons = [],
        public array $warnings = [],
    ) {}
}
