<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | The Geocodio API key to use for authentication requests.
    | You can find and generate new API keys at:
    | https://dash.geocod.io/apikey
    |
    */

    'api_key' => env('GEOCODIO_API_KEY', 'Geocodio'),

    /*
    |--------------------------------------------------------------------------
    | Hostname
    |--------------------------------------------------------------------------
    |
    | In most cases you will not need to change this.
    | The Geocodio hostname needs to be overwritten for customers using the
    | Geocodio+HIPAA product, or using on-premise installations
    |
    | Common hostnames: api.geocod.io, api-hipaa.geocod.io
    |
    */

    'hostname' => env('GEOCODIO_HOSTNAME', 'api.geocod.io'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | Changing this will allow you to use an older API version
    | This is not recommended, but can be useful to assist when migrating to
    | a newer API version.
    |
    | The API changelog can be reviewed here: https://www.geocod.io/docs/#changelog
    |
    */

    'api_version' => env('GEOCODIO_API_VERSION', 'v1.7'),

];
