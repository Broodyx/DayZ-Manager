<?php

namespace Tests\Unit;

use App\Services\Revision\TypesXmlEditor;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TypesXmlEditorTest extends TestCase
{
    private string $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<types>
    <type name="AKM">
        <nominal>8</nominal>
        <lifetime>28800</lifetime>
        <restock>1800</restock>
        <min>4</min>
        <quantmin>-1</quantmin>
        <quantmax>-1</quantmax>
        <cost>100</cost>
        <flags count_in_cargo="0"/>
        <category name="weapons"/>
    </type>
</types>
XML;

    public function test_it_reads_visual_values_from_types_xml(): void
    {
        $entries = app(TypesXmlEditor::class)->entries($this->xml);

        $this->assertCount(1, $entries);
        $this->assertSame('AKM', $entries[0]['name']);
        $this->assertSame(8, $entries[0]['nominal']);
        $this->assertSame(28800, $entries[0]['lifetime']);
    }

    public function test_it_updates_values_without_removing_unknown_xml_elements(): void
    {
        $updated = app(TypesXmlEditor::class)->update($this->xml, 'AKM', [
            'nominal' => 20,
            'min' => 10,
        ]);

        $this->assertStringContainsString('<nominal>20</nominal>', $updated);
        $this->assertStringContainsString('<min>10</min>', $updated);
        $this->assertStringContainsString('<flags count_in_cargo="0"/>', $updated);
        $this->assertStringContainsString('<category name="weapons"/>', $updated);
    }

    public function test_it_adds_a_new_item_with_category_usage_and_safe_defaults(): void
    {
        $updated = app(TypesXmlEditor::class)->add(
            $this->xml,
            'BandageDressing',
            [
                'nominal' => 30,
                'lifetime' => 7200,
                'restock' => 600,
                'min' => 15,
                'quantmin' => 50,
                'quantmax' => 100,
                'cost' => 25,
            ],
            'medical',
            ['Medic', 'Town'],
        );
        $entries = app(TypesXmlEditor::class)->entries($updated);
        $bandage = collect($entries)->firstWhere('name', 'BandageDressing');

        $this->assertNotNull($bandage);
        $this->assertSame('medical', $bandage['category']);
        $this->assertSame(['Medic', 'Town'], $bandage['usages']);
        $this->assertStringContainsString('count_in_map="1"', $updated);
    }

    public function test_it_rejects_a_duplicate_name_case_insensitively(): void
    {
        $this->expectException(ValidationException::class);

        app(TypesXmlEditor::class)->add(
            $this->xml,
            'akm',
            ['nominal' => 10],
            'weapons',
        );
    }
}
