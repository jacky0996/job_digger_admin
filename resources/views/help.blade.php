@extends('layouts.app')

@section('title', '使用說明')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-[220px_minmax(0,1fr)] gap-6">

        {{-- Sidebar TOC --}}
        <aside class="lg:sticky lg:top-6 self-start">
            <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 p-4">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">目錄</h3>
                <nav class="space-y-1 text-sm">
                    <a href="#overview" class="block px-2 py-1.5 rounded hover:bg-slate-50 text-slate-700">系統簡介</a>
                    <a href="#workflow" class="block px-2 py-1.5 rounded hover:bg-slate-50 text-slate-700">使用流程</a>
                    <a href="#search-configs" class="block px-2 py-1.5 rounded hover:bg-slate-50 text-slate-700">關鍵字設定</a>
                    <a href="#vacancies-search" class="block px-2 py-1.5 rounded hover:bg-slate-50 text-slate-700">職缺搜尋</a>
                    <a href="#filter-tags" class="block px-2 py-1.5 rounded hover:bg-slate-50 text-slate-700">過濾標籤規則</a>
                    <a href="#faq" class="block px-2 py-1.5 rounded hover:bg-slate-50 text-slate-700">常見問題</a>
                </nav>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="space-y-6 min-w-0">

            {{-- Header --}}
            <section class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 px-5 sm:px-8 py-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">使用說明</h1>
                <p class="mt-2 text-sm text-slate-500">本後台搭配 Job Digger 爬蟲服務使用，用於管理搜尋關鍵字與檢視抓取的職缺。</p>
            </section>

            {{-- Overview --}}
            <section id="overview" class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 px-5 sm:px-8 py-6 scroll-mt-6">
                <h2 class="text-xl font-semibold text-slate-900">系統簡介</h2>
                <p class="mt-3 text-sm leading-7 text-slate-700">
                    Job Digger Admin 是 Job Digger 專案的管理後台，與 Python 端的爬蟲共用同一個資料庫
                    本後台僅做設定與檢視，不直接執行爬取；爬蟲與清洗由另一個 Python 服務負責。
                </p>
                <ul class="mt-4 space-y-2 text-sm text-slate-700">
                    <li class="flex gap-2">
                        <span class="text-blue-600 font-bold">·</span>
                        <span><strong>關鍵字設定</strong>：維護要爬的關鍵字、以及清洗時的過濾標籤。</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-blue-600 font-bold">·</span>
                        <span><strong>職缺搜尋</strong>：依關鍵字檢視已抓取的職缺資料。</span>
                    </li>
                </ul>
            </section>

            {{-- Workflow --}}
            <section id="workflow" class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 px-5 sm:px-8 py-6 scroll-mt-6">
                <h2 class="text-xl font-semibold text-slate-900">使用流程</h2>
                <ol class="mt-4 space-y-4 text-sm text-slate-700">
                    <li class="flex gap-3">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">1</span>
                        <div>
                            <p class="font-medium text-slate-900">在「關鍵字設定」新增關鍵字</p>
                            <p class="mt-1 text-slate-600">填入要在 104 上搜尋的關鍵字，並設定清洗用的過濾標籤。</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">2</span>
                        <div>
                            <p class="font-medium text-slate-900">由 Python 端的爬蟲依關鍵字抓取資料</p>
                            <p class="mt-1 text-slate-600">資料會落到 資料庫，再進行資料清洗。</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">3</span>
                        <div>
                            <p class="font-medium text-slate-900">在「職缺搜尋」檢視結果</p>
                            <p class="mt-1 text-slate-600">選擇關鍵字後按搜尋，列表分頁瀏覽，可點職缺名稱直接跳到 104 原始頁面。</p>
                        </div>
                    </li>
                </ol>
            </section>

            {{-- Search configs --}}
            <section id="search-configs" class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 px-5 sm:px-8 py-6 scroll-mt-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <h2 class="text-xl font-semibold text-slate-900">關鍵字設定</h2>
                    <a href="{{ route('search-configs.index') }}"
                       class="text-sm text-blue-600 hover:text-blue-700">前往頁面 →</a>
                </div>

                <p class="mt-3 text-sm leading-7 text-slate-700">
                    路徑：<code class="px-1.5 py-0.5 bg-slate-100 rounded text-xs">/search-configs</code>。
                    管理 104 爬蟲使用的關鍵字與清洗階段的過濾標籤。
                </p>

                <h3 class="mt-5 text-sm font-semibold text-slate-900">操作</h3>
                <ul class="mt-2 space-y-1.5 text-sm text-slate-700">
                    <li class="flex gap-2"><span class="text-slate-400">·</span><span><strong>新增</strong>：點右上「新增關鍵字」，填表後送出。</span></li>
                    <li class="flex gap-2"><span class="text-slate-400">·</span><span><strong>編輯</strong>：列表中按「編輯」修改既有設定。</span></li>
                    <li class="flex gap-2"><span class="text-slate-400">·</span><span><strong>刪除</strong>：列表中按「刪除」並確認，會直接從資料表移除。</span></li>
                </ul>

                <h3 class="mt-5 text-sm font-semibold text-slate-900">欄位說明</h3>
                <div class="mt-2 overflow-x-auto">
                    <table class="min-w-full text-sm border border-slate-200 rounded">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-40">欄位</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">說明</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr>
                                <td class="px-3 py-2 text-slate-900 font-medium align-top">搜尋關鍵字 <span class="text-rose-500">*</span></td>
                                <td class="px-3 py-2 text-slate-700">爬蟲實際丟給 104 的字串。<strong>不可重複</strong>，最長 50 字。</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-slate-900 font-medium align-top">過濾標籤</td>
                                <td class="px-3 py-2 text-slate-700">
                                    清洗階段用來過濾職缺標題的關鍵字群。可空白；分隔符可用半形/全形逗號 (<code class="px-1 bg-slate-100 rounded text-xs">,</code> / <code class="px-1 bg-slate-100 rounded text-xs">，</code>)、頓號 (<code class="px-1 bg-slate-100 rounded text-xs">、</code>)、或換行；存檔時自動正規化為「半形逗號分隔」。
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Vacancies search --}}
            <section id="vacancies-search" class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 px-5 sm:px-8 py-6 scroll-mt-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <h2 class="text-xl font-semibold text-slate-900">職缺搜尋</h2>
                    <a href="{{ route('vacancies.search') }}"
                       class="text-sm text-blue-600 hover:text-blue-700">前往頁面 →</a>
                </div>

                <p class="mt-3 text-sm leading-7 text-slate-700">
                    路徑：<code class="px-1.5 py-0.5 bg-slate-100 rounded text-xs">/vacancies/search</code>。
                    依關鍵字檢視已抓取的職缺；下拉選項來自「關鍵字設定」。
                </p>

                <h3 class="mt-5 text-sm font-semibold text-slate-900">操作</h3>
                <ul class="mt-2 space-y-1.5 text-sm text-slate-700">
                    <li class="flex gap-2"><span class="text-slate-400">·</span><span>從下拉選單挑選關鍵字，按「<strong>搜尋</strong>」即列出對應的職缺。</span></li>
                    <li class="flex gap-2"><span class="text-slate-400">·</span><span>每頁 30 筆，可分頁瀏覽。網址帶 <code class="px-1 bg-slate-100 rounded text-xs">?keyword=xxx&page=N</code>，可直接分享。</span></li>
                    <li class="flex gap-2"><span class="text-slate-400">·</span><span>點「<strong>職缺職稱</strong>」或「<strong>公司</strong>」會在新分頁開啟 104 原始頁面。</span></li>
                    <li class="flex gap-2"><span class="text-slate-400">·</span><span>「<strong>更新資料</strong>」按鈕：點擊後會呼叫 Python API，針對所選關鍵字啟動背景爬蟲任務。</span></li>
                </ul>

                <h3 class="mt-5 text-sm font-semibold text-slate-900">表格欄位</h3>
                <div class="mt-2 overflow-x-auto">
                    <table class="min-w-full text-sm border border-slate-200 rounded">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-32">欄位</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">說明</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr><td class="px-3 py-2 font-medium text-slate-900 align-top">ID</td><td class="px-3 py-2 text-slate-700">資料表流水號。</td></tr>
                            <tr><td class="px-3 py-2 font-medium text-slate-900 align-top">職缺職稱</td><td class="px-3 py-2 text-slate-700">點擊跳到 104 職缺頁。</td></tr>
                            <tr><td class="px-3 py-2 font-medium text-slate-900 align-top">公司</td><td class="px-3 py-2 text-slate-700">點擊跳到 104 公司頁。</td></tr>
                            <tr><td class="px-3 py-2 font-medium text-slate-900 align-top">薪資</td><td class="px-3 py-2 text-slate-700">原始薪資文字（未轉換）。</td></tr>
                            <tr><td class="px-3 py-2 font-medium text-slate-900 align-top">資本額 / 員工數</td><td class="px-3 py-2 text-slate-700">由 Python 端 Stage B 補資料；尚未補完會顯示「—」。</td></tr>
                            <tr><td class="px-3 py-2 font-medium text-slate-900 align-top">狀態</td><td class="px-3 py-2 text-slate-700"><span class="inline-flex items-center rounded bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">在徵</span> 或 <span class="inline-flex items-center rounded bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">已關</span>。</td></tr>
                            <tr><td class="px-3 py-2 font-medium text-slate-900 align-top">抓取時間</td><td class="px-3 py-2 text-slate-700">爬蟲第一次抓到該職缺的時間。</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Filter tags --}}
            <section id="filter-tags" class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 px-5 sm:px-8 py-6 scroll-mt-6">
                <h2 class="text-xl font-semibold text-slate-900">過濾標籤規則</h2>
                <p class="mt-3 text-sm leading-7 text-slate-700">
                    清洗階段會檢查每筆職缺的標題：<strong>標題只要包含其中任一個過濾標籤的字串，就會被保留</strong>，否則丟棄。
                </p>

                <div class="mt-4 rounded-md bg-slate-50 ring-1 ring-slate-200 p-4 text-sm">
                    <p class="font-medium text-slate-900">範例</p>
                    <p class="mt-2 text-slate-700">關鍵字：<code class="px-1.5 py-0.5 bg-white rounded ring-1 ring-slate-200 text-xs">php</code></p>
                    <p class="mt-1 text-slate-700">過濾標籤：<code class="px-1.5 py-0.5 bg-white rounded ring-1 ring-slate-200 text-xs">php,PHP,軟體,資訊,後端</code></p>
                    <ul class="mt-3 space-y-1.5 text-slate-700">
                        <li class="flex gap-2"><span class="text-emerald-600 font-bold">✓</span><span>「資深 PHP 後端工程師」 — 標題含 <em>PHP</em> 與 <em>後端</em>，保留。</span></li>
                        <li class="flex gap-2"><span class="text-emerald-600 font-bold">✓</span><span>「軟體開發工程師」 — 標題含 <em>軟體</em>，保留。</span></li>
                        <li class="flex gap-2"><span class="text-rose-600 font-bold">✗</span><span>「行銷企劃」 — 不含任何標籤，過濾掉。</span></li>
                    </ul>
                </div>

                <p class="mt-4 text-sm text-slate-600">
                    註：比對為大小寫敏感，所以建議同時填入 <em>php</em> 與 <em>PHP</em>。中文則不分大小寫。
                </p>
            </section>

            {{-- FAQ --}}
            <section id="faq" class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 px-5 sm:px-8 py-6 scroll-mt-6">
                <h2 class="text-xl font-semibold text-slate-900">常見問題</h2>

                <div class="mt-4 space-y-5 text-sm">
                    <div>
                        <p class="font-medium text-slate-900">Q. 在後台新增關鍵字後，多久能看到資料？</p>
                        <p class="mt-1 text-slate-700 leading-7">A. 取決於 Python 爬蟲的排程。本後台只負責設定，實際抓取由另一個服務執行。</p>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900">Q. 為什麼下拉選單裡只有特定關鍵字？</p>
                        <p class="mt-1 text-slate-700 leading-7">A. 下拉選項來自「關鍵字設定」表內的 <code class="px-1 bg-slate-100 rounded text-xs">keyword</code> 欄位；要新增請先到關鍵字設定頁建立。</p>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900">Q. 「更新資料」按鈕的功能是什麼？</p>
                        <p class="mt-1 text-slate-700 leading-7">A. 此功能會直接呼叫 Python 端 (Port 83) 的 API，並將該關鍵字的 ID 送出。Python 服務接收後會立即在背景啟動完整的抓取與清洗流程 (Stage A, C, B)。</p>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900">Q. 刪除關鍵字會不會連帶刪除已抓的職缺？</p>
                        <p class="mt-1 text-slate-700 leading-7">A. 不會。「關鍵字設定」與 <code class="px-1 bg-slate-100 rounded text-xs">vacancies</code> 沒有外鍵串接，刪除後既有職缺仍保留在資料表中。</p>
                    </div>
                </div>
            </section>

        </div>
    </div>
@endsection
