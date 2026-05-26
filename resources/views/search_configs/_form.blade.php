@csrf

<div class="space-y-5">
    {{-- ─────────────────────────────────────────────────────────
         AI 助手區塊 (mockup,目前回假資料,後端 LLM 整合於 Phase 2)
         使用者填自然語言描述 → 按 AI 建議 → 自動填下方三個欄位
    ───────────────────────────────────────────────────────── --}}
    <div id="ai-assistant" class="rounded-lg border border-purple-200 bg-gradient-to-br from-purple-50 to-indigo-50 p-4">
        <div class="flex items-start gap-2 mb-3">
            <span class="text-xl leading-none">🤖</span>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-slate-800">不知道怎麼填? 試試 AI 助手</h3>
                <p class="text-xs text-slate-600 mt-0.5">用自然語言描述你想找的工作,AI 會幫你產生關鍵字配置</p>
            </div>
        </div>

        <label for="ai_intent" class="sr-only">描述你想找的工作</label>
        <textarea id="ai_intent"
                  rows="3"
                  maxlength="500"
                  placeholder="例:&#10;• 我想找資深 PHP 後端,有 Laravel 經驗&#10;• 5 年以上的 React 前端工程師&#10;• DevOps,要會 K8s 跟 Terraform"
                  class="block w-full rounded-md border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm bg-white/70"></textarea>

        <div class="mt-2 flex flex-wrap gap-1.5">
            <span class="text-xs text-slate-500 mr-1">📌 範例:</span>
            <button type="button" data-example="資深 PHP 後端,有 Laravel 經驗"
                    class="ai-example-chip text-xs px-2 py-0.5 rounded-full bg-white text-slate-700 border border-slate-300 hover:bg-purple-50 hover:border-purple-300 transition">
                資深 PHP 後端
            </button>
            <button type="button" data-example="5 年以上的 React 前端,需要 TypeScript"
                    class="ai-example-chip text-xs px-2 py-0.5 rounded-full bg-white text-slate-700 border border-slate-300 hover:bg-purple-50 hover:border-purple-300 transition">
                React 前端
            </button>
            <button type="button" data-example="DevOps 工程師,要會 Kubernetes 跟 Terraform"
                    class="ai-example-chip text-xs px-2 py-0.5 rounded-full bg-white text-slate-700 border border-slate-300 hover:bg-purple-50 hover:border-purple-300 transition">
                DevOps K8s
            </button>
            <button type="button" data-example="全端工程師,Node.js + React"
                    class="ai-example-chip text-xs px-2 py-0.5 rounded-full bg-white text-slate-700 border border-slate-300 hover:bg-purple-50 hover:border-purple-300 transition">
                全端工程師
            </button>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2 justify-between">
            <p class="text-xs text-slate-500">
                💡 可提供:技術、年資、公司類型 ·
                <span class="text-amber-600">⚠️ 公司規模/排除派遣請事後在列表頁過濾</span>
            </p>
            <button type="button" id="ai_suggest_btn"
                    class="inline-flex items-center gap-1 rounded-md bg-purple-600 px-3 py-1.5 text-xs font-medium text-slate-900 shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-purple-500 transition disabled:opacity-50 disabled:cursor-not-allowed">
                <span id="ai_suggest_label">✨ AI 建議</span>
            </button>
        </div>

        {{-- 錯誤訊息區 --}}
        <div id="ai_error" class="hidden mt-2 rounded-md bg-rose-50 border border-rose-200 px-3 py-2 text-xs text-rose-700"></div>

        {{-- AI Reasoning 摺疊區 --}}
        <details id="ai_reasoning_wrap" class="hidden mt-3 rounded-md bg-white/70 border border-purple-100 px-3 py-2 text-xs text-slate-700">
            <summary class="cursor-pointer font-medium text-purple-700 select-none">💡 AI 為什麼這樣建議?</summary>
            <p id="ai_reasoning" class="mt-2 leading-relaxed whitespace-pre-line"></p>
        </details>
    </div>

    <div class="relative flex items-center">
        <div class="flex-grow border-t border-slate-200"></div>
        <span class="mx-3 flex-shrink text-xs text-slate-400">或者直接手動填寫</span>
        <div class="flex-grow border-t border-slate-200"></div>
    </div>

    <div>
        <label for="keyword" class="block text-sm font-medium text-slate-700">
            搜尋關鍵字 <span class="text-rose-500">*</span>
            <span data-ai-badge="keyword" class="hidden ml-1 align-middle text-[10px] px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 font-normal">🤖 AI 填入</span>
        </label>
        <input type="text"
               id="keyword"
               name="keyword"
               value="{{ old('keyword', $config->keyword ?? '') }}"
               maxlength="50"
               required
               placeholder="例如：php"
               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
        <p class="mt-1.5 text-xs text-slate-500">
            104 搜尋使用的主關鍵字
        </p>
    </div>

    <div>
        <label for="title_tags" class="block text-sm font-medium text-slate-700">
            標題過濾標籤
            <span data-ai-badge="title_tags" class="hidden ml-1 align-middle text-[10px] px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 font-normal">🤖 AI 填入</span>
        </label>
        <textarea id="title_tags"
                  name="title_tags"
                  rows="3"
                  placeholder="例如:SA,系統分析,Analyst,Architect"
                  class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono">{{ old('title_tags', $config->title_tags ?? '') }}</textarea>
        <p class="mt-1.5 text-xs text-slate-500">
            <strong>標題層過濾</strong>:104 搜尋結果中,**標題包含其中任一**才會爬回本地。
            半形/全形逗號、頓號、換行皆可分隔。
        </p>
    </div>

    <div>
        <label for="content_tags" class="block text-sm font-medium text-slate-700">
            內文過濾標籤
            <span data-ai-badge="content_tags" class="hidden ml-1 align-middle text-[10px] px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 font-normal">🤖 AI 填入</span>
        </label>
        <textarea id="content_tags"
                  name="content_tags"
                  rows="3"
                  placeholder="例如:PHP,php,Laravel,laravel"
                  class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono">{{ old('content_tags', $config->content_tags ?? '') }}</textarea>
        <p class="mt-1.5 text-xs text-slate-500">
            <strong>內文層過濾</strong>:會打開職缺頁,檢查工作內容/條件/擅長工具 是否含其中任一,以決定分類。
        </p>
    </div>

    <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 pt-2">
        <a href="{{ route('search-configs.index') }}"
           class="inline-flex justify-center items-center rounded-md bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            取消
        </a>
        <button type="submit"
                class="inline-flex justify-center items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
            {{ $submitLabel ?? '儲存' }}
        </button>
    </div>
</div>

{{-- ─────────────────────────────────────────────────────────────
     AI 助手 — 前端互動腳本(目前回假資料,Phase 2 換成真的 LLM endpoint)
     - 點範例 chip:塞文字到 textarea
     - 點 ✨ AI 建議:模擬 1.2s 延遲後填入假資料 + 顯示 reasoning
     - 點 🔄 重新建議:用同一段 intent 再 call 一次(微微改一下假資料)
     - 任一欄被使用者編輯後,移除該欄的 🤖 AI 填入 badge
───────────────────────────────────────────────────────────────── --}}
<script>
(function () {
    const intentEl    = document.getElementById('ai_intent');
    const suggestBtn  = document.getElementById('ai_suggest_btn');
    const labelEl     = document.getElementById('ai_suggest_label');
    const errorEl     = document.getElementById('ai_error');
    const reasoningWrap = document.getElementById('ai_reasoning_wrap');
    const reasoningEl   = document.getElementById('ai_reasoning');

    const fieldKeyword   = document.getElementById('keyword');
    const fieldTitleTags = document.getElementById('title_tags');
    const fieldContent   = document.getElementById('content_tags');

    // 範例 chip → 塞進 textarea
    document.querySelectorAll('.ai-example-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            intentEl.value = btn.dataset.example || '';
            intentEl.focus();
        });
    });

    // 任一欄被手動改 → 拿掉該欄的 AI badge(代表使用者已接管)
    [
        ['keyword',     fieldKeyword],
        ['title_tags',  fieldTitleTags],
        ['content_tags', fieldContent],
    ].forEach(([name, el]) => {
        el.addEventListener('input', () => {
            const badge = document.querySelector(`[data-ai-badge="${name}"]`);
            if (badge) badge.classList.add('hidden');
        });
    });

    function showError(msg) {
        errorEl.textContent = msg;
        errorEl.classList.remove('hidden');
    }
    function clearError() {
        errorEl.classList.add('hidden');
        errorEl.textContent = '';
    }
    function setLoading(loading) {
        suggestBtn.disabled = loading;
        labelEl.textContent = loading ? '⏳ AI 思考中...' : '✨ AI 建議';
    }
    function showAiBadges() {
        document.querySelectorAll('[data-ai-badge]').forEach(b => b.classList.remove('hidden'));
    }

    const endpoint = "{{ route('llm.suggest-config') }}";
    // 取 CSRF token:優先用 meta(若 layout 有放),否則 fallback 到 form 內的 _token hidden input
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';

    async function callAi() {
        const intent = (intentEl.value || '').trim();
        clearError();

        if (intent.length < 5) {
            showError('請描述得更具體一點,例如「找 PHP 後端」或「資深 Laravel 工程師」');
            return;
        }

        setLoading(true);
        try {
            const resp = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ intent }),
            });

            const payload = await resp.json().catch(() => ({}));

            if (!resp.ok || !payload.ok) {
                const msg = payload.error
                    || (payload.message ? payload.message : `AI 服務回應 ${resp.status}`);
                showError(msg);
                return;
            }

            const data = payload.data || {};
            fieldKeyword.value   = data.keyword || '';
            fieldTitleTags.value = data.title_tags || '';
            fieldContent.value   = data.content_tags || '';

            reasoningEl.textContent = data.reasoning || '';
            reasoningWrap.classList.toggle('hidden', !data.reasoning);
            reasoningWrap.open = false;

            showAiBadges();
        } catch (e) {
            showError('連線 AI 服務失敗,請手動填寫或稍後再試。(' + e.message + ')');
        } finally {
            setLoading(false);
        }
    }

    suggestBtn.addEventListener('click', callAi);
})();
</script>
