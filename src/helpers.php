<?php

if (!function_exists('ssoGetCache')) {
    function ssoGetCache($key, $default = '') {
        $retVal = $default;
        $cacheType = \Config::get('sso.cache_type', 'cookie');
        switch ($cacheType) {
            case 'cache':
                $retVal = \Cache::get($key);
                break;
            case 'cookie':
                $retVal = \Cookie::get($key);
                break;
        }
        return $retVal;
    }
}

if (!function_exists('ssoSetCache')) {
    function ssoSetCache($key, $value, $time = 60) {
        $cacheType = \Config::get('sso.cache_type', 'cookie');
        switch ($cacheType) {
            case 'cache':
                \Cache::put($key, $value, $time);
                break;
            case 'cookie':
                \Cookie::queue($key, $value, $time);
                break;
        }
    }
}

if (!function_exists('ssoForgetCache')) {
    function ssoForgetCache($key) {
        $cacheType = Config::get('sso.cache_type', 'cookie');
        switch ($cacheType) {
            case 'cache':
                \Cache::forget($key);
                break;
            case 'cookie':
                \Cookie::queue(\Cookie::forget($key));
                break;
        }
    }
}