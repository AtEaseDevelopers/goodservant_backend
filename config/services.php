<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
     'e_invoice' => [
        'enabled' => env('E_INVOICE_ENABLED', false),
        'required_fields' => ['customer_tin', 'customer_tax_address'],
    ],
     'deploy' => [
        // Shared secret for POST /api/v1/app/version (mobile release build hook).
        'version_token' => env('DEPLOY_VERSION_TOKEN'),
    ],
     'autocount' => [
        // Shared secret for the AutoCount plugin's sync endpoints
        // (POST /api/v1/autocount/*). Must match OmsApiToken in the plugin's
        // App.config. Defaults to the value shipped with the plugin so the
        // integration works before .env is configured.
        'token' => env('AUTOCOUNT_API_TOKEN', '9afcf222a8e873cc6fcb22359298c45451f0024077d5463edac433652d6d3cb0'),
        // Dry run: when true the endpoints log the received request and return
        // success WITHOUT writing anything, so a developer can inspect what the
        // plugin sends on a live server without mutating data.
        'dry_run' => env('AUTOCOUNT_DRY_RUN', false),
    ],
     'myinvois' => [
        'client_id' => env('MYINVOIS_CLIENT_ID'),
        'client_secret' => env('MYINVOIS_CLIENT_SECRET'),
        'api_url' => env('MYINVOIS_API_URL', 'https://api.myinvois.hasil.gov.my'),
        'portal_url' => env('MYINVOIS_PORTAL_URL', 'https://myinvois.hasil.gov.my'),
    ],
];
