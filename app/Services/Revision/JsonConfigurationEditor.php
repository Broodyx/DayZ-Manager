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

        return is_array($data) && array_intersect(array_keys($data), ['GeneralData', 'PlayerData', 'WorldData', 'StaminaData', 'ShockHandlingData', 'MovementData', 'BaseBuildingData', 'UIData', 'version']) !== [];
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
            data_set($data, $path, $value);
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    }

    private function flatten(array $data, string $prefix, array &$fields): void
    {
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value)) {
                if ($value !== [] && ! array_is_list($value)) {
                    $this->flatten($value, $path, $fields);
                }

continue;
            }
            $fields[] = ['path' => $path, 'section' => explode('.', $path)[0], 'label' => str($key)->headline()->toString(), 'type' => is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'text'), 'value' => $value];
        }
    }
}
