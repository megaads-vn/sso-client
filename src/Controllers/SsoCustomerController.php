<?php
namespace Megaads\SsoClient\Controllers;

use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Megaads\Sso\Controllers\SsoController;
use Megaads\SsoClient\Models\Customer;

class SsoCustomerController extends BaseController {
    use ThrottlesLogins, RedirectsUsers;

    /**
     * @return mixed
     */
    public function showCustomerLoginForm()
    {
        $previousUrl = URL::previous();
        $httpHost = "http://{$_SERVER['HTTP_HOST']}";
        $loginRedirect = $this->getRedirection($httpHost);
        return Redirect::to($loginRedirect);
    }


    public function handleCallback(Request $request)
    {
        if ($request->has('token')) {
            $token = $request->get('token');
            $userInfo = $this->getUser($token);
            if (!empty($userInfo)) {
                $findUser = Customer::query()->where('email', $userInfo->email)->first();

            } else {
                return response()->make('Unauthorized', 403);
            }
        } else {
            return response()->make('Invalid request parameters', 500);
        }
    }

    private function getUser ($token, $appId = 0) {
        $retval = false;

        $ip = isset($_SERVER['REMOTE_ADDR']) ? urlencode($_SERVER['REMOTE_ADDR']) : '';
        $url = Session::has('redirect_url') ? urlencode(Session::get('redirect_url')) : '';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? urlencode($_SERVER['HTTP_USER_AGENT']) : '';
        $domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');
        $domain = urlencode($domain);
        $getUserUrl = Config::get('sso.customer.server.url') . "/customer/sso/auth?token=$token&app_id=$appId&ip=$ip&url=$url&user_agent=$user_agent&domain=$domain";
        $response = $this->sendHttpRequest($getUserUrl);
        
        $response = json_decode($response);

        if ($response && $response->status == 'successful' && $response->user) {
            $retval =  $response->user;
        }

        return $retval;
    }

    /**
     * @param $httpHost
     * @return string
     */
    private function getRedirection($httpHost)
    {
        $server = Config::get('sso.customer.server');
        $callbackUrl = Config::get('sso.customer.callback_url');
        $encodedCallbackUrl = urlencode($httpHost . $callbackUrl);
        $redirectUrl = $server['url'] . $server['login_path'] . "?continue=$encodedCallbackUrl";
        return $redirectUrl;
    }

    /**
     * @param $url
     * @return bool|string
     */
    private function sendHttpRequest($url)
    {
        $retval = false;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYPEER => false
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            Log::error('SSO_PACKAGE_REQUEST: ' . $err);
        } else {
            $retval = $response;
        }

        return $retval;
    }


}