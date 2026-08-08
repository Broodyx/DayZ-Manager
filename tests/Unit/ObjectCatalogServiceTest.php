<?php

namespace Tests\Unit;

use App\Services\Dayz\ObjectCatalogService;
use Tests\TestCase;

class ObjectCatalogServiceTest extends TestCase
{
    public function test_a_real_container_resolves_with_its_cargo_capacity_cross_referenced(): void
    {
        $entry = app(ObjectCatalogService::class)->find('Barrel_Green');

        $this->assertNotNull($entry);
        $this->assertSame('Containers', $entry['category']);
        $this->assertTrue($entry['has_inventory']);
        $this->assertSame(150, $entry['inventory_slots']);
    }

    public function test_a_real_castle_structure_resolves_without_inventory(): void
    {
        $entry = app(ObjectCatalogService::class)->find('Land_Castle_Bastion');

        $this->assertNotNull($entry);
        $this->assertSame('Castle', $entry['category']);
        $this->assertFalse($entry['has_inventory']);
        $this->assertNull($entry['inventory_slots']);
    }

    public function test_find_is_case_insensitive(): void
    {
        $catalog = app(ObjectCatalogService::class);

        $this->assertSame($catalog->find('Barrel_Green'), $catalog->find('barrel_green'));
    }

    public function test_unknown_classname_returns_null(): void
    {
        $this->assertNull(app(ObjectCatalogService::class)->find('ThisClassnameDoesNotExist_XYZ'));
    }

    public function test_raw_unresolved_stringtable_keys_are_not_surfaced_as_display_name(): void
    {
        $catalog = app(ObjectCatalogService::class);

        foreach ($catalog->all() as $entry) {
            if ($entry['display_name'] !== null) {
                $this->assertStringStartsNotWith('$', $entry['display_name']);
            }
        }
    }

    public function test_search_matches_by_classname(): void
    {
        $results = app(ObjectCatalogService::class)->search('barrel_green');

        $this->assertNotEmpty($results);
        $this->assertTrue(collect($results)->contains(fn (array $e) => $e['classname'] === 'Barrel_Green'));
    }

    public function test_search_matches_by_category(): void
    {
        $results = app(ObjectCatalogService::class)->search('castle');

        $this->assertNotEmpty($results);
        $this->assertTrue(collect($results)->contains(fn (array $e) => $e['classname'] === 'Land_Castle_Bastion'));
    }

    public function test_search_can_be_narrowed_to_a_single_category(): void
    {
        $results = app(ObjectCatalogService::class)->search('', category: 'Containers', limit: 1000);

        $this->assertNotEmpty($results);
        foreach ($results as $entry) {
            $this->assertSame('Containers', $entry['category']);
        }
    }

    public function test_search_respects_the_limit(): void
    {
        $results = app(ObjectCatalogService::class)->search('', limit: 5);

        $this->assertCount(5, $results);
    }

    public function test_categories_are_distinct_and_sorted(): void
    {
        $categories = app(ObjectCatalogService::class)->categories();

        $this->assertSame($categories, collect($categories)->unique()->sort()->values()->all());
        $this->assertContains('Containers', $categories);
        $this->assertContains('Castle', $categories);
    }
}
