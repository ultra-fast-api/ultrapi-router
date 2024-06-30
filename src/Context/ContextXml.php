<?php

declare(strict_types=1);

namespace UpiCore\Router\Context;

use UpiCore\Router\Interfaces\ContextInterface;

class ContextXml implements ContextInterface
{

    public function verify(string $plainText): bool
    {
        libxml_use_internal_errors(true);

        $doc = simplexml_load_string($plainText);

        if ($doc === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            return false;
        }

        return true;
    }

    public function translate(string $plainText): array
    {
        $xmlObject = simplexml_load_string($plainText, "SimpleXMLElement", LIBXML_NOCDATA);
        $jsonString = json_encode($xmlObject);
        $arrayData = json_decode($jsonString, true);

        if (!is_null($arrayData))
            return $arrayData;

        return [];
    }

    public function convert(array $queryParams): string
    {
        $xml = new \SimpleXMLElement('<root/>');
        $this->arrayToXml($queryParams, $xml);
        return $xml->asXML();
    }

    private function arrayToXml(array $data, &$xml)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item' . $key; // Numeric keys are not allowed in XML
                }
                $subnode = $xml->addChild("$key");
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }
}
