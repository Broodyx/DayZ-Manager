<?php

namespace App\Services\PlatformDetection;

final class PlatformDetector
{
    public function detect(string $content, array $paths = []): PlatformDetectionResult
    {
        $haystack = strtolower($content."\n".implode("\n", $paths));
        $rules = [
            'Steam Workshop reference' => str_contains($haystack, 'steamcommunity.com/sharedfiles'),
            '-mod parameter' => preg_match('/(?:^|\s)-mod\s*=/i', $haystack) === 1,
            'PC mod directory' => preg_match('/(?:^|[\/\\\\])@[a-z0-9_.-]+/i', $haystack) === 1,
            'Known PC framework' => preg_match('/\b(cftools|community framework|dayz expansion)\b/i', $haystack) === 1,
        ];
        $reasons = array_keys(array_filter($rules));

        if ($reasons !== []) {
            return new PlatformDetectionResult(
                platform: 'steam',
                confidence: min(95, 65 + (count($reasons) - 1) * 10),
                reasons: $reasons,
            );
        }

        return new PlatformDetectionResult(
            platform: 'unknown',
            confidence: 25,
            reasons: ['No PC-only elements detected; configuration may be console-compatible.'],
            warnings: ['A common XML file alone cannot reliably distinguish PlayStation from Xbox.'],
        );
    }
}
