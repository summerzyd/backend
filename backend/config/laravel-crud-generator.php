<?php

return [

    'custom_template' => true,

    /*
    |--------------------------------------------------------------------------
    | Crud Generator Template Stubs Storage Path
    |--------------------------------------------------------------------------
    |
    | Here you can specify your custom template path for the generator.
    |
     */

    'path' => base_path('resources/laravel-crud-generator/'),

    'namespace_model' => 'App\\Models',
    'namespace_controller' => 'App\\Http\\Controllers',

    /**
     * Columns number to show in view's table.
     */
    'view_columns_number' => 3,

];
