<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class JsonMiddleware{
    public function __invoke(Request $request, RequestHandler $handler)
    {
        $response = $handler->handle($request);
        $response = $response->withHeader("Content-Type","application/json");
        return $response;
    }
}