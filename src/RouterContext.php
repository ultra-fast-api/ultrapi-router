<?php

namespace UpiCore\Router;

use UpiCore\Router\Http\Response;
use UpiCore\Router\Interfaces\ResponseInterface;

class RouterContext extends Response implements ResponseInterface
{

    public function __construct()
    {
        parent::__construct();
    }

    public static function createResponse(): ResponseInterface
    {
        return new self();
    }
}
