<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\SsoJwtVerifier;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * SSO Web-Mode 守衛。
 *
 * 流程設計參考 Laravel 內建的 Auth middleware,加上對中台 SSO 的整合:
 *
 *   1. 已登入 (Laravel session) → 直接放行
 *   2. URL 帶 ?token=<JWT> (從中台 callback 過來) → 驗 → User::firstOrCreate
 *      → Auth::login → redirect intended() (保留使用者原本想去的頁)
 *   3. 都沒有 → 存 intended URL → redirect 中台 /sso/login,
 *      callback URL 固定指向 /sso/callback (避免帶業務頁 URL 過去,
 *      因為中台白名單可能沒收業務頁;callback 是固定入口)
 *
 * 為何 callback 也要套這個 middleware:
 *   callback 收到的 token 也是由 middleware 統一驗證,
 *   這樣 controller 不必自己解 JWT,邏輯只有一處。
 */
class AuthorizeJwtSso
{
    public function __construct(private SsoJwtVerifier $verifier) {}

    public function handle(Request $request, Closure $next)
    {
        // 1. Already logged in
        if (Auth::check()) {
            return $next($request);
        }

        // 2. Token in query → verify + login
        if ($rawToken = $request->query('token')) {
            try {
                $payload = $this->decodeJwt($rawToken);
                $user = $this->resolveUser($payload);
                Auth::login($user, true);
                $request->session()->regenerate();

                // 跳回使用者原本想去的頁,沒有就回首頁
                return redirect()->intended('/');
            } catch (Exception $e) {
                Log::warning('SSO JWT decode failed: ' . $e->getMessage(), [
                    'token_sample' => substr($rawToken, 0, 15) . '...',
                ]);
                // Fall through to redirect-to-IdP
            }
        }

        // 3. No session, no valid token → bounce to IdP
        $this->storeIntendedUrl($request);

        $ssoLoginUrl = rtrim(config('sso.middle_platform_url', 'http://localhost'), '/')
            . '/sso/login/';
        $callbackUrl = rtrim(config('app.url', 'http://localhost:84'), '/')
            . '/sso/callback';

        return redirect()->away(
            $ssoLoginUrl . '?redirect=' . urlencode($callbackUrl)
        );
    }

    /**
     * 驗章工作委派給 SsoJwtVerifier — 同時支援 HS256 (legacy) 與 RS256/JWKS。
     * 演算法由 config/sso.php 控制,所有業務系統共用同一驗章原則。
     */
    private function decodeJwt(string $token): object
    {
        return $this->verifier->decode($token);
    }

    /**
     * Find or create the local user mirror by email.
     * Local user 只是 session-carrier,中台才是真實身分來源。
     */
    private function resolveUser(object $payload): User
    {
        $email = $payload->email ?? null;
        if (!$email) {
            throw new Exception('JWT payload missing email claim');
        }

        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $payload->display_name ?? Str::before($email, '@'),
                // Passwordless: 寫一個無法被 check_password 命中的隨機字串
                'password' => bcrypt(Str::random(40)),
            ]
        );
    }

    /**
     * 存使用者原本想去的 URL,登入完後 redirect()->intended() 會用到。
     * 排除 /sso/* 路由(避免登入完還跳回 callback)。
     */
    private function storeIntendedUrl(Request $request): void
    {
        if (str_starts_with($request->path(), 'sso/')) {
            return;
        }

        $request->session()->put('url.intended', $request->fullUrl());
    }
}
