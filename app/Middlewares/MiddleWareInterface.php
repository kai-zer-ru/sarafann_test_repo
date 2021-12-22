<?php

namespace App\Middlewares;

        use Psr\Http\Message\ServerRequestInterface;

        interface MiddleWareInterface
        {
            public function handle(ServerRequestInterface $request, callable $next);
        }
