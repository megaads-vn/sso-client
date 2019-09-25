<?php
return [
    'active' => true,
    'server' => [
        'base_url' => 'https://id.megaads.vn', 
        'login_path' => '/system/home/login', 
        'auth_path' => '/sso/auth', 
    ],
    'redirect_to' => '/', // Default redirect after login.
    'login_name' => '', // name for login route.default is 'login'
    'login_url' => '',
    'callback_url' => '/sso/callback',
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
