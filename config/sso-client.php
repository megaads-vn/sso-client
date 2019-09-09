<?php
return [
    'active' => true,
    'server' => [
        'base_url' => 'https://shopbay.vn',
        'login_path' => '/login',
        'auth_path' => '/api/auth',
    ],
    'redirect_to' => '/admin', // Default redirect after login.
    'login_name' => '', // name for login route.default is 'login'
    'login_url' => '',
    'callback_url' => '/sso/callback',
    'aclService' => [
        'load' => false,
        'name' => 'ACLService', // acl service singleton
        'function' => 'loadPermissions()'
    ],
    'auto_create_user' => false,
    'auth_type' => 'Auth', //Auth or Session
    'auth_params' => [
        'token' => '',
        'shop_uuid' => env('SHOP_UUID', -1)
    ],
    'tables' => [
        'users' => 'sb_users'
    ],
    'post_back' => [
        'debug' => 'false', // If debug == true. Ignore check DNS
        'user_table' => 'users',
        'user_account_column' => 'email',
        'active_status' => 'active',
        'deactive_status' => 'deactive',
        'map' => [
            'full_name' => 'name',
            'active' => 'status',
        ]
    ]
];
