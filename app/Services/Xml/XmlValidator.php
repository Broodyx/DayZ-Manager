<?php

namespace App\Services\Xml;

use Throwable;

final class XmlValidator
{
    public function validate(string $xml): XmlValidationResult
    {
        if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
            return new XmlValidationResult(false, [[
                'line' => 1,
                'message' => 'DOCTYPE and entity declarations are not allowed.',
            ]]);
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $document = new \DOMDocument();
            $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
            $errors = array_map(
                static fn (\LibXMLError $error): array => [
                    'line' => $error->line,
                    'column' => $error->column,
                    'message' => trim($error->message),
                ],
                libxml_get_errors(),
            );

            return new XmlValidationResult($loaded && $errors === [], $errors);
        } catch (Throwable $exception) {
            return new XmlValidationResult(false, [[
                'line' => 0,
                'message' => $exception->getMessage(),
            ]]);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
