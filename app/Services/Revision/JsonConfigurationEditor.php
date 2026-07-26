<?php

namespace App\Services\Revision;

use Illuminate\Validation\ValidationException;
use JsonException;

final class JsonConfigurationEditor
{
    public function supports(string $content): bool
    {
        try {
            $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        return is_array($data);
    }

    public function fields(string $content): array
    {
        try {
            $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw ValidationException::withMessages(['rawContent' => $e->getMessage()]);
        }
        $fields = [];
        $this->flatten($data, '', $fields);

        return $fields;
    }

    public function update(string $content, array $values): string
    {
        try {
            $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw ValidationException::withMessages(['rawContent' => $e->getMessage()]);
        }
        foreach ($values as $path => $value) {
            $originalValue = data_get($data, $path);
            if (is_string($value) && in_array(substr(ltrim($value), 0, 1), ['[', '{'], true)) {
                try {
                    $value = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    throw ValidationException::withMessages([
                        "jsonValues.{$path}" => "Neplatný JSON seznam/objekt: {$e->getMessage()}",
                    ]);
                }
            }
            if ((is_int($originalValue) || is_float($originalValue)) && is_numeric($value)) {
                $value = is_int($originalValue) && ! str_contains((string) $value, '.')
                    ? (int) $value
                    : (float) $value;
            } elseif (is_bool($originalValue) && is_string($value)) {
                $value = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                if ($value === null) {
                    throw ValidationException::withMessages(["jsonValues.{$path}" => 'Povoleno je pouze true nebo false.']);
                }
            }
            data_set($data, $path, $value);
        }
        $this->validateGameplayValues($data);

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    }

    private function validateGameplayValues(array $data): void
    {
        foreach ([
            'PlayerData.MovementData.timeToStrafeJog',
            'PlayerData.MovementData.rotationSpeedJog',
            'PlayerData.MovementData.timeToSprint',
            'PlayerData.MovementData.timeToStrafeSprint',
            'PlayerData.MovementData.rotationSpeedSprint',
        ] as $path) {
            $value = data_get($data, $path);
            if ($value !== null && (! is_numeric($value) || (float) $value < 0.01)) {
                throw ValidationException::withMessages(["jsonValues.{$path}" => 'Hodnota musí být alespoň 0.01.']);
            }
        }
        foreach ([
            'PlayerData.StaminaData.staminaMax',
            'PlayerData.StaminaData.staminaMinCap',
        ] as $path) {
            $value = data_get($data, $path);
            if ($value !== null && (! is_numeric($value) || (float) $value <= 0)) {
                throw ValidationException::withMessages(["jsonValues.{$path}" => 'Hodnota musí být větší než 0.']);
            }
        }
        foreach ([
            'PlayerData.WeaponObstructionData.staticMode',
            'PlayerData.WeaponObstructionData.dynamicMode',
            'WorldsData.lightingConfig',
        ] as $path) {
            $value = data_get($data, $path);
            if ($value !== null && (! is_numeric($value) || ! in_array((int) $value, [0, 1, 2], true))) {
                throw ValidationException::withMessages(["jsonValues.{$path}" => 'Povolené hodnoty jsou 0, 1 nebo 2.']);
            }
        }
        $breakPoint = data_get($data, 'UIData.HitIndicationData.hitDirectionBreakPointRelative');
        if ($breakPoint !== null && (! is_numeric($breakPoint) || (float) $breakPoint < 0 || (float) $breakPoint > 1)) {
            throw ValidationException::withMessages(['jsonValues.UIData.HitIndicationData.hitDirectionBreakPointRelative' => 'Hodnota musí být v rozsahu 0–1.']);
        }
        foreach (['WorldsData.environmentMinTemps' => 12, 'WorldsData.environmentMaxTemps' => 12, 'WorldsData.wetnessWeightModifiers' => 5] as $path => $count) {
            $value = data_get($data, $path);
            if ($value !== null && (! is_array($value) || count($value) !== $count || collect($value)->contains(fn ($item) => ! is_numeric($item)))) {
                throw ValidationException::withMessages(["jsonValues.{$path}" => "Pole musí obsahovat přesně {$count} čísel."]);
            }
        }
    }

    private function flatten(array $data, string $prefix, array &$fields): void
    {
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value)) {
                if ($value !== [] && ! array_is_list($value)) {
                    $this->flatten($value, $path, $fields);
                } else {
                    $fields[] = [
                        'path' => $path,
                        'section' => explode('.', $path)[0],
                        'label' => str($key)->headline()->toString(),
                        'type' => 'json',
                        'value' => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    ];
                }
                continue;
            }
            $fields[] = ['path' => $path, 'section' => explode('.', $path)[0], 'label' => str($key)->headline()->toString(), 'type' => is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'text'), 'value' => $value];
        }
    }
}
