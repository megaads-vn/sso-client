<?php

namespace Megaads\SsoClient\Middleware;

use Illuminate\Support\Facades\Auth;
use Closure;
use Illuminate\Support\Facades\Redirect;
use Megaads\Sso\Controllers\SsoController;
use Megaads\SsoClient\Services\SsoService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

class CustomAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $tableConfig = \Config::get('sso.tables');
        $userTable = 'users';
        if (isset($tableConfig['users'])) {
            $userTable = $tableConfig['users'];
        }
        $token = ssoGetCache('sso_token');
        if ($token) {
            if (\Config::get('sso.active')) {
                $ssoValidationUser = SsoController::getUser($token);
                if ($ssoValidationUser ) {
                    $user = \DB::table($userTable)->where('email', $ssoValidationUser->email)->first();
                    if ($user) {
                        Auth::loginUsingId($user->id, true);
                        return $next($request);
                    }
                }
                Auth::logout();
            } else {
                return $next($request);
            }
        }
        ssoForgetCache('user_id');
        ssoForgetCache('sso_token');
        return Redirect::route('login');
    }
}
