<?php
namespace Megaads\SsoClient\Controllers;

use Exception;
use Illuminate\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;;
use Illuminate\Support\Facades\URL;
use Megaads\Sso\Controllers\SsoController;

class SsoLoginController extends BaseController {
//    use ThrottlesLogins, RedirectsUsers;
    use Authenticatable;

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
            $loginRedirect = $this->prepareRedirectWithLocale($loginRedirect);
            return Redirect::to($loginRedirect);
        }
    }

    public function ssoLogout (){
        Auth::logout();
        Session::forget('token');
        Session::forget('user');
        Session::flush();
        if ( $this->config['active']) {
            $token = Session::get('token');
            ssoForgetCache('user_id');
            ssoForgetCache('sso_token');
            $logoutRedirect = $this->ssoService->getLogoutUrl();
            return Redirect::to( $logoutRedirect );
        }
    }

    public function ssoCallback(Request $request) {
        $params = $request->all();
        $userTable = $this->configTables['users'];
        if ( array_key_exists('token', $params) ) {
            $token = $params['token'];
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
                        return $this->handleUserSignin($userInfo, $request);
                    } else {
                        return Response::make($invalidUserMsg, 403);
                    }
                } else {
                    if (isset($userInfo->public_key) && isset($userInfo->private_key)
                    && Schema::hasColumn($userTable, 'public_key')
                    && Schema::hasColumn($userTable, 'private_key')) {
                        $this->updateUserKey($existsUser->id, $userInfo);
                    }
                    $this->saveUserToken($existsUser->id, $token);
                    return $this->handleUserSignin($existsUser, $request);
                }
            } else {
                if (Auth::check()) {
                    Auth::logout();
                }
                if (function_exists('ssoForgetCache')) {
                    ssoForgetCache('sso_token');
                }
                return Response::make('Access Denied', 403);
            }
        } else {
            if (Auth::check()) {
                Auth::logout();
            }
            if (function_exists('ssoForgetCache')) {
                ssoForgetCache('sso_token');
            }
            return Response::make('Unauthorized', 500);
        }
    }

    protected function handleUserSignin($user, Request $request) {
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
            $request = $request->all();
            // Event::fire('sso.auth.login');
            ssoSetCache('user_id', $user->id, 999999999);
            ssoSetCache('sso_token', $request['token'] , 999999999);
            $this->saveUserToken($user->id, $request['token']);
            if (Session::has('redirection_' . $user->email)) {
                $this->redirectTo = Session::pull('redirection_' . $user->email);
            }
            return Redirect::to($this->redirectTo);
        } else {
            return Response::make('Unauthorize', 401);
        }
    }

    protected function getRedirectTo($userEmail = '') {
        $this->redirectTo = $this->config['redirect_to'];
        $locale = '';// $this->getLocale();
        if ($locale !== '') {
            $this->redirectTo = '/' . $locale . $this->redirectTo;
        }
        if ( Session::has('redirection') ) {
            $this->redirectTo = Session::get('redirection');
            Session::forget('redirection');
        }

        if ( Session::has('redirect_url') ) {
            $this->redirectTo = Session::get('redirect_url');
            Session::forget('redirect_url');
        }
    }

    /**
     * @param $ssoUser
     * @return mixed
     * @throws Exception
     */
    protected function createUser($ssoUser) {
        $insertId = -1;
        try {
            $tableUser = $this->configTables['users'];
            $this->buildInsertData($ssoUser);
            $insertData = (array) $ssoUser;
            $insertId = DB::table($tableUser)->insertGetId($insertData);

        } catch (\Exception $ex) {
            \Log::error('Sso_Insert_User_Error: ' . $ex->getMessage());
            throw new Exception("Error when create user " . $ex->getMessage());
        }
        return $insertId;
    }

    protected function updateUserKey($userId, $ssoUser) {
        try {
            $tableUser = $this->configTables['users'];
            $this->buildInsertData($ssoUser);
            $updateId = DB::table($tableUser)->select('id')->where('id', $userId)->update(
                array(
                    'public_key' => $ssoUser->public_key,
                    'private_key' => $ssoUser->private_key,
                )
            );

        } catch (\Exception $ex) {
            \Log::error('Sso_Insert_User_Error: ' . $ex->getMessage());
            throw new Exception("Error when create user " . $ex->getMessage());
        }
        return $updateId;
    }

    private function buildInsertData(&$ssoUser) {
        $postbackConfig = $this->config['post_back'];
        $defaultFields = $postbackConfig['default_fields'];
        foreach ( $ssoUser as $field => $val ) {
            if ( array_key_exists($field, $postbackConfig['map'])) {
                $getColum = $postbackConfig['map'][$field];
                $ssoUser->$getColum = $ssoUser->$field;
                unset($ssoUser->$field);
            }
        }
        if (isset($ssoUser->id)) {
            unset($ssoUser->id);
        }
        if (count($defaultFields) > 0) {
            foreach ($defaultFields as $col => $val) {
                if (!isset($ssoUser->$col) && $col == 'password' && $val == 'auto_create') {
                    $ssoUser->$col = Hash::make(date('YmdHi'));
                } elseif (!isset($ssoUser->$col)) {
                    $ssoUser->$col = $val;
                }
            }
        }
    }

    private function showSelectCountry() {
        return view('select-contry');
    }

    private function getLocale() {
        $uri = array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : '';
        preg_match('/\/([A-Za-z]+)(\/*\-*)/', $uri, $locale);

        if (isset($locale[0])) {
            $locale[0] = str_replace('/-', '', $locale[0]);
            if (strpos($locale[0], '-') === false) {
                if (count($locale) == 3) {
                    $locale = $locale[1];
                } else {
                    $locale = '';
                }
            } else {
                $locale = '';
            }
        } else {
            $locale = '';
        }
        $envFile = '.env';
        if ($locale !== '') {
            $envFile = '.' . $locale . '.env';
            if (!file_exists(__DIR__ . '/../../../../../' . $envFile)) {
                $locale = '';
            }
        }
        return $locale;
    }

    private function prepareRedirectWithLocale($loginRedirect) {
        $parts = parse_url($loginRedirect);
        parse_str($parts['query'], $query);
        $locale = $this->getLocale();
        if ($locale !== '') {
            $query['continue'] = str_replace('/sso/callback', "/$locale/sso/callback", $query['continue']);
        }
        $queryStr = '';
        foreach ($query as $attr => $value) {
            $queryStr .= $attr . '=' . urlencode($value) . '&';
        }
        $queryStr = rtrim($queryStr, '&');
        $loginRedirect = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . $queryStr;
        return $loginRedirect;
    }

    private function saveUserToken($userId, $token) {
        $userTable = $this->configTables['users'];
        $user = DB::table($userTable)->where('id', $userId)
            ->first();
        // if (isset($user->token)) {
            DB::table($userTable)->where('id', $userId)->update([
                'token' => $token
            ]);
        // }
    }
}
