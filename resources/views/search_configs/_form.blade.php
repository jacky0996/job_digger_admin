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
        <label for="filter_tags" class="block text-sm font-medium text-slate-700">
            過濾標籤 (清洗時使用)
        </label>
        <textarea id="filter_tags"
                  name="filter_tags"
                  rows="4"
                  placeholder="以逗號分隔，例如：php,PHP,軟體,資訊,後端"
                  class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono">{{ old('filter_tags', $config->filter_tags ?? '') }}</textarea>
        <p class="mt-1.5 text-xs text-slate-500">
            清洗階段會檢查職缺標題是否包含其中任一標籤。半形或全形逗號、頓號、換行皆可分隔，儲存時會自動正規化。
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
