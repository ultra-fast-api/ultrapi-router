<?php

namespace UpiCore\Router\Http;

use Psr\Http\Message\UriInterface;
use InvalidArgumentException;

class Uri implements UriInterface
{
    private $scheme;
    private $host;
    private $path;
    private $port;
    private $userInfo;
    private $query;
    private $fragment;

    public function __construct(string $uri)
    {
        $partsOfUri = \parse_url($uri);

        if ($partsOfUri === false) {
            throw new \UpiCore\Exception\UpiException('ROUTER_INVALID_URI', $uri);
        }

        $this->scheme   = $partsOfUri['scheme'] ?? '';
        $this->host     = $partsOfUri['host'] ?? '';
        $this->path     = $partsOfUri['path'] ?? '';
        $this->port     = $partsOfUri['port'] ?? null;
        $this->userInfo = $partsOfUri['user'] ?? '';
        $this->query    = $partsOfUri['query'] ?? '';
        $this->fragment = $partsOfUri['fragment'] ?? '';
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        $authority = $this->host;
        if (!empty($this->userInfo)) {
            $authority = $this->userInfo . '@' . $authority;
        }
        if (!empty($this->port)) {
            $authority .= ':' . $this->port;
        }
        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): UriInterface
    {
        $new = clone $this;
        $new->scheme = $scheme;

        return $new;
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $new = clone $this;
        $new->userInfo = $password !== null ? $user . ':' . $password : $user;

        return $new;
    }

    public function withHost(string $host): UriInterface
    {
        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    public function withPort(?int $port): UriInterface
    {
        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    public function withPath(string $path): UriInterface
    {
        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    public function withQuery(string $query): UriInterface
    {
        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    public function withFragment(string $fragment): UriInterface
    {
        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    public function __toString(): string
    {
        $uri = $this->scheme . '://' . $this->host . $this->path;
        if (!empty($this->port)) {
            $uri .= ':' . $this->port;
        }
        if (!empty($this->query)) {
            $uri .= '?' . $this->query;
        }
        if (!empty($this->fragment)) {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }
}
