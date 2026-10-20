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
        $token = trim(ssoGetCache('sso_token'));
        if ($token) {
            if (\Config::get('sso.active')) {
                $previousUrl = URL::previous();
                Session::put('redirect_url', $previousUrl);
                $ssoValidationUser = SsoController::getUser($token);
                if ($ssoValidationUser ) {
                    $user = \DB::table($userTable)->where('email', $ssoValidationUser->email)->first();
                    if ($user) {
                        if (!trim((string) $user->token)) {
                            $this->saveUserToken($user->id, $token, $userTable);
                        }
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
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'fail', 'message' => 'Unauthenticated'], 401);
        }
        return Redirect::route('login');
    }

    /**
     * users.token is a stable per-user API key (cron jobs and internal APIs
     * authenticate with it), not a mirror of the SSO cookie. Only fill it when
     * it is empty; never overwrite an existing value, otherwise a stale tab
     * holding an older sso_token cookie rewrites the key and breaks callers.
     */
    private function saveUserToken($userId, $token, $userTable) {
        if (!trim($token)) {
            $token = md5($userId . time());
        }
        \DB::table($userTable)
            ->where('id', $userId)
            ->where(function ($query) {
                $query->whereNull('token')->orWhere('token', '');
            })
            ->update([
                'token' => trim($token)
            ]);
    }
}
