<?php 
namespace Megaads\SsoClient\Controllers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;

class SsoLoginController {
    
    private $ssoService;
    private $configTables;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin';

    public function __construct()
    {
        $this->ssoService = App::make('ssoService');
        $this->configTables = \Config::get('sso-client.tables');
    }

    public function showLoginForm() {
        if ( ! config('sso.active') ) {
            return view('auth.login');
        } else {
            $loginRedirect = $this->ssoService->getRedirectUrl();
            return Redirect::to($loginRedirect);
        }
    }

    public function ssoCallback() {
        $request = Input::all();
        $userTable = $this->configTables['users'];
        $isAutoCreateUser = \Config::get('sso-client.auto_create_user');
        $authType = \Config::get('sso-client.auth_type');
        if ( array_key_exists('token', $request) ) {
            $token = $request['token'];
            Session::put('ssoToken', $token);
            $this->ssoService->setToken();
            $userInfo = $this->ssoService->getUser();
            if ( $userInfo ) {
                $existsUser = DB::table($userTable)->where('email', $userInfo->email)->first();
                if ( empty($existsUser) ) {
                    if ( $isAutoCreateUser ) {
                        return $this->createUser($userInfo);
                    } else {
                        return Response::make('Invalid username', 403);
                    }
                } else {
                    if ( $authType == 'Auth') {
                        if ( Auth::loginUsingId($existsUser->id)) {
                            return redirect()->to($this->redirectTo);
                        } else { 
                            return Response::make('Unauthorized', 401);
                        }
                    } else {

                    }
                    
                }
            } else {
                return Response::make('Unauthorized', 403);
            }
        } else {
            return Response::make('Unauthorized', 500);
        }
    }
}