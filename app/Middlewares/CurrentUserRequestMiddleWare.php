<?php

namespace App\Middlewares;

    use Firebase\JWT\JWT;
    use Illuminate\Support\Arr;
    use Predis\Client;
    use Psr\Http\Message\ServerRequestInterface;

    class CurrentUserRequestMiddleWare implements MiddleWareInterface
    {
        public function handle(ServerRequestInterface $request, callable $next)
        {
            /**
             * @var Client $redis
             */
            global $currentUserID, $redis, $authToken, $sessionID;
            $currentUserID = null;
            $data = array_merge($request->getParsedBody(), $request->getQueryParams());
            saveLogInfo('request data = '.json_encode($data));
            $jwt = Arr::get($data, 'access_token');
            if (!$jwt) {
                $jwtAll = $request->getHeader('Authorization');
                if (!$jwtAll) {
                    return $next($request);
                }
                $jwt = substr($jwtAll[0], 7);
            }
            if (null !== $jwt) {
                $jwt = str_replace('?', '', $jwt);
                $jwt = trim($jwt, '\"');
                if ('' === $jwt) {
                    $currentUserID = null;
                    $authToken = '';
                } else {
                    $payload = JWT::decode($jwt, env('JWT_KEY'), ['HS256']);
                    $payloadArr = (array) $payload;
                    if (!array_key_exists('user_id', $payloadArr)) {
                        $currentUserID = null;
                        $authToken = '';
                    } else {
                        $currentUserID = $payloadArr['user_id'];
                        $authToken = $payloadArr['token'];
                    }
                }
            }

            return $next($request);
        }
    }
