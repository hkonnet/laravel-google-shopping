<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Modes
    |--------------------------------------------------------------------------
    |
    | This package supports sandbox and production modes.
    | You may specify which one you're using throughout
    | your application here.
    |
    | Supported: "sandbox", "production"
    |
    */
    'mode' => env('GS_MODE', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | Name of the application provided to Google.
    |
    */
    'app_name' => env('GS_APP_NAME', 'My Application'),

    /*
    |--------------------------------------------------------------------------
    | Config Dir
    |--------------------------------------------------------------------------
    |
    | This is configuration directory path where your google configuration files resides.
    | For example, service account json file | O auth client json file.
    | A service account details can be read from here
    |(https://developers.google.com/shopping-content/v2/how-tos/service-accounts).
    |
    |
    */
    'config_dir' => base_path().'/'.env('GS_CONFIG_DIR',''),

    /*
    |--------------------------------------------------------------------------
    | Auth Type
    |--------------------------------------------------------------------------
    |
    | There are multiple authentication methods available there are (Google Application Default Credentials,
    | Service accounts credentials, OAuth2 client credentials). Options for this parameters are
    | (default_cred, service_account_cred, o_auth2_cred). Default is service_account_cred
    |
    |
    */
    'authType' => env('GS_AUTH_TYPE','service_account_cred'),

    /*
    |--------------------------------------------------------------------------
    | Access Files
    |--------------------------------------------------------------------------
    |
    | Filenames of access files
    |
    */
    'file_name' => [
        'service_account_filename' => env('GS_SERVICE_ACC_FILENAME'),
    ]
];