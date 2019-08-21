<?php
return [
    'active' => true,
    'server' => [
        'base_url' => 'https://shopbay.vn',
        'login_path' => '/login',
        'auth_path' => '/api/auth',
    ],
    'login_url' => '',
    'callback_url' => '/sso/callback',
    'auto_create_user' => false,
    'auth_type' => 'Auth', //Auth or Session
    'auth_params' => [
        'token' => '',
        'shop_uuid' => env('SHOP_UUID', -1)
    ],
    'tables' => [
        'users' => 'sb_users'
    ]
];