<?php

namespace App\Services\Revision;

use App\Services\Dayz\MapConfigurationReader;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Reads and writes the official DayZ Object Spawner JSON format — a top-level object with an
 * "Objects" array, each entry `{name, pos:[x,y,z], ypr:[yaw,pitch,roll], scale,
 * enableCEPersistency}` (see App\Services\Dayz\ConfigurationFieldMetadata for the field-level
 * help text already shown elsewhere in the app). This is a real, existing mod/console format —
 * never a custom invention — matching what ConfigurationCatalog's 'objects' area and
 * MapEditor's long-standing `pointTypeCatalog['custom']` placeholder both already point at.
 *
 * An object's identity within one revision is its position in the "Objects" array, addressed as
 * a `path` string "Objects[N]" — the same role `path` already plays for XML map points (an
 * xpath-like string), just JSON-shaped. This is index-based, not a stable ID: deleting object 2
 * shifts every later index down by one within that same save. That's an accepted limitation
 * shared with every other JSON position source this app already reads (see
 * MapConfigurationReader::jsonPositions(), which has the same property) — not a new one.
 */
final readonly class ObjectSpawnerJsonEditor
{
    private const PATH_PATTERN = '/^Objects\[(\d+)\]$/';

    public function supports(string $filename, string $content): bool
    {
        if (Str::is('*spawner*.json', strtolower(basename($filename)))) {
            return true;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) && is_array($decoded['Objects'] ?? null);
    }

    /** @return list<array{index:int,path:string,name:string,pos:array{0:float,1:float,2:float},ypr:array{0:float,1:float,2:float},scale:float,enableCEPersistency:bool}> */
    public function entries(string $content): array
    {
        $objects = $this->decodeObjects($content);

        return collect($objects)->values()->map(fn (array $object, int $index): array => [
            'index' => $index,
            'path' => "Objects[{$index}]",
            ...$this->normalize($object),
        ])->all();
    }

    /** @param array<string,mixed> $object */
    public function append(string $content, array $object): string
    {
        $decoded = $this->decode($content);
        $objects = is_array($decoded['Objects'] ?? null) ? $decoded['Objects'] : [];
        $objects[] = $this->normalize($object);
        $decoded['Objects'] = array_values($objects);

        return $this->encode($decoded);
    }

    /** @param array<string,mixed> $changes */
    public function updateAt(string $content, int $index, array $changes): string
    {
        $decoded = $this->decode($content);
        $objects = is_array($decoded['Objects'] ?? null) ? array_values($decoded['Objects']) : [];
        if (! array_key_exists($index, $objects)) {
            throw new RuntimeException("Objekt na indexu {$index} v Object Spawner JSON neexistuje.");
        }
        $objects[$index] = $this->normalize([...$objects[$index], ...$changes]);
        $decoded['Objects'] = $objects;

        return $this->encode($decoded);
    }

    public function removeAt(string $content, int $index): string
    {
        $decoded = $this->decode($content);
        $objects = is_array($decoded['Objects'] ?? null) ? array_values($decoded['Objects']) : [];
        if (! array_key_exists($index, $objects)) {
            throw new RuntimeException("Objekt na indexu {$index} v Object Spawner JSON neexistuje.");
        }
        unset($objects[$index]);
        $decoded['Objects'] = array_values($objects);

        return $this->encode($decoded);
    }

    /** @param list<int> $indexes descending order not required — sorted internally so earlier removals don't shift later target indexes */
    public function removeMany(string $content, array $indexes): string
    {
        $decoded = $this->decode($content);
        $objects = is_array($decoded['Objects'] ?? null) ? array_values($decoded['Objects']) : [];
        rsort($indexes);
        foreach ($indexes as $index) {
            if (array_key_exists($index, $objects)) {
                unset($objects[$index]);
            }
        }
        $decoded['Objects'] = array_values($objects);

        return $this->encode($decoded);
    }

    public function duplicateAt(string $content, int $index): string
    {
        $decoded = $this->decode($content);
        $objects = is_array($decoded['Objects'] ?? null) ? array_values($decoded['Objects']) : [];
        if (! array_key_exists($index, $objects)) {
            throw new RuntimeException("Objekt na indexu {$index} v Object Spawner JSON neexistuje.");
        }
        array_splice($objects, $index + 1, 0, [$objects[$index]]);
        $decoded['Objects'] = $objects;

        return $this->encode($decoded);
    }

    public function indexFromPath(string $path): int
    {
        if (! preg_match(self::PATH_PATTERN, $path, $matches)) {
            throw new RuntimeException("Neplatná cesta k objektu: {$path}");
        }

        return (int) $matches[1];
    }

    /** @param array<string,mixed> $object @return list<string> validation error messages, empty when valid */
    public function validate(array $object): array
    {
        $errors = [];
        $name = trim((string) ($object['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'Classname objektu je povinný.';
        } elseif (! preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            $errors[] = "Classname '{$name}' smí obsahovat jen písmena, čísla a podtržítko.";
        }

        $pos = $object['pos'] ?? null;
        if (! is_array($pos) || count($pos) !== 3 || ! $this->allNumeric($pos)) {
            $errors[] = 'Pozice (pos) musí být pole tří čísel [X, Y, Z].';
        } else {
            [$x, , $z] = array_values($pos);
            if ($x < 0 || $x > MapConfigurationReader::WORLD_SIZE || $z < 0 || $z > MapConfigurationReader::WORLD_SIZE) {
                $errors[] = 'Souřadnice X/Z musí být v rozsahu mapy (0–'.MapConfigurationReader::WORLD_SIZE.').';
            }
        }

        $ypr = $object['ypr'] ?? null;
        if (! is_array($ypr) || count($ypr) !== 3 || ! $this->allNumeric($ypr)) {
            $errors[] = 'Rotace (ypr) musí být pole tří čísel [yaw, pitch, roll].';
        }

        $scale = $object['scale'] ?? 1;
        if (! is_numeric($scale) || (float) $scale <= 0) {
            $errors[] = 'Měřítko (scale) musí být kladné číslo.';
        }

        return $errors;
    }

    /**
     * Adds $relativePath to cfggameplay.json's objectSpawnersArr, preserving every other key
     * untouched, and creating the array if it doesn't exist yet. Real cfggameplay.json exports
     * (checked against 3 files under dokumenty/) nest it under "WorldsData" (plural) —
     * "PlayerData"/root are also accepted if already present, matching whichever the file
     * already uses, but a brand-new array is always created under "WorldsData" to match the
     * real, observed shape rather than a guessed one.
     */
    public function registerSpawnerFile(string $gameplayContent, string $relativePath): string
    {
        $decoded = json_decode($gameplayContent, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('cfggameplay.json není platný JSON.');
        }

        $relativePath = str_replace('\\', '/', $relativePath);
        $location = match (true) {
            Arr::has($decoded, 'WorldsData.objectSpawnersArr') => 'WorldsData.objectSpawnersArr',
            Arr::has($decoded, 'PlayerData.objectSpawnersArr') => 'PlayerData.objectSpawnersArr',
            Arr::has($decoded, 'objectSpawnersArr') => 'objectSpawnersArr',
            default => 'WorldsData.objectSpawnersArr',
        };

        $files = Arr::get($decoded, $location, []);
        $files = is_array($files) ? $files : [];
        $already = collect($files)->contains(fn ($file) => is_string($file) && strtolower(str_replace('\\', '/', $file)) === strtolower($relativePath));
        if (! $already) {
            $files[] = $relativePath;
            Arr::set($decoded, $location, array_values($files));
        }

        return $this->encode($decoded);
    }

    /** @return list<array<string,mixed>> */
    private function decodeObjects(string $content): array
    {
        $decoded = $this->decode($content);

        return is_array($decoded['Objects'] ?? null) ? array_values($decoded['Objects']) : [];
    }

    /** @return array<string,mixed> */
    private function decode(string $content): array
    {
        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Object Spawner JSON není platný.');
        }

        return $decoded;
    }

    private function encode(array $decoded): string
    {
        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ?: throw new RuntimeException('Object Spawner JSON se nepodařilo sestavit.');
    }

    /** @param array<string,mixed> $object @return array{name:string,pos:array{0:float,1:float,2:float},ypr:array{0:float,1:float,2:float},scale:float,enableCEPersistency:bool} */
    private function normalize(array $object): array
    {
        $pos = is_array($object['pos'] ?? null) ? array_values($object['pos']) : [0, 0, 0];
        $ypr = is_array($object['ypr'] ?? null) ? array_values($object['ypr']) : [0, 0, 0];

        return [
            'name' => trim((string) ($object['name'] ?? '')),
            'pos' => [(float) ($pos[0] ?? 0), (float) ($pos[1] ?? 0), (float) ($pos[2] ?? 0)],
            'ypr' => [(float) ($ypr[0] ?? 0), (float) ($ypr[1] ?? 0), (float) ($ypr[2] ?? 0)],
            'scale' => (float) ($object['scale'] ?? 1),
            'enableCEPersistency' => (bool) ($object['enableCEPersistency'] ?? false),
        ];
    }

    private function allNumeric(array $values): bool
    {
        foreach ($values as $value) {
            if (! is_numeric($value)) {
                return false;
            }
        }

        return true;
    }
}
