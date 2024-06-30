<?php

declare(strict_types=1);

namespace UpiCore\Router;

use UpiCore\Exception\UpiException;

class ContextTranslator
{
    /**
     * Accepted Context Types
     *
     * @var array
     */
    private $contextTypes = [
        \JSON   => \UpiCore\Router\Context\ContextJson::class,
        \XML    => \UpiCore\Router\Context\ContextXml::class
    ];

    protected $translator;

    function __construct(string $contextType = \JSON)
    {
        if (\in_array($contextType, [\JSON, \XML])) {
            $this->translator = new ($this->contextTypes[$contextType])();
        } else {
            throw new UpiException('ROUTER_CONTENT_TYPE_UNSUPPORTED');
        }
    }

    public static function getTranslator(string $contextType = \JSON): object
    {
        return (new self($contextType))->translator;
    }
}
