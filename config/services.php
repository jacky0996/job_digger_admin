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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // job-digger Python FastAPI(Stage A/B/C 爬蟲觸發)
    // - url:給瀏覽器用(使用者前台手動觸發 fetch),host POV
    // - internal_url:給後端 PHP 用(scheduler / Http::post),從 admin container 看
    //   要走 host.docker.internal 才能打到 host 上的 uvicorn:85
    'job_digger' => [
        'url'           => env('JOB_DIGGER_API_URL', 'http://localhost:85'),
        'internal_url'  => env('JOB_DIGGER_INTERNAL_URL', 'http://host.docker.internal:85'),
        'poll_interval' => (int) env('JOB_DIGGER_POLL_INTERVAL', 30),
        'task_timeout'  => (int) env('JOB_DIGGER_TASK_TIMEOUT', 7200),
    ],

];
