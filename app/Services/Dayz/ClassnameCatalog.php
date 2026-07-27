<?php

namespace App\Services\Dayz;

use App\Services\Revision\TypesXmlEditor;
use Illuminate\Support\Facades\Cache;

final readonly class ClassnameCatalog
{
    public function __construct(private TypesXmlEditor $typesEditor) {}

    /** @return list<array{name:string,category:string}> */
    public function entries(): array
    {
        return Cache::remember('dayz.catalog.types.v1', now()->addDay(), function (): array {
            $path = base_path('database/seeders/fixtures/dayz-types-chernarus.xml');
            $content = is_file($path) ? file_get_contents($path) : false;

            return $content === false ? [] : $this->typesEditor->entries($content);
        });
    }

    /** @return list<string> */
    public function names(): array
    {
        return collect($this->entries())->pluck('name')->sort()->values()->all();
    }
}
