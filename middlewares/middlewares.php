<?php

    use App\Middlewares\CurrentUserRequestMiddleWare;
    use App\Middlewares\SqlDebug;

    return [
        SqlDebug::class,
        CurrentUserRequestMiddleWare::class,
    ];
