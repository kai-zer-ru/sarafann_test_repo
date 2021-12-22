<?php

    return FastRoute\cachedDispatcher(function (FastRoute\RouteCollector $r) {
        $r->get('/method/{apiMethod}', 'ApiMainController@getMethodData');
        $r->post('/method/{apiMethod}', 'ApiMainController@getMethodData');
        $r->get('/', 'MainController@root');
    }, ['cacheFile' => dirname(__DIR__).'/cache.route.api']);
