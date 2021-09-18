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
        $ssoService = new SsoService();
        $tableConfig = \Config::get('sso.tables');
        $userTable = 'users';
        if (isset($tableConfig['users'])) {
            $userTable = $tableConfig['users'];
        }
        if (Session::has("user")) {
            if (\Config::get('sso.active')) {
                $ssoValidationUser = SsoController::ssoTokenValidation();
                if ($ssoValidationUser) {
                    $sessionUser = Session::get('user');
                    $user = \DB::table($userTable)->where('email', $sessionUser->email)->first();
                    Auth::loginUsingId($user->id, true);
                    return $next($request);
                }
                Auth::logout();
            } else {
                return $next($request);
            }
        }
        Session::forget('token');
        Session::forget('user');
        Session::forget('lastUserLogin');
        $cookie = Cookie::forget('user_id');
        return Redirect::route('login')->withCookie($cookie);;
    }
}
