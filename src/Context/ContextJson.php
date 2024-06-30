<?php

declare(strict_types=1);

namespace UpiCore\Router\Context;

use UpiCore\Router\Interfaces\ContextInterface;

class ContextJson implements ContextInterface
{
    public function verify(string $plainText): bool
    {
        \json_decode($plainText, true);

        return json_last_error() === JSON_ERROR_NONE;
    }

    public function translate(string $plainText): array
    {
        $requestContent = \json_decode($plainText, true);

        if (!\is_null($requestContent))
            return $requestContent;

        return [];
    }

    public function convert(array $queryParams): string
    {
        return \json_encode($queryParams);
    }
}
