<?php 
namespace Megaads\SsoClient\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class SsoService 
{   
    protected $token = "";

    public function setToken() {
        if ( Session::has('ssoToken') ) {
            $token = Session::get('ssoToken');
            $this->token = $token;
        }
    }
    public function getRedirectUrl () {
        $httpHost = "http://{$_SERVER['HTTP_HOST']}";
        $serverConfig = \Config::get('sso-client.server');
        $callbackUrl  = $httpHost . '/sso/callback';
        if ( \Config::get('sso-client.callback_url') != '') {
            $callbackUrl = $httpHost . '' . \Config::get('sso-client.callback_url');
        }
        $encodedCallbackUrl = urlencode($callbackUrl);
        $ssoServer = $serverConfig['base_url'];
        $loginPath = $serverConfig['login_path'];
        $redirectUrl  = $ssoServer;
        $redirectUrl .= $loginPath; 
        $redirectUrl .= "?continue=$encodedCallbackUrl";
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
        $configAuthParams = \Config::get('sso-client.auth_params');
        $urlParams = '';
        if ( count($configAuthParams) > 0 ) {
            foreach ( $configAuthParams as $key => $val ) {
               if ( $key == 'token' && $this->token !== '') {
                    $val = $this->token ;
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