<?php

namespace App\Commands;

    use Illuminate\Support\Arr;
    use Predis\Client;

    class Console
    {
        public $params = [];
        protected Client $redis;
        protected $signature = '';

        public function __construct($redis)
        {
            $this->redis = $redis;
        }

        public function getSignature()
        {
            return $this->signature;
        }

        public function handle()
        {
        }

        public function option($key, $default = null)
        {
            return Arr::get($this->params, $key, $default);
        }

        public function info($string)
        {
            $string = $this->prepareStringToPrint($string);
            $styled = "\e[0;32m${string}\e[0m\n";
            file_put_contents('php://output', $styled);
        }

        public function error($string)
        {
            $string = $this->prepareStringToPrint($string);
            $styled = "\e[1;30;41m${string}\e[0m\n";
            file_put_contents('php://output', $styled);
        }

        private function prepareStringToPrint($string)
        {
            if (is_array($string) || is_object($string)) {
                $string = json_encode($string);
                if (json_last_error()) {
                    $string = json_last_error_msg();
                }
            }

            return $string;
        }
    }
