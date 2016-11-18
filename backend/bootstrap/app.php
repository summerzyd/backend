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
$app->configure('eqq');
$app->configure('mail');

/*
 * 配置日志文件为每日
 */
/*$app->configureMonologUsing(function(Monolog\Logger $monoLog) use ($app){
    return $monoLog->pushHandler(
        new \Monolog\Handler\RotatingFileHandler($app->storagePath().'/logs/lumen.log', 65535)
    );
});*/

// 使用自定义的UserProvider以支持原有密码md5加密
Auth::extend('custom', function() {
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
if (env('APP_DEBUG')) {
    $app->configure('laravel-crud-generator');
    $app->register(\Funson86\LaravelCrudGenerator\LumenCrudGeneratorServiceProvider::class);
}
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

defined('DEFAULT_PRODUCT_PAGE_SIZE') or define('DEFAULT_PRODUCT_PAGE_SIZE', 100);
defined('DEFAULT_PRODUCT_PAGE_NO') or define('DEFAULT_PRODUCT_PAGE_NO', 1);
return $app;
