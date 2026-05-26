<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LLM 助手:把使用者「想找什麼工作」的自然語言翻譯成 search_config 三欄。
 *
 * 本 controller 只是把請求 forward 到 job-digger Python service 的 /api/llm/suggest-config,
 * 因為 Anthropic SDK 在 Python 端,key 也統一管在那裡。前端打這支,我們轉發 + 包裝錯誤。
 */
class SearchConfigLlmController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'intent' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $base = rtrim((string) config('services.job_digger.internal_url'), '/');
        $url = "{$base}/api/llm/suggest-config";

        try {
            $resp = Http::timeout(30)->post($url, ['intent' => $data['intent']]);
        } catch (\Throwable $e) {
            Log::warning('[llm-suggest] connect failed', ['err' => $e->getMessage()]);

            return response()->json([
                'ok'    => false,
                'error' => '無法連線 LLM 服務,請稍後再試',
            ], 503);
        }

        if (! $resp->successful()) {
            $detail = $resp->json('detail') ?? $resp->body();
            Log::info('[llm-suggest] upstream non-2xx', ['status' => $resp->status(), 'detail' => $detail]);

            return response()->json([
                'ok'    => false,
                'error' => $this->humanizeError($resp->status(), $detail),
            ], $resp->status());
        }

        // 直接回傳 upstream 的 payload(keyword / title_tags / content_tags / reasoning)
        return response()->json([
            'ok'   => true,
            'data' => $resp->json(),
        ]);
    }

    /** 把 upstream 錯誤訊息翻成給使用者看的話。 */
    private function humanizeError(int $status, $detail): string
    {
        if ($status === 503) {
            $msg = (string) $detail;
            if (str_contains($msg, '未設定 GEMINI_API_KEY')) {
                return 'AI 服務尚未設定 API key(請聯絡管理員)';
            }

            return 'AI 服務暫時無法回應,請稍後再試';
        }

        return 'AI 服務發生錯誤,請稍後再試';
    }
}
