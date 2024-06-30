<?php

declare(strict_types=1);

namespace UpiCore\Router\Interfaces;

interface ContextInterface
{
    public function verify(string $textPlain): bool;

    public function translate(string $textPlain): array;

    public function convert(array $queryParams): string;
}
