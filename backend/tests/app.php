<?php

require_once __DIR__.'/../vendor/autoload.php';

if (isset($_SERVER['SERVER_NAME'])) {
    Dotenv::load(__DIR__.'/../', '.env.' . $_SERVER['SERVER_NAME']);
} else {
    Dotenv::load(__DIR__.'/../');
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

$app->withFacades();

$app->withEloquent();

$app->configure('app');
$app->configure('auth');
$app->configure('error');
$app->configure('biddingos');
$app->configure('filesystems');
$app->configure('permissions');
$app->configure('mail');

// 使用自定义的UserProvider以支持原有密码md5加密
Auth::extend('custom', function () {
    return new \App\Providers\EloquentUserProvider('App\Models\User');
});
/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
//     // Illuminate\Cookie\Middleware\EncryptCookies::class,
//     // Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    Illuminate\Session\Middleware\StartSession::class,
//     // Illuminate\View\Middleware\ShareErrorsFromSession::class,
//     // Laravel\Lumen\Http\Middleware\VerifyCsrfToken::class,
]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'guest' => App\Http\Middleware\RedirectIfAuthenticated::class,
    'permission' => App\Http\Middleware\PermissionMiddleware::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

// $app->register(App\Providers\AppServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);
$app->register(Maatwebsite\Excel\ExcelServiceProvider::class);
/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->group(['namespace' => 'App\Http\Controllers'], function ($app) {
    require __DIR__.'/../app/Http/routes.php';
});

defined('DEFAULT_PAGE_SIZE') or define('DEFAULT_PAGE_SIZE', 25);
defined('DEFAULT_PAGE_NO') or define('DEFAULT_PAGE_NO', 1);

defined('DEFAULT_USER_ID') or define('DEFAULT_USER_ID', 60);
defined('DEFAULT_ACCOUNT_ID') or define('DEFAULT_ACCOUNT_ID', 35);
defined('DEFAULT_SELF_USER_ID') or define('DEFAULT_SELF_USER_ID', 634);
defined('DEFAULT_SELF_ACCOUNT_ID') or define('DEFAULT_SELF_ACCOUNT_ID', 1672);
defined('DEFAULT_AGENCY_ID') or define('DEFAULT_AGENCY_ID', 2);
defined('ADVERTISER_ROLE') or define('ADVERTISER_ROLE', 9);
defined('MANAGER_ROLE') or define('MANAGER_ROLE', 1010);
defined('MANAGER_OT_ROLE') or define('MANAGER_OT_ROLE', 1013);
defined('TRAFFICKER_ROLE') or define('TRAFFICKER_ROLE', 6);
defined('TRAFFICKER_SELF_ROLE') or define('TRAFFICKER_SELF_ROLE', 1905);
defined('BROKER_ROLE') or define('BROKER_ROLE', 5);
defined('ADMIN_ROLE') or define('ADMIN_ROLE', 1);

// For AdvertiserAccountControllerTest.php
defined('DEFAULT_ACCOUNT_SUB_USER_ID') or define('DEFAULT_ACCOUNT_SUB_USER_ID', 339);
defined('DEFAULT_ROLE_ID') or define('DEFAULT_ROLE_ID', 11);
defined('EXIST_USERNAME') or define('EXIST_USERNAME', 'uc浏览器');
defined('EXIST_PASSWORD') or define('EXIST_PASSWORD', '123456');
defined('EXIST_EMAIL') or define('EXIST_EMAIL', 'uc123@iwalnuts.com');

// for Campaign
defined('PRODUCT_TYPE_DOWNLOAD') or define('PRODUCT_TYPE_DOWNLOAD', 0);
defined('PRODUCT_TYPE_LINK') or define('PRODUCT_TYPE_LINK', 1);
defined('REVENUE_TYPE_CPD') or define('REVENUE_TYPE_CPD', 1);
defined('REVENUE_TYPE_CPC') or define('REVENUE_TYPE_CPC', 2);

defined('AD_TYPE_MARKET') or define('AD_TYPE_MARKET', 0);
defined('AD_TYPE_BANNER_IMG') or define('AD_TYPE_BANNER_IMG', 1);
defined('AD_TYPE_FEED') or define('AD_TYPE_FEED', 2);
defined('AD_TYPE_BANNER_TEXT_LINK') or define('AD_TYPE_BANNER_TEXT_LINK', 5);
defined('AD_TYPE_APP_STORE') or define('AD_TYPE_APP_STORE', 71);

defined('DEFAULT_TRAFFICKER_USER_ID') or define('DEFAULT_TRAFFICKER_USER_ID', 55);
defined('DEFAULT_TRAFFICKER_ACCOUNT_ID') or define('DEFAULT_TRAFFICKER_ACCOUNT_ID', 34);
defined('DEFAULT_TRAFFICKER_SELF_USER_ID') or define('DEFAULT_TRAFFICKER_SELF_USER_ID', 1717);
defined('DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID') or define('DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID', 1644);


defined('DEFAULT_BROKER_USER_ID') or define('DEFAULT_BROKER_USER_ID', 506);
defined('DEFAULT_BROKER_ACCOUNT_ID') or define('DEFAULT_BROKER_ACCOUNT_ID', 449);

defined('DEFAULT_MANAGER_USER_ID') or define('DEFAULT_MANAGER_USER_ID', 2);
defined('DEFAULT_MANAGER_ACCOUNT_ID') or define('DEFAULT_MANAGER_ACCOUNT_ID', 2);
defined('DEFAULT_MANAGER_OT_USER_ID') or define('DEFAULT_MANAGER_OT_USER_ID', 59);
defined('DEFAULT_MANAGER_OT_ACCOUNT_ID') or define('DEFAULT_MANAGER_OT_ACCOUNT_ID', 1);

defined('DEFAULT_ADMIN_USER_ID') or define('DEFAULT_ADMIN_USER_ID', 1);
defined('DEFAULT_ADMIN_ACCOUNT_ID') or define('DEFAULT_ADMIN_ACCOUNT_ID', 1);

defined('AGENCY_ID') or define('AGENCY_ID', 2);

return $app;
