<?php

namespace Tests\Feature;

use App\Services\Dayz\CargoSizeCatalog;
use Tests\TestCase;

class CargoSizeCatalogTest extends TestCase
{
    public function test_barrel_green_resolves_to_its_real_150_slot_cargo_grid(): void
    {
        $entry = app(CargoSizeCatalog::class)->lookup('Barrel_Green');

        $this->assertNotNull($entry);
        $this->assertSame(10, $entry['width']);
        $this->assertSame(15, $entry['height']);
        $this->assertSame(150, $entry['slots']);
        $this->assertSame('container', $entry['kind']);
    }

    public function test_lookup_is_case_insensitive(): void
    {
        $catalog = app(CargoSizeCatalog::class);

        $this->assertSame($catalog->lookup('Barrel_Green'), $catalog->lookup('barrel_green'));
    }

    public function test_unknown_classname_returns_null_rather_than_a_guessed_value(): void
    {
        $this->assertNull(app(CargoSizeCatalog::class)->lookup('ThisClassnameDoesNotExist_XYZ'));
    }

    public function test_bigger_containers_are_sorted_ascending_and_all_exceed_the_threshold(): void
    {
        $suggestions = app(CargoSizeCatalog::class)->biggerContainers(minSlots: 150, limit: 5);

        $this->assertNotEmpty($suggestions);
        $slots = array_column($suggestions, 'slots');
        $this->assertSame($slots, collect($slots)->sort()->values()->all());
        foreach ($slots as $slot) {
            $this->assertGreaterThan(150, $slot);
        }
    }
}
