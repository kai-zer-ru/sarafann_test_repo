<?php

namespace App\Pipeline;

    use Psr\Http\Message\ServerRequestInterface;

    class Resolver
    {
        public function resolve($handler): callable
        {
            return is_string($handler) ? function (ServerRequestInterface $request, $next) use ($handler) {
                if (strstr($handler, '@')) {
                    [$controller, $func] = explode('@', $handler);
                    $controller = "App\\Controllers\\{$controller}";
                    $controller = new $controller($request);

                    return $controller->{$func}($request);
                }
                $handler = new $handler();
                $func = 'handle';

                return $handler->{$func}($request, $next);
            }
            : $handler;
        }
    }
