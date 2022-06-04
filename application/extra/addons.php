<?php

return [
    'autoload' => false,
    'hooks' => [
        'app_init' => [
            'epay',
            'log',
            'qrcode',
        ],
        'get_cfg' => [
            'litestore',
        ],
        'config_init' => [
            'third',
        ],
    ],
    'route' => [
        '/qrcode$' => 'qrcode/index/index',
        '/qrcode/build$' => 'qrcode/index/build',
        '/third$' => 'third/index/index',
        '/third/connect/[:platform]' => 'third/index/connect',
        '/third/callback/[:platform]' => 'third/index/callback',
        '/third/bind/[:platform]' => 'third/index/bind',
        '/third/unbind/[:platform]' => 'third/index/unbind',
    ],
    'priority' => [],
];
