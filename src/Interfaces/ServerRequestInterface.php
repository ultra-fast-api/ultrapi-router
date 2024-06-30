<?php

declare(strict_types=1);

namespace UpiCore\Router\Interfaces;

interface ServerRequestInterface extends \Psr\Http\Message\ServerRequestInterface
{
    /**
     * Returns client side request method such as GET, POST, DELETE and so on
     *
     * @return string
     */
    public function getRequestMethod(): string;
}
