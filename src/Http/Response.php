<?php

declare(strict_types=1);

namespace UpiCore\Router\Http;

use Psr\Http\Message\MessageInterface;
use UpiCore\Exception\UpiException;
use \UpiCore\Router\Interfaces\ResponseInterface;
use UpiCore\Router\Traits\RequestTrait;

class Response implements ResponseInterface
{

    use RequestTrait;

    /**
     * HTTP Status Code
     *
     * @var int
     */
    protected $statusCode = null;

    /**
     * HTTP Reason Phrase
     *
     * @var string
     */
    protected $reasonPhrase = null;

    /**
     * HTTP Response Body
     *
     * @var \UpiCore\Router\Http\Stream
     */
    protected $responseBody = null;

    /**
     * HTTP Data Content
     *
     * @var mixed
     */
    private $responseData = null;

    /**
     * HTTP Response Message
     *
     * @var string
     */
    private $responseMessage = null;

    /**
     * Content Type Translator
     *
     * @var \UpiCore\Router\Context\ContextJson|\UpiCore\Router\Context\ContextXml
     */
    private $translator = null;

    /**
     * HTTP Headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * User defined HTTP Protocol Version
     * Version "1.1" or "1.0"
     *
     * @var string
     */
    protected $withProtocol = null;

    private const PHRASES = [
        100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing',
        200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-status', 208 => 'Already Reported',
        300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 306 => 'Switch Proxy', 307 => 'Temporary Redirect',
        400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Time-out', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Large', 415 => 'Unsupported Media Type', 416 => 'Requested range not satisfiable', 417 => 'Expectation Failed', 418 => 'I\'m a teapot', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 425 => 'Unordered Collection', 426 => 'Upgrade Required', 428 => 'Precondition Required', 429 => 'Too Many Requests', 431 => 'Request Header Fields Too Large', 451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Time-out', 505 => 'HTTP Version not supported', 506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage', 508 => 'Loop Detected', 511 => 'Network Authentication Required',
    ];

    public function __construct()
    {
        $this->responseBody = new Stream(\PHP_OUTPUT);
    }

    public function withContent(string $contentType): ResponseInterface
    {
        $new = clone $this;
        $new->translator = \UpiCore\Router\ContextTranslator::getTranslator($contentType);

        return $new;
    }

    public function getProtocolVersion(): string
    {
        return \substr(\getenv('SERVER_PROTOCOL'), (\strpos(\getenv('SERVER_PROTOCOL'), '/') + 1));
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        $this->headers[] = [\sprintf('HTTP/%s %d %s', $this->withProtocol = $version, $this->getStatusCode(), $this->getReasonPhrase())];

        return $this;
    }

    public function getBody(): \UpiCore\Router\Http\Stream
    {
        return $this->responseBody;
    }

    public function withBody(\Psr\Http\Message\StreamInterface $body): MessageInterface
    {
        $new = clone $this;
        $new->responseBody = $body;

        return $new;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $reasonPhrase = $reasonPhrase ?: $this->getDefaultReasonPhrase($code);

        $new = clone $this;

        $new->statusCode = $code;
        $new->reasonPhrase = $reasonPhrase ?: $this->getDefaultReasonPhrase($code);

        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    private function getDefaultReasonPhrase(int $code): string
    {
        return self::PHRASES[$code] ?? '';
    }

    public final function withData(mixed $data): ResponseInterface
    {
        $new = clone $this;
        $new->responseData = $data;

        return $new;
    }

    public final function withMessage(string $message): ResponseInterface
    {
        $new = clone $this;
        $new->responseMessage = $message;

        return $new;
    }

    public function toResponse(): void
    {
        if (!$this->withProtocol) $this->withProtocolVersion('1.1');

        foreach ($this->headers as $header => $value) {
            // Header key is numeric, treat $value as header line
            if (is_numeric($header)) {
                $headerLine = trim($value[0]);
                header($headerLine);
                continue;
            }

            // Validate header name
            if (!preg_match('/^[a-zA-Z0-9-]+$/', $header)) {
                throw new UpiException('ROUTER_INVALID_CHARS_IN_HEADER', $header);
            }

            // Set header
            $headerLine = trim($header) . ': ' . implode(', ', $value);
            header($headerLine);
        }

        if (\is_null($this->translator))
            throw new UpiException('ROUTER_CONTEXT_INTERPRETATIN_NOT_SPECIFIED');

        $this->responseBody->write(
            $this->translator->convert([
                'status'    => $this->statusCode,
                'message'   => $this->responseMessage,
                'data'      => $this->responseData
            ])
        );
    }
}
