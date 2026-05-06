<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Middle Platform (IdP) URL
    |--------------------------------------------------------------------------
    |
    | 中台 SSO 服務的對外 URL。本系統會把使用者導到 {middle_platform_url}/sso/login/
    | 進行登入,登入後中台會 redirect 回 {APP_URL}/sso/callback?token=<JWT>。
    |
    */
    'middle_platform_url' => env('MIDDLE_PLATFORM_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | SSO JWT 驗證密鑰 (HS256)
    |--------------------------------------------------------------------------
    |
    | 必須跟中台 (Middle_Platform) 的 DJANGO_SECRET_KEY 完全一致。
    | 故意跟 APP_KEY 分開:
    |   - APP_KEY 是 Laravel 內部加密用 (AES-256-CBC,要求 exactly 32 bytes / base64:)
    |   - SSO_JWT_SECRET 是 HS256 共享密鑰,長度寬鬆,跟著中台走
    |
    */
    'jwt_secret' => env('SSO_JWT_SECRET', env('APP_KEY')),
];
