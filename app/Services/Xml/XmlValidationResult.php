<?php

namespace App\Services\Xml;

final readonly class XmlValidationResult
{
    public function __construct(
        public bool $valid,
        public array $errors = [],
    ) {}
}
