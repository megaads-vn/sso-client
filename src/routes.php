<?php

$loginRoute = '/login';
$loginRouteName = 'login';
if ( Config::get('sso-client.login_url') != '' ) {
    $loginRoute = Config::get('sso-client.login_url');
}
if ( Config::get('sso-client.login_name') != '' ) {
    $loginRouteName = Config::get('sso-client.login_name');
}

Route::get($loginRoute, [
    'as' => $loginRouteName, 
    'uses' => '\Megaads\SsoClient\Controllers\SsoLoginController@showLoginForm']);

Route::get('/sso/callback', [
    'as' => 'sso::call::back::login', 
    'uses' => '\Megaads\SsoClient\Controllers\SsoLoginController@ssoCallback']);

Route::any('/sso/postback', [
    'as' => 'sso::postback',
    'uses' => '\Megaads\SsoClient\Controllers\SsoPostbackController@ssoPostback'
]);
