<?php

namespace Megaads\SsoClient\Middleware;

use Closure;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Megaads\Sso\Controllers\SsoController;
use Megaads\SsoClient\Services\SsoService;

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
                $previousUrl = URL::previous();
                Session::put('redirect_url', $previousUrl);
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
