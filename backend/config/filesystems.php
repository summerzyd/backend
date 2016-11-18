<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. A "local" driver, as well as a variety of cloud
    | based drivers are available for your choosing. Just store away!
    |
    | Supported: "local", "s3", "rackspace"
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
        ],

        's3' => [
            'driver'   => 's3',
            'key'      => env('S3_KEY'),
            'secret'   => env('S3_SECRET'),
            'region'   => env('S3_REGION'),
            'bucket'   => env('S3_BUCKET'),
            'base_url' => env('S3_URL'),
        ],

        'rackspace' => [
            'driver'    => 'rackspace',
            'username'  => env('RACKSPACE_USERNAME'),
            'key'       => env('RACKSPACE_KEY'),
            'container' => env('RACKSPACE_CONTAINER'),
            'endpoint'  => 'https://identity.api.rackspacecloud.com/v2.0/',
            'region'    => env('RACKSPACE_REGION'),
            'url_type'  => 'publicURL',
        ],

    ],

    'linux'     => '/tmp/',
    'windows'   => 'c:\\windows\\temp\\',

    'common' => [
        'uploadPath'        => dirname(__FILE__) . '/../public',
        'uploadMaxSize'     => 40 * 1024 * 1024,
        'uploadAcceptExt'   => ['apk', 'ipa'],
        'origin'            => 'http://localhost',
        'http'              => 'http://www.lumen.com/',
    ],

    'campaign' => [
        'uploadPath'        => dirname(__FILE__) . '/../public',
        'uploadMaxSize'         => 40 * 1024 * 1024,
        'uploadAcceptExt'   => ['apk', 'ipa'],
        'origin'            => 'http://localhost',
        'http'              => 'http://www.lumen.com/',
    ],

    'qiniu' => [
        'accessKey'     => env('QINIU_ACCESS_KEY', 'DXF60eoreRNsxkv0EoL9upcLJM6Fr61blIJiqnG0'), //密钥
        'secretKey'     => env('QINIU_SECRET_KEY', 'v9_xUlzaGnePxrfGXW_HOAQylnTo0_knGBABmnwi'), //密钥
        'bucket'        => env('QINIU_BUCKET', 'test'), //空间名称
        'domain'        => env('QINIU_DOMAIN', 'http://7xnoye.com1.z0.glb.clouddn.com'),
    ],
    'f_web'         => env('UPLOAD_FILE_WEB','http://www.fileserver.com'), //包访问的地址
    'img_web'       => env('UPLOAD_IMG_WEB', 'http://localhost/files/public/'),

];
