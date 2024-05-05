<?php

return [
    'autoload' => false,
    'hooks' => [
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
        'app_init' => [
            'twostep',
        ],
    ],
    'route' => [],
    'priority' => [],
    'domain' => '',
];
