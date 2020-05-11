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
        if ($framework && $framework['key'] == 'laravel/framework' && $framework['version'] > 52 ) {
            include __DIR__ . '/routes.php';
        } else {  
            // if ( method_exists($this, 'routesAreCached') ) {
            //     if (!$this->app->routesAreCached()) {
            //         include __DIR__ . '/routes.php';
            //     }
            // }
        }
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
        $tmpFile = __DIR__.'/../config/tmp-config.php';
        $fileContent = include($path);
        $existsConfig = $this->checkExistsSsoConfig();
        if ($existsConfig['status']) {
            $existsContent = include($existsConfig['file']);
            if (count($existsContent) > 0) {
                $fileContent = array_merge($fileContent, $existsContent);
            } 
        }
        $rebuildFileContent = "<?php return [";
        $rebuildFileContent .= $this->buildFileContent($fileContent);
        $rebuildFileContent .= "];";
        file_put_contents($tmpFile, $rebuildFileContent);
        $this->publishes([$tmpFile => config_path('sso.php')], 'config');
    }

    private function getConfigPath()
    {
        return __DIR__.'/../config/sso-client.php';
    }

    private function buildFileContent($fileContent) {
        $retval = "";
        foreach ($fileContent as $key => $val) {
            if (!is_array($val)) {
                $val = $this->paramType($val);
                $retval .= "'$key' => $val, \n";
            } else {
                $retval .= "'$key' => [\n";
                $retval .= $this->buildFileContent($val);
                $retval .= "],";
            }
        }
        return $retval;
    }

    private function paramType($param) {
        $type = gettype($param);
        switch ($type) {
            case 'boolean':
                $retval = json_encode($param);
            break;
            case 'integer':
                $retval = (int) $param;
            break;
            default:
                $retval = "'$param'";
            break;
        }
        return $retval;
    }

    private function checkExistsSsoConfig() {
        $configPath = __DIR__;
        $laravelInfo = $this->checkFrameWork();
        if (isset($laravelInfo['version']) && $laravelInfo['version'] >= 54) {
            $logPath = $configPath . '/../../../../storage/logs';
            $configPath .= '/../../../../config/sso.php'; 
        } else if (isset($laravelInfo['version']) && $laravelInfo['version'] <= 42) {
            $logPath = $configPath . '/../../../../app/storage/logs';
            $configPath .= '/../../../../app/config/sso.php'; 
        }
        $retval = [
            'status' => file_exists($configPath), 
            'file' => $configPath, 
            'logPath' => $logPath,
        ];
        return $retval;
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