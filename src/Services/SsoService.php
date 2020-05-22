<?php 
namespace Megaads\SsoClient\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class SsoService 
{   
    protected $token = "";

    public function setToken($token) {
        Session::put('ssoToken', $token);
    }

    public function getToken() {
        $token = NULL;
        if (Session::has('ssoToken')) {
            $token = Session::get('ssoToken');
        }
        return $token;
    }
    
    public function getLogoutUrl() {
        $httpHost = "http://{$_SERVER['HTTP_HOST']}";
        $serverUrl = \Config::get('sso.server');
        $serverLogoutPath = \Config::get('sso.logout_path');
        $callbackUrl  = $httpHost . \Config::get('sso.logout_callback_url');
        $encodedCallbackUrl = urlencode($callbackUrl);
        $redirectUrl = "$serverUrl$serverLogoutPath?continue=$encodedCallbackUrl";
        $urlParams = $this->buildUrlParams();
        if ( $urlParams !== '' ) {
            $redirectUrl .= '&' . $urlParams;
        }
        return $redirectUrl;
    }

    public function getUser () {
        $retval = false;
        $serverConfig = \Config::get('sso-client.server');
        $getUserUrl  = $serverConfig['base_url'];
        $authPath = $serverConfig['auth_path'];
        $getUserUrl .= $authPath;
        $urlParams = $this->buildUrlParams();
        if ( $urlParams != '' ) {
            $getUserUrl .= '?' . $urlParams;
        }
        $response = self::sendRequest($getUserUrl);
        $response = json_decode($response);

        if ($response && $response->status == 'success' && $response->code == 0 && $response->user) {
            $retval =  $response->user;
        }

        return $retval;
    }

    public function sendRequest ($url) {
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
          CURLOPT_HTTPHEADER => array(
            "Accept: */*",
            "Cache-Control: no-cache",
            "Connection: keep-alive",
            "accept-encoding: gzip, deflate",
            "cache-control: no-cache",
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {

        } else {
            $retval = $response;
        }

        return $retval;
    }

    protected function buildUrlParams() {
        $token = $this->getToken();
        $configAuthParams = \Config::get('sso-client.auth_params');
        $urlParams = '';
        if ( count($configAuthParams) > 0 ) {
            foreach ( $configAuthParams as $key => $val ) {
               if ( $key == 'token' && $token !== '') {
                    $val = $token ;
               }
               if ( $val != '' ) {
                    $urlParams .= $key . '=' . $val . '&';
               }
            }
            $urlParams = rtrim($urlParams,'&');
        } 
        return $urlParams;
    }
}