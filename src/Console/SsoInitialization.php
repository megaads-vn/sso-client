<?php
/**
 * Created by PhpStorm.
 * User: KimTung
 * Date: 3/27/2020
 * Time: 2:58 PM
 */

namespace Megaads\SsoClient\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class SsoInitialization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sso:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sso client config';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $ssoConfig = config_path('/sso.php');
        if (!file_exists($ssoConfig)) {
            Artisan::call("vendor:publish", [
                '--provider' => "Megaads\SsoClient\SsoClientServiceProvider"
            ]);
            $publishConfig = Artisan::output();
            $this->colorLog(trim($publishConfig), 's');
        }

        if (Schema::hasTable('users')) {
            $callAlter = shell_exec('php artisan migrate --path="vendor/megaads/sso-client/src/Migrations/"');
            $this->colorLog(trim($callAlter), 's');
        } else {
            $this->colorLog('Do not have users table. Then run command make:auth', 'w');
            $artisanPath = base_path('artisan');
            exec('php ' . $artisanPath  .  ' make:auth --force');

            Artisan::call("migrate");
            $migrateOutput = Artisan::output();
            
            $callAlter = shell_exec('php artisan migrate --path="vendor/megaads/sso-client/src/Migrations/"');
            $this->colorLog(trim($callAlter), 's');
        }
    }
    
    private function colorLog($str, $type = 'i'){
        switch ($type) {
            case 'e': //error
                echo "\033[31m$str \033[0m\n";
            break;
            case 's': //success
                echo "\033[32m$str \033[0m\n";
            break;
            case 'w': //warning
                echo "\033[33m$str \033[0m\n";
            break;  
            case 'i': //info
                echo "\033[39m$str \033[0m\n";
            break;      
            default:
            # code...
            break;
        }
    }
}
