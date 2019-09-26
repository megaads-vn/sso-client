# MEGAADS SSO CLIENT PACKAGE 
- Install
    ```
    composer require megaads/sso-client
    ```
- Registry app service provider in project app.php config file 
    ```
    Megaads\SsoClient\SsoClientServiceProvider::class
    ```
- Publish package config file
    ```
    php artisan vendor:publish --provider="Megaads\SsoClient\SsoClientServiceProvider"
    ```
After file publish open and edit file config
- Registry custom authentication middleware in `Kernel.php` file
    ```
    'sso' => \Megaads\SsoClient\Middleware\CustomAuthenticate::class,
    ```
- Change middleware on `Kernel.php` like below:
    ```
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        
        //Add bellow lines👇🏻
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class
    ];
    ```
