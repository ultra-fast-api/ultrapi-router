<?php

declare(strict_types=1);

namespace UpiCore\Router\Http;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    /**
     * Stream Resource
     *
     * @var resource
     */
    private $stream;

    public function __construct(string $PHPStream = \PHP_INPUT, string $mode = 'r+')
    {
        $this->stream = \fopen($PHPStream, $mode);
    }

    public function close(): void
    {
        \fclose($this->stream);
    }

    public function detach()
    {
        $result = $this->stream;
        $this->stream = null;
        return $result;
    }

    public function getSize(): null | int
    {
        $stats = \fstat($this->stream);
        return $stats['size'];
    }

    public function tell(): int
    {
        return \ftell($this->stream);
    }

    public function eof(): bool
    {
        return \feof($this->stream);
    }

    public function isSeekable(): bool
    {
        $meta = \stream_get_meta_data($this->stream);
        return $meta['seekable'];
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        \fseek($this->stream, $offset, $whence);
    }

    public function rewind(): void
    {
        \rewind($this->stream);
    }

    public function isWritable(): bool
    {
        $meta = \stream_get_meta_data($this->stream);
        $mode = $meta['mode'];
        return \strstr($mode, 'w') || \strstr($mode, '+');
    }

    public function write($string): int
    {
        return (int) \fwrite($this->stream, $string);
    }

    public function isReadable(): bool
    {
        $meta = \stream_get_meta_data($this->stream);
        $mode = $meta['mode'];
        return \strstr($mode, 'r') || \strstr($mode, '+');
    }

    public function read(int $length): string
    {
        return (string) \fread($this->stream, $length);
    }

    public function getContents(): string
    {
        return \stream_get_contents($this->stream);
    }

    public function getMetadata($key = null)
    {
        $meta = \stream_get_meta_data($this->stream);
        if ($key === null) {
            return $meta;
        }
        return $meta[$key] ?? null;
    }

    public function __toString(): string
    {
        return (string) \stream_get_contents($this->stream);
    }
}
