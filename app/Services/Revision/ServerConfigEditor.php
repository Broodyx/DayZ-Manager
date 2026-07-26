<?php

namespace App\Services\Revision;

use RuntimeException;

final class ServerConfigEditor
{
    /** @return array<string, string> */
    public function parse(string $content): array
    {
        $values = [];
        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            $line = trim((string) preg_replace('/\/\/.*$/', '', $line));
            if ($line === '' || ! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $values[$key] = trim($value, " \t\";");
        }

        return $values;
    }

    public function update(string $content, array $updates): string
    {
        $this->validate($updates);
        foreach ($updates as $key => $value) {
            $pattern = '/^\s*'.preg_quote($key, '/').'\s*=.*$/mi';
            $line = $key.' = '.$this->formatValue((string) $value).';';
            if (preg_match($pattern, $content)) {
                $content = (string) preg_replace($pattern, $line, $content, 1);
            } else {
                $content .= "\n".$line;
            }
        }

        return rtrim($content)."\n";
    }

    private function formatValue(string $value): string
    {
        $trimmed = trim($value);
        if (preg_match('/^-?\d+(?:\.\d+)?$/', $trimmed) || in_array(strtolower($trimmed), ['true', 'false'], true)) {
            return $trimmed;
        }
        if ((str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}'))
            || (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'))) {
            return $trimmed;
        }

        return '"'.addcslashes($trimmed, "\\\"").'"';
    }

    public function validate(array $values): void
    {
        if (isset($values['maxPlayers']) && ((int) $values['maxPlayers'] < 1 || (int) $values['maxPlayers'] > 128)) {
            throw new RuntimeException('maxPlayers musí být v rozsahu 1–128.');
        }
        foreach (['enableWhitelist', 'forceSameBuild', 'disableVoN', 'disable3rdPerson', 'disableCrosshair',
            'disablePersonalLight', 'serverTimePersistent', 'storageAutoFix', 'disableBaseDamage',
            'disableContainerDamage', 'disableRespawnDialog', 'enableCfgGameplayFile', 'shotValidation'] as $key) {
            if (isset($values[$key]) && ! in_array((string) $values[$key], ['0', '1', 'true', 'false'], true)) {
                throw new RuntimeException($key.' musí být 0/1 nebo true/false.');
            }
        }

        // DayZ uses three signature-verification modes: 0 (off), 1 (verify
        // signatures) and 2 (strict/required signatures). Some server
        // templates also expose the boolean spellings, so accept both forms.
        if (isset($values['verifySignatures']) && ! in_array((string) $values['verifySignatures'], ['0', '1', '2', 'true', 'false'], true)) {
            throw new RuntimeException('verifySignatures musí být 0, 1, 2 nebo true/false.');
        }
        foreach (['serverTimeAcceleration', 'serverNightTimeAcceleration'] as $key) {
            if (isset($values[$key]) && (! is_numeric($values[$key]) || (float) $values[$key] < 0.1 || (float) $values[$key] > 64)) {
                throw new RuntimeException($key.' musí být v rozsahu 0.1–64.');
            }
        }
        foreach (['steamport', 'steamqueryport', 'clientPort'] as $key) {
            if (isset($values[$key]) && ((int) $values[$key] < 1024 || (int) $values[$key] > 65535)) {
                throw new RuntimeException($key.' musí být v rozsahu 1024–65535.');
            }
        }
        if (isset($values['vonCodecQuality']) && ((int) $values['vonCodecQuality'] < 0 || (int) $values['vonCodecQuality'] > 20)) {
            throw new RuntimeException('vonCodecQuality musí být v rozsahu 0–20.');
        }
    }
}
