@csrf

<div class="space-y-5">
    <div>
        <label for="keyword" class="block text-sm font-medium text-slate-700">
            搜尋關鍵字 <span class="text-rose-500">*</span>
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
            104 搜尋使用的主關鍵字（最多 50 字、不可重複）。
        </p>
    </div>

    <div>
        <label for="title_tags" class="block text-sm font-medium text-slate-700">
            標題過濾標籤 (Stage A：搜尋結果收斂)
        </label>
        <textarea id="title_tags"
                  name="title_tags"
                  rows="3"
                  placeholder="例如:SA,系統分析,Analyst,Architect"
                  class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono">{{ old('title_tags', $config->title_tags ?? '') }}</textarea>
        <p class="mt-1.5 text-xs text-slate-500">
            <strong>標題層過濾</strong>:104 搜尋結果中,**標題包含其中任一**才會寫入 vacancies(用來收斂職缺類別)。
            半形/全形逗號、頓號、換行皆可分隔。
        </p>
    </div>

    <div>
        <label for="content_tags" class="block text-sm font-medium text-slate-700">
            內文過濾標籤 (Stage B:工作內容/條件/擅長工具)
        </label>
        <textarea id="content_tags"
                  name="content_tags"
                  rows="3"
                  placeholder="例如:PHP,php,Laravel,laravel"
                  class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono">{{ old('content_tags', $config->content_tags ?? '') }}</textarea>
        <p class="mt-1.5 text-xs text-slate-500">
            <strong>內文層過濾</strong>:Stage B 會打開職缺頁,檢查
            <code class="text-slate-700">.job-description__content</code> /
            <code class="text-slate-700">.job-requirement</code> /
            擅長工具 是否含其中任一,以決定 check_type 分類。
        </p>
    </div>

    <div class="rounded-md bg-blue-50 p-3 text-xs text-slate-700 ring-1 ring-blue-100">
        💡 <strong>例</strong>:想找「需要 PHP 技能的 SA 職缺」 →
        關鍵字 = <code>SA</code>、標題標籤 = <code>SA,系統分析,Architect</code>、內文標籤 = <code>PHP,php,Laravel</code>。
        Stage A 把標題像 SA 的職缺都收進來,Stage B 再用 PHP/Laravel 在內文裡精準篩。
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
