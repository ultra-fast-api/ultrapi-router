<?php

declare(strict_types=1);

namespace UpiCore\Router\Http;

use UpiCore\Exception\UpiException;
use UpiCore\Router\Interfaces\ServerRequestInterface;
use UpiCore\Router\Traits\RequestTrait;

class Client implements ServerRequestInterface
{

    use RequestTrait;

    /**
     * The data given from request
     *
     * @var array
     */
    private array $queryParams = [];

    private array $cookieParams = [];

    private array $uploadedFiles = [];

    private static ?string $requestMethod = null;

    /**
     * @var \UpiCore\Router\Http\Stream
     */
    private ?\UpiCore\Router\Http\Stream $requestBody = null;

    protected $parsedBody;
    protected $attributes = [];
    protected $requestTarget;
    protected $method;

    /**
     * @var \UpiCore\Router\Http\Uri
     */
    protected ?\UpiCore\Router\Http\URi $uri;
    protected $headers = [];
    protected $body;

    protected $protocolVersion;

    /**
     * Content Type Translator
     *
     * @var \UpiCore\Router\Context\ContextJson|\UpiCore\Router\Context\ContextXml
     */
    private $translator = null;

    function __construct(string $translator)
    {
        self::$requestMethod = \getenv('REQUEST_METHOD');

        if (empty(self::$requestMethod))
            throw new UpiException('ROUTER_REQUEST_METHOD_ERR');

        $this->translator = \UpiCore\Router\ContextTranslator::getTranslator($translator);
    }

    /**
     * Handle: Client Side Request Handling
     *
     * @return void
     */
    public function handle(): void
    {
        /**
         * Handle incoming request data
         */
        if (\getenv('CONTENT_TYPE'))
            if (\str_contains(\strtolower(\getenv('CONTENT_TYPE')), 'multipart/form-data') && self::$requestMethod === \POST)
                $isFileRequest = true;

        $this->requestBody = new Stream(\PHP_INPUT);

        $treatAsPartOfData = fn ($data) => ['data' => $data];

        if (\in_array(self::$requestMethod, [\GET])) {
            $this->withQueryParams($treatAsPartOfData($_GET));
        } else if (\in_array(self::$requestMethod, [\POST, \DELETE, \PATCH, \PUT])) {

            $requestContent = $this->requestBody->getContents();

            $isDeleteRequest = (self::$requestMethod === 'DELETE');
            $hasRequestContent = !empty($requestContent);

            if (($isDeleteRequest && $hasRequestContent) || !$isDeleteRequest) {

                // Verify received data
                if (!$this->translator->verify($requestContent) && !isset($isFileRequest))
                    throw new UpiException('ROUTER_RECEIVED_DATA_NOT_VALID');

                if (!isset($isFileRequest)) {
                    $this->withQueryParams($this->translator->translate(
                        $requestContent
                    ));
                } else if (isset($isFileRequest) && self::$requestMethod === \POST) {
                    $this->withQueryParams($treatAsPartOfData($_POST));
                    $this->withUploadedFiles($_FILES);
                } else {
                    $this->withQueryParams($_POST);
                }

                // Set "Received Data" into Stream
                $this->requestBody->write(
                    $requestContent
                );
            }
        }

        // Set Cookies
        $this->withCookieParams($_COOKIE);
    }

    /**
     * Client side request method
     *
     * @return string
     */
    public function getRequestMethod(): string
    {
        return self::$requestMethod;
    }

    public function getServerParams(): array
    {
        $serverParams = [];
        foreach ($_SERVER as $field => $value) {
            if (\preg_match('/SERVER_/', $field))
                $serverParams[\substr($field, strlen('SERVER_'))] = $value;
        }

        return $serverParams;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookieParams): ServerRequestInterface
    {
        foreach ($cookieParams as $field => $value) $this->cookieParams[$field] = $value;

        return $this;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $queryParams): ServerRequestInterface
    {
        foreach ($queryParams as $field => $value) $this->queryParams[$field] = $value;

        return $this;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        foreach ($uploadedFiles as $filePath) {
            $this->uploadedFiles[] = $filePath;
        }

        return $this;
    }

    public function getParsedBody(): array
    {
        return $this->translator->translate(file_get_contents('php://input'));
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion($version): ServerRequestInterface
    {
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getBody(): \UpiCore\Router\Http\Stream
    {
        return $this->requestBody;
    }

    public function withBody(\Psr\Http\Message\StreamInterface $body): ServerRequestInterface
    {
        $new = clone $this;
        $new->requestBody = $body;

        return $new;
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target ?: '/';
    }

    public function withRequestTarget($requestTarget): ServerRequestInterface
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new \InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method): ServerRequestInterface
    {
        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    public function getUri(): \UpiCore\Router\Http\Uri
    {
        return (new Uri('/'));
    }

    public function withUri(\Psr\Http\Message\UriInterface $uri, bool $preserveHost = false): ServerRequestInterface
    {
        $new = clone $this;
        $new->uri = $uri;

        if ($preserveHost && $this->hasHeader('Host')) {
            return $new;
        }

        $host = $uri->getHost();
        if ($host) {
            $new->headers['Host'] = [$host];
        }

        return $new;
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        $new = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute($name, $value): ServerRequestInterface
    {
        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    public function withoutAttribute($name): ServerRequestInterface
    {
        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }
}
