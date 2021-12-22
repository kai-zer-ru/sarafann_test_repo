<?php

namespace App\Controllers;

    use App\Models\ApiErrorCode;
    use App\Models\ApiMethod;
    use Error;
    use Illuminate\Database\Capsule\Manager;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Str;
    use Predis\Client;
    use Psr\Http\Message\ServerRequestInterface;

    class Main
    {
        public ServerRequestInterface $request;
        public bool $isLocal = false;
        public Manager $database;
        public Client $redis;

        public function __construct(ServerRequestInterface $request)
        {
            global $redis;
            $this->redis = $redis;
            $this->request = $request;
            if (array_key_exists('HTTP_REFERER', $_SERVER)) {
                $referer = parse_url($_SERVER['HTTP_REFERER']);
                $referer = $referer['host'];
            } else {
                $referer = $request->getUri()->getHost();
            }
        }

        public function prepareErrorResponse($errorCode, $text = '')
        {
            $allResponse = [
                'response' => [
                    'count' => 0,
                    'items' => [],
                ],
            ];
            $serverId = env('SERVER_ID', 0);
            $allResponse['run_time'] = (int) ((microtime(true) - START_TIME) * 1000);
            $allResponse['server_time'] = time();
            $allResponse['error'] = $errorCode;
            $allResponse['error_text'] = $text;
            $allResponse['server_id'] = $serverId;

            return $allResponse;
        }

        public function prepareResponse($response, $st = START_TIME, $lastId = null, $count = null)
        {
            if (isset($response['error']) && 0 !== $response['error'] && !is_string($response['error'])) {
                return $this->prepareErrorResponse(Arr::get($response, 'error', 0), Arr::get($response, 'error_text', ''));
            }
            $allResponse = [
                'response' => [
                    'count' => count($response),
                    'items' => $response,
                ],
            ];
            if (null !== $lastId) {
                $allResponse['response']['last_id'] = $lastId;
            }
            if (null !== $count) {
                $allResponse['response']['all_count'] = $count;
            }
            $allResponse['run_time'] = (int) ((microtime(true) - $st) * 1000);
            $allResponse['server_time'] = time();
            $allResponse['error'] = 0;
            $allResponse['error_text'] = '';
            $allResponse['error_url'] = '';
            $allResponse['server_id'] = env('SERVER_ID');

            return $allResponse;
        }

        public function bearerToken()
        {
            $header = $this->request->getHeader('Authorization')[0];

            if (Str::startsWith($header, 'Bearer ')) {
                return Str::substr($header, 7);
            }

            return '';
        }

        public function getApiResponse($group, $name, $params)
        {
            global $api;
            saveLogDebug('Start API from /api/ (from site)');
            saveLogDebug("{$group}.{$name}");
            /**
             * @var ApiMethod $method
             */
            $method = ApiMethod::whereGroup($group)
                ->whereName($name)
                ->first();
            if (!$method) {
                return ApiErrorCode::whereCode(101)->first()->getArray();
            }
            $functionName = $method->function_name;
            saveLogDebug("Function = {$functionName}");
            $api->setMethod($method);
            $this->request = $this->request->withParsedBody($params);
            [$status, $code, $text] = $api->init($this->request);
            if (!$status) {
                saveLogDebug("{$functionName} is ended without status");
                saveLogDebug($code.' => '.$text);

                return [
                    'error' => $code,
                    'error_text' => $text,
                    'error_url' => '',
                    'response' => [
                        'count' => 0,
                        'items' => [],
                    ],
                    'server_time' => time(),
                    'server_id' => env('SERVER_ID'),
                    'run_time' => 0,
                ];
            }

            try {
                $response = $api->work($functionName);
            } catch (Error $e) {
                saveLogError($e);

                return $this->prepareErrorResponse(500, 'Ошибка на сервере');
            }
            $data = $api->closeLocal($response);
            $format = Arr::get($this->request->getParsedBody(), 'format', 'json');
            if ('text' === $format) {
                if (iAmAdmin()) {
                    if ('production' !== env('APP_ENV') || iAmAdmin()) {
                        return '{}';
                    }
                }

                return $data;
            }
            saveLogDebug("{$functionName} is ended!");

            return $data;
        }

        public function getInputDataInt($name, $default)
        {
            $d = Arr::get($this->request->getParsedBody(), $name, $default);
            if ($d !== $default) {
                return (int) $d;
            }

            return $default;
        }

        public function getInputDataString($name, $default)
        {
            return Arr::get($this->request->getParsedBody(), $name, $default);
        }
    }
