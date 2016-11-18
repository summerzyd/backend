<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database results will be returned as instances of the PHP
    | stdClass object; however, you may desire to retrieve records in an
    | array format for simplicity. Here you can tweak the fetch style.
    |
    */

    'fetch' => PDO::FETCH_CLASS,

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'testing' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ],

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix'   => env('DB_PREFIX', ''),
        ],

        'mysql' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', 'localhost'),
            'port'      => env('DB_PORT', 3306),
            'database'  => env('DB_DATABASE', 'lumen'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => env('DB_PREFIX', ''),
            'timezone'  => env('DB_TIMEZONE', '+00:00'),
            'strict'    => false,
        ],

        'pmp' => [
            'driver'    => 'mysql',
            'host'      => env('PMP_DB_HOST', 'localhost'),
            'port'      => env('PMP_DB_PORT', 3306),
            'database'  => env('PMP_DB_DATABASE', 'lumen'),
            'username'  => env('PMP_DB_USERNAME', 'root'),
            'password'  => env('PMP_DB_PASSWORD', ''),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => env('PMP_DB_PREFIX', ''),
            'timezone'  => env('DB_TIMEZONE', '+00:00'),
            'strict'    => false,
        ],

        'redisCacheMysql' => [
            'driver'    => 'mysql',
            'host'      => env('REDIS_CACHE_HOST', 'localhost'),
            'port'      => env('REDIS_CACHE_PORT', 3306),
            'database'  => env('REDIS_CACHE_DATABASE', 'lumen'),
            'username'  => env('REDIS_CACHE_USERNAME', 'root'),
            'password'  => env('REDIS_CACHE_PASSWORD', ''),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => env('REDIS_CACHE_PREFIX', ''),
            'timezone'  => env('DB_TIMEZONE', '+00:00'),
            'strict'    => false,
        ],

        'pgsql' => [
            'driver'   => 'pgsql',
            'host'     => env('DB_HOST', 'localhost'),
            'port'     => env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => env('DB_PREFIX', ''),
            'schema'   => 'public',
        ],

        'sqlsrv' => [
            'driver'   => 'sqlsrv',
            'host'     => env('DB_HOST', 'localhost'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'prefix'   => env('DB_PREFIX', ''),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'cluster' => env('REDIS_CLUSTER', false),

        'default' => [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'port'     => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DATABASE', 0),
            'password' => env('REDIS_PASSWORD', null),
        ],
        
        'RLog' => [
            'host'     => env('REDIS_LOG_HOST', '127.0.0.1'),
            'port'     => env('REDIS_LOG_PORT', 6379),
            'database' => env('REDIS_LOG_DATABASE', 0),
            'password' => env('REDIS_LOG_PASSWORD', null),
        ],

        'RV3' => [
            'host'     => env('REDIS_V3_HOST', '127.0.0.1'),
            'port'     => env('REDIS_V3_PORT', 6379),
            'database' => env('REDIS_V3_DATABASE', 0),
            'password' => env('REDIS_V3_PASSWORD', null),
        ],

        'redis_delivery' => [
            'host'     => env('REDIS_DELIVERY_HOST', '127.0.0.1'),
            'port'     => env('REDIS_DELIVERY_PORT', 6379),
            'database' => env('REDIS_DELIVERY_DATABASE', 0),
            'password' => env('REDIS_DELIVERY_PASSWORD', null),
        ],
        'redis_pika_target' => [
            'host'     => env('REDIS_PIKA_TARGET_HOST', '127.0.0.1'),
            'port'     => env('REDIS_PIKA_TARGET_PORT', 6379),
            'database' => env('REDIS_PIKA_TARGET_DATABASE', 0),
            'password' => env('REDIS_PIKA_TARGET_PASSWORD', null),
        ],

        'redis_adserver' => [
            'host'     => env('REDIS_AD_SERVER_HOST', '127.0.0.1'),
            'port'     => env('REDIS_AD_SERVER_PORT', 6379),
            'database' => env('REDIS_AD_SERVER_DATABASE', 0),
            'password' => env('REDIS_AD_SERVER_PASSWORD', null),
        ],
    ],

];
