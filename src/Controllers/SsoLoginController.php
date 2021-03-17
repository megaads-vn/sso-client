<?php 
namespace Megaads\SsoClient\Controllers;

use Exception;
use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;
use Megaads\Sso\SsoController;

class SsoLoginController extends BaseController {
    use ThrottlesLogins, RedirectsUsers;

    private $ssoService;
    private $configTables;
    private $aclServices;
    private $config;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin';

    public function __construct()
    {
        $this->ssoService = App::make('ssoService');
        $this->config = \Config::get('sso');
        $this->configTables = \Config::get('sso.tables');
    }


    public function showLoginForm(Request $request) {
        $previousUrl = URL::previous();
        Session::put('redirection', $previousUrl);
        if ( ! $this->config['active'] ) {
            return view('auth.login');
        } else {
            $httpHost = "http://{$_SERVER['HTTP_HOST']}";
            $loginRedirect = SsoController::getRedirectUrl($httpHost);
            return Redirect::to($loginRedirect);
        }
    }

    public function ssoLogout (){
        Auth::logout();
        if ( $this->config['active']) {
            Session::forget('token');
            Session::forget('user');
            $logoutRedirect = $this->ssoService->getLogoutUrl();
            return Redirect::to( $logoutRedirect );
        }
    }

    public function ssoCallback() {
        $request = Input::all();
        $userTable = $this->configTables['users'];
        if ( array_key_exists('token', $request) ) {
            $token = $request['token'];
            $activeStatus = isset($this->config['post_back']) && isset($this->config['post_back']['active_status']) ? $this->config['post_back']['active_status'] : 'active';
            $invalidUserMsg = isset($this->config['messages']) && isset($this->config['messages']['invalid_user']) ? $this->config['messages']['invalid_user'] : 'Invalid user';
            $this->ssoService->setToken($token);
            $userInfo = SsoController::getUser($token);
            if ( $userInfo ) {
                $existsUser = DB::table($userTable)
                                ->where('email', $userInfo->email)
                                ->where('status', $activeStatus)
                                ->first();
                $this->getRedirectTo();
                if ( empty($existsUser) ) {
                    if ( $this->config['auto_create_user'] ) {
                        $userInfo->status = $activeStatus;
                        $userId = $this->createUser($userInfo);
                        $userInfo->id = $userId;
                        return $this->handleUserSignin($userInfo);
                    } else {
                        return Response::make($invalidUserMsg, 403);
                    }
                } else {
                    return $this->handleUserSignin($existsUser);
                }
            } else {
                return Response::make('Unauthorized', 403);
            }
        } else {
            return Response::make('Unauthorized', 500);
        }
    }

    protected function handleUserSignin($user) {
        $acl = (object) $this->config['aclService'];
        $loggedIn = false;
        $authType = $this->config['auth_type'];
        if ( $authType == 'Auth' ) {
            $loggedIn = Auth::loginUsingId($user->id, true);
            Session::put('user', $user);
        }
        if ( $authType == 'Session' ) {
            Session::put("user", $user);
            $loggedIn = true;
        }
        if ( $acl->load && $user->type && $user->type == 'staff' ) {
            $aclFunction = $acl->function;
            $this->aclServices = App::make($acl->name);
            $this->aclServices->$aclFunction;
        }
        if ( $loggedIn ) {
            // Event::fire('auth.login');
            return Redirect::to($this->redirectTo);
        } else {
            return Response::make('Unauthorize', 401);
        }
    }

    protected function getRedirectTo() {
        $this->redirectTo = $this->config['redirect_to'];
        if ( Session::has('redirection') ) {
            $this->redirectTo = Session::get('redirection');
            Session::forget('redirection');
        }

        if ( Session::has('redirect_url') ) {
            $this->redirectTo = Session::get('redirect_url');
            Session::forget('redirect_url');
        }

    }

    protected function createUser($ssoUser) {
        $insertId = -1;
        try {
            $tableUser = $this->configTables['users'];
            $this->buildInsertData($ssoUser);
            $insertId = DB::table($tableUser)->insertGetId(
                array(
                    'email' => $ssoUser->email,
                    'name' => $ssoUser->name,
                    'password' => '',
                    'status' => $ssoUser->status
                )
            );
        } catch (\Exception $ex) {
            \Log::error('Sso_Insert_User_Error: ' . $ex->getMessage());
            throw new Exception("Error when create user " . $ex->getMessage());
        }
        return $insertId;
    }

    private function buildInsertData(&$ssoUser) {
        $postbackConfig = $this->config['post_back'];
        foreach ( $ssoUser as $field => $val ) {
            if ( array_key_exists($field, $postbackConfig['map'])) {
                $getColum = $postbackConfig['map'][$field];
                $ssoUser->$getColum = $ssoUser->$field;
            }
        }
    }
}
