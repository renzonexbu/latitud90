<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | These options configures if and how Inertia uses Server Side Rendering
    | to pre-render the initial visits made to your application's pages.
    |
    | Do note that enabling these options will NOT automatically make SSR work,
    | as a separate rendering service needs to be available. To learn more,
    | please visit https://inertiajs.com/server-side-rendering
    |
    */

    'ssr' => [
        'enabled' => false,
        'url' => 'http://127.0.0.1:13715/render',
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | The values described here are used to simulate how Inertia works
    | during testing, change them to match your needs.
    |
    */

    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [resource_path('js/Pages')],
        'page_extensions' => ['vue', 'svelte'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Inertia view
    |--------------------------------------------------------------------------
    |
    | Configure the view that Inertia uses to render pages.
    |
    */
    'default_view' => 'app',

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Configure the middleware that will be automatically applied to all
    | Inertia requests.
    |
    */
    'middleware' => ['web'],
];
