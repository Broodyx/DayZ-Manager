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
            $line = $key.' = "'.addcslashes((string) $value, '"').'";';
            if (preg_match($pattern, $content)) {
                $content = (string) preg_replace($pattern, $line, $content, 1);
            } else {
                $content .= "\n".$line;
            }
        }

        return rtrim($content)."\n";
    }

    public function validate(array $values): void
    {
        if (isset($values['maxPlayers']) && ((int) $values['maxPlayers'] < 1 || (int) $values['maxPlayers'] > 128)) {
            throw new RuntimeException('maxPlayers musí být v rozsahu 1–128.');
        }
        foreach (['enableWhitelist', 'verifySignatures', 'forceSameBuild', 'disableVoN', 'disable3rdPerson'] as $key) {
            if (isset($values[$key]) && ! in_array((string) $values[$key], ['0', '1', 'true', 'false'], true)) {
                throw new RuntimeException($key.' musí být 0/1 nebo true/false.');
            }
        }
    }
}
