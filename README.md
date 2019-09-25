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
    php artisan publish:vendor --provider="Megaads\SsoClient\SsoClientServiceProvider"
    ```
- Registry custom authentication middleware in `Kernel.php` file
    ```
    'sso' => \Megaads\SsoClient\Middleware\CustomAuthenticate::class,
    ```
