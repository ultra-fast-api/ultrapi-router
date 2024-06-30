<?php

// declare(strict_types=1);

// namespace UpiCore\Router;

if (!\defined('UPI_ROUTER_CONSTANTS')) {

    /**
     * Request Methods Defining
     */
    \define('GET', 'GET');
    \define('HEAD', 'HEAD');
    \define('POST', 'POST');
    \define('PATCH', 'PATCH');
    \define('DELETE', 'DELETE');
    \define('PUT', 'PUT');
    \define('CONNECT', 'CONNECT');
    \define('OPTIONS', 'OPTIONS');
    \define('TRACE', 'TRACE');

    \define('ALL_ORIGINS', '*');

    \define('JSON', 'application/json');
    \define('XML', 'application/xml');

    \define('PHP_INPUT', 'php://input');
    \define('PHP_OUTPUT', 'php://output');
    \define('PHP_TEMP', 'php://temp');

    \define('UPI_ROUTER_CONSTANTS', true);
}
