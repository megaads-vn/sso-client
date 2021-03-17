<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Active Single Sign On
    |--------------------------------------------------------------------------
    |
    | This option mark client using Single Sign On or Not.
    |
    */

    'active' => true,

    /*
    |--------------------------------------------------------------------------
    | Server Single Sign On configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the server url, server login path, server auth path
    | server logout path.
    |
    */

    'server' => 'https://id.megaads.vn',
    'login_path' => '/system/home/login',
    'auth_path' => '/sso/auth',
    'logout_path' => '/system/home/logout',

    /*
    |--------------------------------------------------------------------------
    | Client App ID
    |--------------------------------------------------------------------------
    | Using when server required
    | Default is not required this param
    */

    'app_id' => 2,

    /*
    |--------------------------------------------------------------------------
    | Client call back
    |--------------------------------------------------------------------------
    |
    | You may define login callback, logout callback, redirect url after login success
    |
    */

    'callback_url' => '/sso/callback',
    'logout_callback_url' => '/',
    'redirect_to' => '/home',
    'login_name' => '',
    'login_url' => '',
    'logout_url' => 'sso-logout',
    'logout_name' => 'logout',
    
    /*
    |--------------------------------------------------------------------------
    | ACL Service configuration.
    |--------------------------------------------------------------------------
    | This special function. You may not care.
    |
    */

    'aclService' => [
        'load' => false,
        'name' => 'ACLService', // acl service singleton
        'function' => 'loadPermissions()'
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto create user
    |--------------------------------------------------------------------------
    |
    | Allow create user on client automatically after login. 
    |
    */

    'auto_create_user' => true,

    /*
    |--------------------------------------------------------------------------
    | 
    |--------------------------------------------------------------------------
    | This option allow using Auth or Session to login. Default using Laravel Auth
    |
    */

    'auth_type' => 'Auth', //Auth or Session
    'auth_params' => [
        'token' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Map client table
    |--------------------------------------------------------------------------
    |
    | This option allow map client table using store user login information
    |
    */

    'tables' => [
        'users' => 'users'
    ],

    /*
    |--------------------------------------------------------------------------
    | Postback
    |--------------------------------------------------------------------------
    |
    |
    */

    'post_back' => [
        'debug' => 'true', // If debug == true. Ignore check DNS
        'user_table' => 'users',
        'user_account_column' => 'email',
        'active_status' => 'active',
        'inactive_status' => 'deactive',
        'map' => [
            'full_name' => 'name',
            'active' => 'status',
        ]
    ],
    'messages' => [
        'invalid_user' => 'User does not exists or not has permission for this system. Please contact to admin for more information.',
    ]
];
