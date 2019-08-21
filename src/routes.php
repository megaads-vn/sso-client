<?php

$loginRoute = '/login';
if ( Config::get('sso-client.login_url') != '') {
    $loginRoute = Config::get('sso-client.login_url');
}

Route::get($loginRoute, [
    'as' => 'sso::show::form::login', 
    'uses' => '\Megaads\SsoClient\Controllers\SsoLoginController@showLoginForm']);

Route::get('/sso/callback', [
    'as' => 'sso::call::back::login', 
    'uses' => '\Megaads\SsoClient\Controllers\SsoLoginController@ssoCallback']);
