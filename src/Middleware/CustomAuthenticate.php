<?php

namespace Megaads\SsoClient\Middleware;

use Illuminate\Support\Facades\Auth;
use Closure;
use Illuminate\Support\Facades\Redirect;
use Megaads\Sso\SsoController;
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
        $ssoService = new SsoService();
        $authCheck = Auth::check();
        if ($authCheck) {
            if (\Config::get('sso.active')) {
                $token = $ssoService->getToken();
                $userInfo = SsoController::getUser($token);
                if (
                    $userInfo &&
                    $userInfo->email &&
                    Auth::user() &&
                    Auth::user()->email  &&
                    str_replace(".", "", $userInfo->email) == str_replace(".", "", Auth::user()->email)
                ) {
                    return $next($request);
                }
                Auth::logout();
            } else {
                return $next($request);
            }
        }
        return Redirect::to('login');
    }
}
