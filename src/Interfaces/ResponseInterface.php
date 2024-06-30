<?php

declare(strict_types=1);

namespace UpiCore\Router\Interfaces;

interface ResponseInterface extends \Psr\Http\Message\ResponseInterface
{
    public function withContent(string $contentType): ResponseInterface;

    public function withData(mixed $data): ResponseInterface;
    
    public function withMessage(string $message): ResponseInterface;

    public function toResponse(): void;
}
