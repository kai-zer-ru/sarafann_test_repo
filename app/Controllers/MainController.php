<?php

/**
 * Copyright  (c).
 *
 * @author kaizer
 * @email kaizer@kai-zer.ru
 * @project SaraFann
 * @file MainController.php
 * @updated 2019-8-5
 */

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface;

class MainController extends Main
{
    public function __construct(ServerRequestInterface $request)
    {
        parent::__construct($request);
        if (!defined('TWO_WEEKS')) {
            define('TWO_WEEKS', 1209600);
        }
    }

    public function root()
    {
        $allResponse = [
            'response' => [
                'count' => 0,
                'items' => [],
            ],
        ];
        $error = [
            'error' => 101,
            'error_text' => 'Неизвестный метод',
            'error_url' => '',
        ];
        $allResponse['run_time'] = 0;
        $allResponse['server_time'] = time();
        $allResponse['error'] = $error['error'];
        $allResponse['error_text'] = $error['error_text'];
        $allResponse['error_url'] = $error['error_url'];

        return $allResponse;
    }


    public function getTime()
    {
        return time();
    }
}
