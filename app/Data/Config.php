<?php

namespace App\Data;

    use Illuminate\Database\Capsule\Manager;

    class Config
    {
        private static array $config = [];
        private static bool $loaded = false;

        public function __construct()
        {
            $this->loadDir(dirname(__DIR__).'/../config/');
            $existTableConfig = Manager::table('config')->exists();
            if ($existTableConfig) {
                $configDatabase = Manager::table('config')->get();
                foreach ($configDatabase as $row) {
                    $array = json_decode($row->config_value);
                    if (!json_last_error()) {
                        self::$config[$row->group_name][$row->config_key] = $array;
                    } else {
                        self::$config[$row->group_name][$row->config_key] = $row->config_value;
                    }
                }
            }
        }

        public static function getConfig()
        {
            if (self::$loaded) {
                return self::$config;
            }
            $c = new self();
            $c::$loaded = true;

            return $c::$config;
        }

        private function loadDir($dir)
        {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ('.' === $file || '..' === $file) {
                    continue;
                }
                if (is_dir($dir.\DIRECTORY_SEPARATOR.$file)) {
                    $this->loadDir($dir.\DIRECTORY_SEPARATOR.$file);
                } else {
                    $data = require_once $dir.\DIRECTORY_SEPARATOR.$file;
                    $fileName = str_replace('.php', '', $file);
                    self::$config[$fileName] = $data;
                }
            }
        }
    }
