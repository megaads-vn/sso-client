<?php

if (!function_exists('ssoGetCache')) {
    function ssoGetCache($key, $default = '') {
        $retVal = $default;
        $cacheType = \Config::get('sso.cache_type', 'session');
        switch ($cacheType) {
            case 'cache':
                $retVal = \Cache::get($key);
                break;
            case 'cookie':
                $retVal = \Cookie::get($key);
                break;
            case 'session':
                $retVal = \Session::get($key);
                break;
        }
        return $retVal;
    }
}

if (!function_exists('ssoSetCache')) {
    function ssoSetCache($key, $value, $time = 60) {
        $cacheType = \Config::get('sso.cache_type', 'session');
        switch ($cacheType) {
            case 'cache':
                \Cache::put($key, $value, $time);
                break;
            case 'cookie':
                \Cookie::queue($key, $value, $time);
                break;
            case 'session':
                \Session::put($key, $value);
                break;
        }
    }
}

if (!function_exists('ssoForgetCache')) {
    function ssoForgetCache($key) {
        $cacheType = Config::get('sso.cache_type', 'session');
        switch ($cacheType) {
            case 'cache':
                \Cache::forget($key);
                break;
            case 'cookie':
                \Cookie::queue(\Cookie::forget($key));
                break;
            case 'session':
                \Session::forget($key);
                break;
        }
    }
}

if (!function_exists('columnListing')) {
    function columnListing($table) {
        $retVal = NULL;
        if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
            $retVal = \Illuminate\Support\Facades\Schema::getColumnListing($table);
        }
        return $retVal;
    }
}