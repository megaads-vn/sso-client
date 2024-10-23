<?php 
namespace Megaads\SsoClient\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SsoPostbackController extends BaseController 
{
    private $config;
    public function __construct()
    {
        $this->config = \Config::get('sso.post_back');
    }

    public function ssoPostback() {
        $retval = [
            'status' => 'fail'
        ];
        $configUserTable = $this->config['user_table'];
        $configTableColumn = $this->config['user_account_column'];
        if ( ! $this->config['debug'] ) {
            $checkDns = $this->checkDnsCallService();
            if ( !$checkDns ) {
                $retval['message'] = 'Invalid domain! Please check again.';
                return \Response::json($retval);
            }
        }
        
        if ( !Schema::hasTable($configUserTable) ) {
            $retval['message'] = 'Invalid table name. Please check configuration file.';
            return \Response::json($retval);
        }
        $tableColumns = DB::getSchemaBuilder()->getColumnListing($configUserTable);
        if ( !Input::has('email') || !Input::has('active') || !Input::has('username')) {
            $retval['message'] = 'Invalid param email or active. Please check again!';
        } else {
            $email = Input::get('email');
            $active = Input::get("active");
            $username = Input::get("username");
            $privateKey = Input::get('private_key');
            $user = DB::table($configUserTable)->whereRaw("replace(`email`, '.', '') = replace('$email', '.', '')")->first();

            if (!$user) {
                $retval['msg'] = "Email doesn't exist.";
                if (!$active) {
                    $retval['status'] = 'successful';
                    $retval['msg'] = "Email doesn't exist.";
                } else {
                    $requestInput = Input::all();
                    $insertParams = $this->buildInsertData($tableColumns, $requestInput);
                    $userId = -1;
                    if ( $configTableColumn == 'email' ) {
                        $userId = DB::table($configUserTable)->insertGetId($insertParams);
                    } else {
                        $checkUser = DB::table($configUserTable)->where("username", $username)->first();
                        if ($checkUser) {
                            $insertParams['username'] = $username . mt_rand(100,999);
                            $userId = DB::table($configUserTable)->insertGetId($insertParams);
                        } else {
                            $userId = DB::table($configUserTable)->insertGetId($insertParams);
                        }
                    }
                    if ($userId > 0) {
                        $this->saveUserToken($userId, $configUserTable);
                    }
                    $retval['status'] = 'successful';
                    $retval['msg'] = "Account created successfully with email $email";
                }
            } else {
                $status = $active ? $this->config['active_status'] : $this->config['inactive_status'];
                DB::table($configUserTable)->whereRaw("replace(`email`, '.', '') = replace('$email', '.', '')")
                    ->update(['status' => $status, 'private_key' => $privateKey]);
                if ($status !== $this->config['active_status']) {
                    DB::table($configUserTable)->whereRaw("replace(`email`, '.', '') = replace('$email', '.', '')")
                        ->update(['token' => ""]);
                }
                $retval['status'] = 'successful';
                $retval['msg'] = "Update user's status to $status";
            }
        }

        return \Response::json($retval);
    }

    private function buildInsertData($tableColumns, $requestData = []) {
        unset($tableColumns[0]);
        $username = Input::get('username');
        $mapColumn = $this->config['map'];
        $defaultColumns = $this->config['default_fields'];
        $ignoreColumns = $this->config['ignore_fields'];
        $buildData = [];
        foreach($tableColumns as $column) {
            $params = [];
            $getColum = $column;
            if ( in_array($column, $mapColumn) ) {
                $getColum = array_search($column, $mapColumn);
            }
            $params[$column] = Input::get($getColum, '');
            if (isset($params['created_at'])) {
                $params['created_at'] = date('Y-m-d H:i:s');
            }
            if (isset($params['updated_at'])) {
                $params['updated_at'] = date('Y-m-d H:i:s');
            }
            if (count($requestData) > 0 && isset($requestData[$column])) {
                $params[$column] = $requestData[$column];
            }
            
            $buildData = $buildData + $params;
        }
        if (in_array('slug', $tableColumns)) {
            $name = isset($buildData['name']) && $buildData['name'] != "" ? $buildData['name'] : $username . ' rand' . mt_rand(100,999);
            $emailSlugify = $this->sluggify($buildData['email']);
            $buildData['slug'] = $this->sluggify($name);
            $buildData['slug'] .= '-' . $emailSlugify . '-' . time();
        }
        if (count($defaultColumns) > 0) {
            foreach ($defaultColumns as $col => $val) {
                if (isset($buildData[$col])) {
                    $buildData[$col] = $val;
                }
            }
        }
        if (count($ignoreColumns) > 0) {
            foreach ($ignoreColumns as $col) {
                if (isset($buildData[$col])) {
                    unset($buildData[$col]);
                }
            }
        }
        return $buildData;
    }

    private function checkDnsCallService() {
        return true;
        $retval = true;
        $dns = dns_get_record("id.megaads.vn", DNS_A);
        if ($dns) {
          $currentIp = $_SERVER['REMOTE_ADDR'];
          $ssoIp = $dns[0]['ip'];
          if ($ssoIp != $currentIp) {
              \Log::error("Ip $currentIp dang truy cap trai phep");
              $retval = false;
          }
        } else {
          \Log::error("Khong the phan giai duoc ten mien id.megaads.vn");
          $retval = false;
        }
        return $retval;
    }

    private function sluggify($text, $allowUnder = false) {
        $charMap = array(
            "à" => "a", "ả" => "a", "ã" => "a", "á" => "a", "ạ" => "a", "ă" => "a", "ằ" => "a", "ẳ" => "a", "ẵ" => "a", "ắ" => "a", "ặ" => "a", "â" => "a", "ầ" => "a", "ẩ" => "a", "ẫ" => "a", "ấ" => "a", "ậ" => "a",
            "đ" => "d",
            "è" => "e", "ẻ" => "e", "ẽ" => "e", "é" => "e", "ẹ" => "e", "ê" => "e", "ề" => "e", "ể" => "e", "ễ" => "e", "ế" => "e", "ệ" => "e",
            "ì" => "i", "ỉ" => "i", "ĩ" => "i", "í" => "i", "ị" => "i",
            "ò" => "o", "ỏ" => "o", "õ" => "o", "ó" => "o", "ọ" => "o", "ô" => "o", "ồ" => "o", "ổ" => "o", "ỗ" => "o", "ố" => "o", "ộ" => "o", "ơ" => "o", "ờ" => "o", "ở" => "o", "ỡ" => "o", "ớ" => "o", "ợ" => "o",
            "ù" => "u", "ủ" => "u", "ũ" => "u", "ú" => "u", "ụ" => "u", "ư" => "u", "ừ" => "u", "ử" => "u", "ữ" => "u", "ứ" => "u", "ự" => "u",
            "ỳ" => "y", "ỷ" => "y", "ỹ" => "y", "ý" => "y", "ỵ" => "y",
            "À" => "A", "Ả" => "A", "Ã" => "A", "Á" => "A", "Ạ" => "A", "Ă" => "A", "Ằ" => "A", "Ẳ" => "A", "Ẵ" => "A", "Ắ" => "A", "Ặ" => "A", "Â" => "A", "Ầ" => "A", "Ẩ" => "A", "Ẫ" => "A", "Ấ" => "A", "Ậ" => "A",
            "Đ" => "D",
            "È" => "E", "Ẻ" => "E", "Ẽ" => "E", "É" => "E", "Ẹ" => "E", "Ê" => "E", "Ề" => "E", "Ể" => "E", "Ễ" => "E", "Ế" => "E", "Ệ" => "E",
            "Ì" => "I", "Ỉ" => "I", "Ĩ" => "I", "Í" => "I", "Ị" => "I",
            "Ò" => "O", "Ỏ" => "O", "Õ" => "O", "Ó" => "O", "Ọ" => "O", "Ô" => "O", "Ồ" => "O", "Ổ" => "O", "Ỗ" => "O", "Ố" => "O", "Ộ" => "O", "Ơ" => "O", "Ờ" => "O", "Ở" => "O", "Ỡ" => "O", "Ớ" => "O", "Ợ" => "O",
            "Ù" => "U", "Ủ" => "U", "Ũ" => "U", "Ú" => "U", "Ụ" => "U", "Ư" => "U", "Ừ" => "U", "Ử" => "U", "Ữ" => "U", "Ứ" => "U", "Ự" => "U",
            "Ỳ" => "Y", "Ỷ" => "Y", "Ỹ" => "Y", "Ý" => "Y", "Ỵ" => "Y"
        );

        $text = strtr($text, $charMap);

        $text = $this->cleanUpSpecialChars($text, $allowUnder);
        return strtolower($text);
    }

    private function cleanUpSpecialChars($text, $allowUnder = false) {
        $regExpression = "`\W`i";
        if ($allowUnder)
            $regExpression = "`[^a-zA-Z0-9-]`i";

        $text = preg_replace(array($regExpression, "`[-]+`",), "-", $text);
        return trim($text, "-");
    }

    private function saveUserToken($userId, $userTable) {
        $token = md5($userId . time());
        $user = DB::table($userTable)->where('id', $userId)
                                ->first();
        if (isset($user->token)) {
            DB::table($userTable)->where('id', $userId)->update([
                'token' => $token
            ]);
        }
    }
}
