<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The URI prefix for Blueprint Studio. Access at /blueprint-studio by default.
    |
    */
    'route_prefix' => env('BLUEPRINT_STUDIO_PREFIX', 'blueprint-studio'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to all Studio routes. Use auth / admin middleware
    | in production so only trusted users can generate code.
    |
    */
    'middleware' => array_filter([
        'web',
        env('BLUEPRINT_STUDIO_MIDDLEWARE'),
    ]),

    /*
    |--------------------------------------------------------------------------
    | Enabled in environments
    |--------------------------------------------------------------------------
    */
    'enabled' => env('BLUEPRINT_STUDIO_ENABLED', true),

    'force_enable' => env('BLUEPRINT_STUDIO_FORCE', false),

    'allowed_environments' => ['local', 'development', 'staging'],

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'models' => app_path('Models'),
        'controllers' => app_path('Http/Controllers'),
        'migrations' => database_path('migrations'),
        'views' => resource_path('views'),
        'requests' => app_path('Http/Requests'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Blade layout used by generated views. If missing, Studio will create it.
    |
    */
    'layout' => [
        'path' => 'layouts/app',
        'section' => 'content',
        'title_section' => 'title',
        'auto_create' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespace defaults
    |--------------------------------------------------------------------------
    */
    'namespaces' => [
        'models' => 'App\\Models',
        'controllers' => 'App\\Http\\Controllers',
        'requests' => 'App\\Http\\Requests',
    ],

    /*
    |--------------------------------------------------------------------------
    | Controller bases
    |--------------------------------------------------------------------------
    */
    'controller_bases' => [
        'user' => [
            'label' => 'User Base',
            'namespace' => 'App\\Http\\Controllers\\User',
            'path' => 'Http/Controllers/User',
            'request_namespace' => 'App\\Http\\Requests\\User',
            'request_path' => 'Http/Requests/User',
            'view_prefix' => 'user.',
            'route_prefix' => 'user.',
            'uri_prefix' => 'user',
        ],
        'admin' => [
            'label' => 'Admin Base',
            'namespace' => 'App\\Http\\Controllers\\Admin',
            'path' => 'Http/Controllers/Admin',
            'request_namespace' => 'App\\Http\\Requests\\Admin',
            'request_path' => 'Http/Requests/Admin',
            'view_prefix' => 'admin.',
            'route_prefix' => 'admin.',
            'uri_prefix' => 'admin',
        ],
        'guest' => [
            'label' => 'Guest Base',
            'namespace' => 'App\\Http\\Controllers\\Guest',
            'path' => 'Http/Controllers/Guest',
            'request_namespace' => 'App\\Http\\Requests\\Guest',
            'request_path' => 'Http/Requests/Guest',
            'view_prefix' => 'guest.',
            'route_prefix' => 'guest.',
            'uri_prefix' => 'guest',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-register resource routes in routes/web.php
    |--------------------------------------------------------------------------
    */
    'auto_routes' => env('BLUEPRINT_STUDIO_AUTO_ROUTES', true),

    /*
    |--------------------------------------------------------------------------
    | Default migration columns (always shown visually)
    |--------------------------------------------------------------------------
    */
    'default_columns' => [
        ['name' => 'id', 'type' => 'id', 'nullable' => false, 'unique' => false, 'default' => null, 'locked' => true],
        ['name' => 'timestamps', 'type' => 'timestamps', 'nullable' => false, 'unique' => false, 'default' => null, 'locked' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field types available in the visual builder
    |--------------------------------------------------------------------------
    */
    'field_types' => [
        'string' => ['label' => 'String', 'migration' => 'string', 'cast' => null, 'input' => 'text', 'rules' => ['string', 'max:255']],
        'text' => ['label' => 'Text', 'migration' => 'text', 'cast' => null, 'input' => 'textarea', 'rules' => ['string']],
        'longText' => ['label' => 'Long Text', 'migration' => 'longText', 'cast' => null, 'input' => 'textarea', 'rules' => ['string']],
        'integer' => ['label' => 'Integer', 'migration' => 'integer', 'cast' => 'integer', 'input' => 'number', 'rules' => ['integer']],
        'bigInteger' => ['label' => 'Big Integer', 'migration' => 'bigInteger', 'cast' => 'integer', 'input' => 'number', 'rules' => ['integer']],
        'boolean' => ['label' => 'Boolean', 'migration' => 'boolean', 'cast' => 'boolean', 'input' => 'checkbox', 'rules' => ['boolean']],
        'decimal' => ['label' => 'Decimal', 'migration' => 'decimal', 'cast' => 'decimal:2', 'input' => 'number', 'rules' => ['numeric']],
        'float' => ['label' => 'Float', 'migration' => 'float', 'cast' => 'float', 'input' => 'number', 'rules' => ['numeric']],
        'date' => ['label' => 'Date', 'migration' => 'date', 'cast' => 'date', 'input' => 'date', 'rules' => ['date']],
        'dateTime' => ['label' => 'DateTime', 'migration' => 'dateTime', 'cast' => 'datetime', 'input' => 'datetime-local', 'rules' => ['date']],
        'time' => ['label' => 'Time', 'migration' => 'time', 'cast' => null, 'input' => 'time', 'rules' => ['date_format:H:i']],
        'json' => ['label' => 'JSON', 'migration' => 'json', 'cast' => 'array', 'input' => 'textarea', 'rules' => ['json']],
        'uuid' => ['label' => 'UUID', 'migration' => 'uuid', 'cast' => 'string', 'input' => 'text', 'rules' => ['uuid']],
        'email' => ['label' => 'Email', 'migration' => 'string', 'cast' => null, 'input' => 'email', 'rules' => ['email', 'max:255']],
        'password' => ['label' => 'Password', 'migration' => 'string', 'cast' => 'hashed', 'input' => 'password', 'rules' => ['string', 'min:8']],
        'foreignId' => ['label' => 'Foreign ID', 'migration' => 'foreignId', 'cast' => 'integer', 'input' => 'number', 'rules' => ['integer', 'exists:TABLE,id']],
        'enum' => ['label' => 'Enum', 'migration' => 'enum', 'cast' => null, 'input' => 'select', 'rules' => ['string']],
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft deletes option
    |--------------------------------------------------------------------------
    */
    'soft_deletes' => false,

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    */
    'brand' => [
        'name' => 'Laravel Blueprint Studio',
        'tagline' => 'Visual scaffolding for modern Laravel apps',
        'developer' => 'Imran Dev BD',
        'developer_url' => 'https://imrandev.bd/',
        'contact_url' => 'https://imrandev.bd/contact',
    ],

];
