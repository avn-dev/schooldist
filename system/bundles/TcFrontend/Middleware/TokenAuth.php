<?php

namespace TcFrontend\Middleware;

use Closure;
use Access_Frontend;
use TcFrontend\Service\Auth\Token;

class TokenAuth {

    const HEADER_KEY = 'X-Fidelo-Token';

    private $access;

    public function __construct(Access_Frontend $access) {
        $this->access = $access;
    }

    public function handle($request, Closure $next) {

        $tokenData = Token::decode((string)$request->header(self::HEADER_KEY, ''));

        if(
            isset($tokenData['user']) &&
            isset($tokenData['token']) &&
            $this->access->checkSession($tokenData['user'], $tokenData['token'])
        ) {
            return $next($request);
        }

        return response('Unauthorized', 401);
    }

}
