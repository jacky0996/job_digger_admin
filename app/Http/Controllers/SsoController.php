<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * SSO 進站 / 離站處理。
 *
 * 注意:核心驗證邏輯在 AuthorizeJwtSso middleware,本 controller 只負責:
 *   - /sso/callback : middleware 已處理 token,這裡只是個著陸點(redirect 到首頁)
 *   - /sso/logout   : 清 Laravel session 並導去中台登出
 */
class SsoController extends Controller
{
    /**
     * Token 接收頁。
     * 中台簽完 JWT 後 redirect 到這裡並帶 ?token=... ,
     * AuthorizeJwtSso middleware 會在進入此 action 前驗 + 登入,
     * 所以本 method 通常不會被執行(會被 middleware 的 redirect 蓋掉)。
     */
    public function callback(Request $request)
    {
        // Middleware 已經完成驗證 + Auth::login
        return redirect()->intended('/');
    }

    /**
     * 前端 idle ping — 回傳目前 session 是否仍有效。
     *
     * 故意不掛 AuthorizeJwtSso middleware,session 失效時直接回 401 給前端,
     * 避免被 middleware 直接 302 到中台(那會把 ping 變成完整登入流程)。
     * 前端拿到 401 才自己決定要不要 reload / 提示使用者。
     */
    public function ping(): JsonResponse
    {
        if (Auth::check()) {
            return response()->json(['ok' => true]);
        }
        return response()->json(['ok' => false], 401);
    }

    /**
     * 登出:清本地 session,然後 redirect 到中台 /sso/logout。
     * 中台 logout 後若帶 redirect 會跳回 admin,我們導回首頁(會再被 middleware 攔到中台登入)。
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $ssoLogoutUrl = rtrim(config('sso.middle_platform_url', 'http://localhost'), '/')
            . '/sso/logout/';

        return redirect()->away($ssoLogoutUrl);
    }
}
