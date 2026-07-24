<?php

namespace Tests\Unit;

use App\Services\Xml\XmlValidator;
use PHPUnit\Framework\TestCase;

class XmlValidatorTest extends TestCase
{
    public function test_it_accepts_valid_xml(): void
    {
        $result = (new XmlValidator)->validate('<types><type name="Apple"/></types>');
        $this->assertTrue($result->valid);
        $this->assertSame([], $result->errors);
    }

    public function test_it_returns_line_numbers_for_invalid_xml(): void
    {
        $result = (new XmlValidator)->validate("<types>\n<type>\n</types>");
        $this->assertFalse($result->valid);
        $this->assertNotEmpty($result->errors);
        $this->assertGreaterThan(0, $result->errors[0]['line']);
    }

    public function test_it_rejects_doctype_and_external_entities(): void
    {
        $xml = '<!DOCTYPE root [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><root>&xxe;</root>';
        $result = (new XmlValidator)->validate($xml);
        $this->assertFalse($result->valid);
        $this->assertStringContainsString('not allowed', $result->errors[0]['message']);
    }
}
