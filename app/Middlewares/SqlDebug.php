<?php

namespace App\Middlewares;

    use App\Services\Api\Funcs;
    use Illuminate\Database\Capsule\Manager;
    use Illuminate\Support\Arr;
    use Psr\Http\Message\ServerRequestInterface;

    class SqlDebug implements MiddleWareInterface
    {
        public function handle(ServerRequestInterface $request, callable $next)
        {
            $format = Arr::get($request->getQueryParams(), 'format', 'json');
            if ('text' === $format) {
                Manager::enableQueryLog();
            }
            $response = $next($request);
            if ('text' === $format) {
                $response['sql'] = Funcs::getDataBaseLog();
            }

            return $response;
        }
    }
