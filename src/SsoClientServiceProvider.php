<?php 
namespace Megaads\SsoClient;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Megaads\SsoClient\Services\SsoService;

class SsoClientServiceProvider extends ServiceProvider 
{

    public function boot() 
    {
        $framework = $this->checkFrameWork();
        // if ($framework && $framework['key'] == 'laravel/framework' && $framework['version'] > 52 ) {
        //     include __DIR__ . '/routes.php';
        // } else {  
        //     // if ( method_exists($this, 'routesAreCached') ) {
        //     //     if (!$this->app->routesAreCached()) {
        //     //         include __DIR__ . '/routes.php';
        //     //     }
        //     // }
        // }
        $this->app->register('Megaads\SsoClient\Providers\RouteServiceProvider');
        $this->publishConfig();
    }

    public function register() {
        App::singleton('ssoService', function() {
            return new SsoService();
        });
    }

    private function publishConfig()
    {
        $path = $this->getConfigPath();
        $this->publishes([$path => config_path('sso.php')], 'config');
    }

    private function getConfigPath()
    {
        return __DIR__.'/../config/sso-client.php';
    }

    private function checkFrameWork() {
        $findFrameWork = ['laravel/framework','laravel/lumen-framework'];
        $frameworkDeclare = file_get_contents(__DIR__ . '../../../../../composer.json');
        $frameworkDeclare = json_decode($frameworkDeclare, true);
        $required =  array_key_exists('require', $frameworkDeclare) ? $frameworkDeclare['require'] : [];
        $requiredKeys = [];
        if ( !empty($required) ) {
            $requiredKeys = array_keys($required);
            foreach($requiredKeys as $key) {
                if ( in_array($key, $findFrameWork) ) {
                    $version = $required[$key];
                    $version = str_replace('*', '',$version);
                    $version = preg_replace('/\./', '', $version);
                    return ['key' => $key, 'version' => (int) $version];
                }
            }
        }
        return NULL;
    }
}