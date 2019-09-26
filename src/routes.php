<?php
$isActive = Config::get('sso-client.active');
if ($isActive) {
    $loginRoute = '/login';
    $loginRouteName = 'login';
    $logoutRoute = '/logout';
    $logoutRouteName = 'logout';
    if (Config::get('sso-client.login_url') != '') {
        $loginRoute = Config::get('sso-client.login_url');
    }
    if (Config::get('sso-client.login_name') != '') {
        $loginRouteName = Config::get('sso-client.login_name');
    }

    if ( Config::get('sso-client.logout_url') != '' ) {
        $logoutRoute = Config::get('sso-client.logout_url');
    }
    if (Config::get('sso-client.logout_name') != '') {
        $logoutRouteName = Config::get('sso-client.logout_name');
    }
    Route::get($loginRoute, [
        'as' => $loginRouteName,
        'uses' => '\Megaads\SsoClient\Controllers\SsoLoginController@showLoginForm'
    ]);

    Route::post($logoutRoute, [
        'as' => $logoutRouteName,
        'uses' => '\Megaads\SsoClient\Controllers\SsoLoginController@ssoLogout'
    ])->middleware(['sso']);
    
    Route::get('/sso/callback', [
        'as' => 'sso::call::back::login',
        'uses' => '\Megaads\SsoClient\Controllers\SsoLoginController@ssoCallback'
    ]);

    Route::any('/sso/postback', [
        'as' => 'sso::postback',
        'uses' => '\Megaads\SsoClient\Controllers\SsoPostbackController@ssoPostback'
    ]);
}
