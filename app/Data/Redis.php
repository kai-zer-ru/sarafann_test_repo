<?php

namespace App\Data;

    use Predis\Client;

    class Redis
    {
        private static Client $redis;

        public function __construct()
        {
            static::$redis = new Client([
                'scheme' => 'tcp',
                'host' => env('REDIS_HOST'),
                'port' => env('REDIS_PORT'),
                'password' => env('REDIS_PASSWORD'),
            ]);
            static::$redis->connect();
        }

        public static function getRedis(): Client
        {
            $r = new self();

            return $r::$redis;
        }
    }
