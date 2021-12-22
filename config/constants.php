<?php

    $coefficient = \Illuminate\Database\Capsule\Manager::table('config')
        ->where('group_name', 'auctions')
        ->where('config_key', 'coefficient_money_up')
        ->first();
    $coefficientItog = 1;
    if ($coefficient) {
        $coefficientItog = $coefficient->config_value;
    }

return [
    'products' => [
        'coefficient_bonus_plus_money' => $coefficientItog,
        'price' => [
            'name' => 'Цена',
            'type' => 'float',
            'position' => 'right',
            'value' => 0,
            'coefficient' => null,
            'postfix' => 'currency',
        ],
        'is_free' => [
            'name' => 'Отдаю даром',
            'type' => 'boolean',
            'position' => 'left',
            'value' => false,
            'coefficient' => null,
        ],
        'is_torg' => [
            'name' => 'Торг',
            'type' => 'boolean',
            'position' => 'left',
            'value' => false,
            'coefficient' => null,
        ],
        'is_barter' => [
            'name' => 'Обмен',
            'type' => 'boolean',
            'position' => 'left',
            'value' => false,
            'coefficient' => null,
        ],
        'is_hide_bids' => [
            'name' => 'Скрыть список ставок',
            'type' => 'boolean',
            'position' => 'left',
            'value' => false,
            'coefficient' => null,
        ],
        'is_old_price' => [
            'name' => 'Есть старая цена',
            'type' => 'boolean',
            'position' => 'right',
            'value' => false,
            'coefficient' => null,
        ],
        'old_price' => [
            'name' => 'Старая цена',
            'type' => 'float',
            'position' => 'right',
            'postfix' => 'currency',
            'value' => 0,
            'coefficient' => null,
        ],
        'price_minimal_real' => [
            'name' => 'Минимальная реальная цена',
            'type' => 'float',
            'position' => 'right',
            'postfix' => 'currency',
            'value' => null,
            'coefficient' => 1,
        ],
        'price_minimal_bids' => [
            'name' => 'Минимальная реальная цена',
            'type' => 'float',
            'position' => 'right',
            'postfix' => 'currency',
            'value' => null,
            'coefficient' => 1,
        ],
        'price_step' => [
            'name' => 'Шаг цены',
            'type' => 'float',
            'position' => 'right',
            'postfix' => 'currency',
            'value' => null,
            'coefficient' => 0.1,
        ],
        'price_start' => [
            'name' => 'Начальная цена',
            'type' => 'float',
            'position' => 'right',
            'postfix' => 'currency',
            'value' => null,
            'coefficient' => [
                'classic' => 0.6,
                'penny' => 0.02,
                'quick' => 0.9,
            ],
        ],
        'price_rate_bids' => [
            'name' => 'Цена заявки',
            'type' => 'float',
            'position' => 'right',
            'postfix' => 'currency',
            'value' => null,
            'coefficient' => [
                'penny' => 0.1,
                'quick' => 0.1,
            ],
        ],
        'time_step' => [
            'name' => 'Шаг времени',
            'type' => 'integer',
            'position' => 'left',
            'postfix' => 'time',
            'value' => [
                'classic' => 60, //21600,
                'penny' => 15,
                'quick' => 'develop' === env('APP_ENV') ? 10 * 60 : 10,
            ],
            'coefficient' => null,
        ],
        'time_start' => [
            'name' => 'Начало торгов',
            'type' => 'integer',
            'position' => 'left',
            'postfix' => 'time',
            'value' => 0,
            'coefficient' => null,
        ],
        'time_end' => [
            'name' => 'Конец торгов',
            'type' => 'integer',
            'position' => 'left',
            'postfix' => 'time',
            'value' => 180,
            'coefficient' => null,
        ],
        'has_auto_bids' => [
            'name' => 'Включить автоматические заявки',
            'type' => 'boolean',
            'position' => 'right',
            'value' => false,
            'coefficient' => null,
        ],
        'time_pause' => [
            'name' => 'Время сна аукциона',
            'type' => 'integer',
            'position' => 'left',
            'value' => 18000,
            'coefficient' => null,
        ],
    ],
    'message_edit_interval' => 86400,
    'message_attachments_count' => 10,
    'max_root_dirs' => 32766,
    'price_to_up_product' => 1,
    'insert_money_rate' => 1,
    'bids_to_money' => 1,
    'bids_to_money_gamer' => 1,
    'bids_to_money_bonus' => 1,
    'qr_code_size' => 130,
    'ACTION_LIMIT_1M' => 10000,
    'comission' => 0.00,
    'price_step_penny' => 3,
    'quick_penny_percent' => 0.1,
    'penny_price_rate_bids' => 30,
    'penny_price_rate_bids_gamer' => 1,
    'registration_bonus' => 10,
    'registration_bonus_gamer' => 100,
    'equiring_min' => 150, // TODO toAdminka
    'equiring' => 0.05, // TODO toAdminka
    'equiring_min_gamer' => 250, // TODO toAdminka
    'equiring_gamer' => 0.08, // TODO toAdminka
    'api_version' => 1.12,
];
