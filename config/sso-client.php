<?php
return [
    'active' => true,
    'server' => "https://id.megaads.vn",
    'callback_url' => url('/sso/callback'),
    'logout_callback_url' => url("/"),
    'app_id' => 2,
    'login_path' => '/system/home/login',
    'auth_path' => '/sso/auth',
    'logout_path' => '/system/home/logout',
    'redirect_to' => '/home', // Default redirect after login.
    'login_name' => '', // name for login route.default is 'login'
    'login_url' => '',
    'logout_url' => 'sso-logout',
    'logout_name' => 'logout',
    'callback_url' => '/sso/callback',
    'logout_callback_url' => '/',
    'aclService' => [
        'load' => false,
        'name' => 'ACLService', // acl service singleton
        'function' => 'loadPermissions()'
    ],
    'auto_create_user' => true,
    'auth_type' => 'Auth', //Auth or Session
    'auth_params' => [
        'token' => '',
    ],
    'tables' => [
        'users' => 'users'
    ],
    'post_back' => [
        'debug' => 'true', // If debug == true. Ignore check DNS
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
