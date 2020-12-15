<?php

namespace Middlewares;

use Components\Token;
use Models\Action;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class RegisterActionMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler)
    {
        $response = $handler->handle($request);
        $action = new Action;

        $token = $request->getHeaders()['token'] ?? "";
        if (!empty($token))
            $action->id_user = Token::getId($token);

        $action->verb = $request->getMethod();
        $action->endpoint = $request->getUri();

        $ip = $request->getAttribute('ip_address', '127.0.0.1');
        $action->ip_address = $ip;

        $action->save();

        $response = $response->withHeader("Content-Type", "application/json");
        return $response;
    }
}
