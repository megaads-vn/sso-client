<?php
$isActive = Config::get('sso.active');
if ($isActive) {
    $loginRoute = '/login';
    $loginRouteName = 'login';
    $logoutRoute = '/logout';
    $logoutRouteName = 'logout';
    if (Config::get('sso.login_url') != '') {
        $loginRoute = Config::get('sso.login_url');
    }
    if (Config::get('sso.login_name') != '') {
        $loginRouteName = Config::get('sso.login_name');
    }

    if ( Config::get('sso.logout_url') != '' ) {
        $logoutRoute = Config::get('sso.logout_url');
    }
    if (Config::get('sso.logout_name') != '') {
        $logoutRouteName = Config::get('sso.logout_name');
    }
    Route::get($loginRoute, [
        'as' => $loginRouteName,
        'uses' => '\Megaads\SsoClient\Controllers\SsoLoginController@showLoginForm'
    ]);

    Route::any($logoutRoute, [
        'as' => $logoutRouteName,
        'uses' => '\Megaads\SsoClient\Controllers\SsoLoginController@ssoLogout'
    ]);
    
    Route::get('/sso/callback', [
        'as' => 'sso::call::back::login',
        'uses' => '\Megaads\SsoClient\Controllers\SsoLoginController@ssoCallback'
    ]);

    Route::any('/sso/postback', [
        'as' => 'sso::postback',
        'uses' => '\Megaads\SsoClient\Controllers\SsoPostbackController@ssoPostback'
    ]);
}


$customer = Config::get('sso.customer.active');
if ($customer) {
    $customerPrefix = 'customer';
    Route::group(['prefix' => $customerPrefix, 'namespace' => 'Megaads\SsoClient\Controllers'], function() {
        Route::get('sign-in', 'SsoCustomerController@showCustomerLoginForm')->name('customer::show::login::form');
        Route::get('/sso/callback', 'SsoCustomerController@handleCallback')->name('customer::sso::callback');
    });
}