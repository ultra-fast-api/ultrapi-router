<?php

declare(strict_types=1);

namespace UpiCore\Router\Traits;

use Psr\Http\Message\MessageInterface;

trait RequestTrait
{

    public function getHeader(string $name): array
    {
        return \array_key_exists($name, $this->headers) ? \explode(',', $this->headers[$name]) : [];
    }

    public function getHeaderLine(string $name): string
    {
        return \implode(', ', $this->getHeader($name));
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return \array_key_exists($name, $this->headers);
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $new = clone $this;
        $new->headers[$name][] = $value;

        return $new;
    }
    

    public function withHeader(string $name, $value): MessageInterface
    {
        $new = clone $this;
        $new->headers[$name] = [$value];

        return $new;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $new = clone $this;

        if ($new->headers[$name] ?? false)
            unset($new->headers[$name]);

        return $new;
    }
}
