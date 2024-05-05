<?php

return [
    'autoload' => false,
    'hooks' => [
        'app_init' => [
            'qrcode',
            'twostep',
        ],
        'user_login_successed' => [
            'twostep',
        ],
        'admin_login_after' => [
            'twostep',
        ],
        'user_logout_successed' => [
            'twostep',
        ],
        'admin_logout_after' => [
            'twostep',
        ],
        'user_sidenav_after' => [
            'twostep',
        ],
    ],
    'route' => [
        '/qrcode$' => 'qrcode/index/index',
        '/qrcode/build$' => 'qrcode/index/build',
    ],
    'priority' => [],
    'domain' => '',
];
