<?php

namespace App\Pipeline;

    class DefaultResponse
    {
        public static function root(): array
        {
            $allResponse = [
                'response' => [
                    'count' => 0,
                    'items' => [],
                ],
            ];
            $allResponse['run_time'] = (int) (microtime(true) - START_TIME) * 1000;
            $allResponse['server_time'] = time();
            $allResponse['server_id'] = env('SERVER_ID', 0);
            $allResponse['error'] = 101;
            $allResponse['error_text'] = 'Неизвестный метод';

            return $allResponse;
        }

        public static function root500($errorCode = 500, $errorText = 'Ошибка на сервере'): array
        {
            $allResponse = [
                'response' => [
                    'count' => 0,
                    'items' => [],
                ],
            ];
            $allResponse['run_time'] = (int) (microtime(true) - START_TIME) * 1000;
            $allResponse['server_time'] = time();
            $allResponse['server_id'] = env('SERVER_ID', 0);
            $allResponse['error'] = $errorCode;
            $allResponse['error_text'] = $errorText;

            return $allResponse;
        }
    }
