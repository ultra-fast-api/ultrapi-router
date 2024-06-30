<?php

declare(strict_types=1);

namespace UpiCore\Router;

use UpiCore\Config\Config;
use UpiCore\Exception\UpiException;
use UpiCore\Localization\Language;
use UpiCore\Router\Http\Client;

class Router
{

    /**
     * Server Content-Type
     *
     * @var string
     */
    protected static $interpretation = \JSON;

    protected $headers = [];

    protected static $allowedOrigin = null;

    protected static $allowedMethods = [];

    private $phrases = [];

    public function __construct()
    {
        if (\strpos(\getenv('HTTP_ACCEPT'), 'text/html') !== false) {
            $this->viewBrowserClient();
        } else {
            $this->contentType(self::$interpretation);
        }
    }

    function viewBrowserClient(): void
    {
        $apiText = Language::createHttpMessage('HTTP_BROWSER_VIEW');

        (new RouterContext())
            ->withContent(self::getInterpretation())
            ->withStatus($apiText->getStatus())
            ->withMessage($apiText->getMessage())
            ->withData(Config::get('BrowserView'))
            ->toResponse();

        exit;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Returns default selected api Content Type
     *
     * @return string
     */
    public static function getInterpretation(): string
    {
        return self::$interpretation;
    }

    public function setHeaders(array $headers): void
    {
        foreach ($headers as $header => $value) {
            $this->headers[$header] = $value;
        }
    }

    public function contentType(string $interpretation, string $charset = 'UTF-8')
    {
        $this->headers[] = \sprintf('Content-Type: %s; %s', \strtolower((self::$interpretation = $interpretation)), $charset);
    }

    public function allowedOrigin(string $origin)
    {
        $this->headers['Access-Control-Allow-Origin'] = (self::$allowedOrigin = $origin);
    }

    public function allowedMethods(...$methods)
    {
        $this->headers['Access-Control-Allow-Methods'] = self::$allowedMethods = $methods;
    }

    public function phraseExists(string $phrase): bool
    {
        return isset($this->phrases[$phrase]);
    }

    public function get_init(Client $client, \UpiCore\Router\RouterContext $routerContext): \UpiCore\Controller\Interfaces\ControllerInterface
    {
        return $this->phrases['_init']($client, $routerContext, $this);
    }

    public function get_access(Client $client): bool|null
    {
        return $this->phraseExists('_access') ? $this->phrases['_access']($client, $this) : NULL;
    }

    public function get_out(\UpiCore\Router\RouterContext $routerContext): \UpiCore\Router\RouterContext
    {
        return $this->phrases['_out']($routerContext, $this);
    }

    /**
     * Initialize middleware UPI Router
     *
     * @return void
     */
    public function _init(\Closure $initMiddleware): void
    {
        $this->phrases['_init'] = $initMiddleware;
    }

    /**
     * UPI Router middleware before request handling
     *
     * @return void
     */
    public function _access(\Closure $accessMiddleware)
    {
        $this->phrases['_access'] = $accessMiddleware;
    }

    /**
     * UPI Router middleware before initialization of output
     *
     * @return void
     */
    public function _out(\Closure $outgoingMiddleware): void
    {
        $this->phrases['_out'] = $outgoingMiddleware;
    }

    /**
     * Emitting UPI Router changes
     *
     * @return void
     */
    public function _emit()
    {
        try {
            $client = new Client(self::$interpretation);
            $routerContext = (new RouterContext())->withContent(self::getInterpretation());

            // Set present headers
            foreach ($this->headers as $header => $value) {
                if (!\is_array($value)) {
                    $routerContext = $routerContext->withHeader((string) $header, $value);
                } else {
                    foreach ($value as $val) {
                        $routerContext = $routerContext->withAddedHeader($header, $val);
                    }
                }
            }

            // set default exception handler
            UpiException::setExceptionHandler(function (\Throwable $exception) use ($routerContext) {
                try {
                    throw new UpiException('HTTP_STATUS_503', $exception->getMessage());
                } catch (UpiException $e) {
                    $e->withRouterContext($routerContext);
                    $e->returnResult();
                    exit;
                }
            });

            if (!\in_array($client->getRequestMethod(), self::$allowedMethods))
                throw new UpiException('ROUTER_REQUEST_METHOD_IS_NOT_ALLOWED');

            $client->handle();

            if ($this->phraseExists('_init') && $this->phraseExists('_out')) {
                $_init = $this->get_init($client, $routerContext);

                if (!$_init instanceof \UpiCore\Controller\Interfaces\ControllerInterface)
                    throw new UpiException('ROUTER_INIT_TYPEOF_ERR');

                // Handling access process
                $methodResult = $_init(function (\UpiCore\Controller\MethodManager $methodManager) use ($client) {
                    if (!$methodManager->isEveryoneAccess() && false === $this->get_access($client))
                        throw new UpiException('ROUTER_AUTH_ERR');
                });

                $_out = $this->get_out($methodResult);

                if (!$_out instanceof \UpiCore\Router\RouterContext)
                    throw new UpiException('ROUTER_OUT_TYPEOF_ERR');

                $_out->toResponse();
            }
        } catch (UpiException $e) {
            $e->withRouterContext($routerContext);
            $e->returnResult();
            exit;
        }
    }
}
