<?php

namespace App\Services\Yaml;

class YamlToArray extends sfYamlParser
{
    public function __construct()
    {
    }

    public function fileParseToArray($yaml)
    {
        return $this->parse($yaml);
    }
}
