@extends('layouts.app')

@section('title', '關鍵字設定')

@php
    // 給 JS fetch 用 — 瀏覽器看得到的 host POV URL
    $apiBase = rtrim((string) config('services.job_digger.url'), '/');
@endphp

@section('content')
    <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200">
        <div class="px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200">
            <div>
                <h2 class="text-lg sm:text-xl font-semibold text-slate-900">104 搜尋關鍵字</h2>
                <p class="text-sm text-slate-500 mt-1">
                    設定要爬取的關鍵字與清洗階段使用的過濾標籤。
                    <span class="text-slate-400">非今日建立的關鍵字會由排程於每日 03:00 自動更新。</span>
                </p>
            </div>
            <a href="{{ route('search-configs.create') }}"
               class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                新增關鍵字
            </a>
        </div>

        @if ($configs->isEmpty())
            <div class="px-4 sm:px-6 py-10 text-center text-slate-500">
                尚未建立任何搜尋關鍵字。
            </div>
        @else
            {{-- Mobile: card list --}}
            <ul class="md:hidden divide-y divide-slate-200">
                @foreach ($configs as $config)
                    <li class="px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-400">#{{ $config->id }}</span>
                                    <span class="font-semibold text-slate-900 truncate">{{ $config->keyword }}</span>
                                </div>
                                <div class="mt-2 space-y-1">
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-[10px] uppercase tracking-wider text-slate-400 mr-1">標題</span>
                                        @forelse ($config->title_tags_array as $tag)
                                            <span class="inline-flex items-center rounded bg-blue-50 px-2 py-0.5 text-xs text-blue-700 ring-1 ring-blue-100">{{ $tag }}</span>
                                        @empty
                                            <span class="text-xs text-slate-400">—</span>
                                        @endforelse
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-[10px] uppercase tracking-wider text-slate-400 mr-1">內文</span>
                                        @forelse ($config->content_tags_array as $tag)
                                            <span class="inline-flex items-center rounded bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700 ring-1 ring-emerald-100">{{ $tag }}</span>
                                        @empty
                                            <span class="text-xs text-slate-400">—</span>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="mt-2 text-xs text-slate-400 space-y-0.5">
                                    <div>建立 {{ optional($config->created_at)->format('Y-m-d H:i') ?? '—' }} · {{ $config->created_by_email ?? '—' }}</div>
                                    @if ($config->updated_at)
                                        <div>更新 {{ $config->updated_at->format('Y-m-d H:i') }} · {{ $config->updated_by_email ?? '—' }}</div>
                                    @endif
                                    <div>
                                        最後爬蟲
                                        @if ($config->last_scraped_at)
                                            {{ $config->last_scraped_at->format('Y-m-d H:i') }}
                                        @else
                                            <span class="text-slate-300">尚未執行</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 flex gap-2 flex-wrap">
                            <a href="{{ route('search-configs.edit', $config) }}"
                               class="flex-1 text-center rounded-md bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200 transition">
                                編輯
                            </a>

                            @if ($config->isCreatedToday())
                                <button type="button"
                                        onclick="triggerScrape({{ $config->id }}, @js($config->keyword))"
                                        data-scrape-btn="{{ $config->id }}"
                                        class="flex-1 rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    更新
                                </button>
                            @else
                                <span class="flex-1 text-center rounded-md bg-slate-50 px-3 py-1.5 text-xs text-slate-400 ring-1 ring-slate-200">
                                    由排程執行
                                </span>
                            @endif

                            <form method="POST"
                                  action="{{ route('search-configs.destroy', $config) }}"
                                  class="flex-1"
                                  onsubmit="return confirm('確定刪除「{{ $config->keyword }}」？');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full rounded-md bg-rose-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-700 transition">
                                    刪除
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- Desktop: table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-16">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-48">搜尋關鍵字</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">過濾標籤<br><span class="text-[10px] normal-case font-normal text-slate-400">標題=Stage A / 內文=Stage B</span></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-44">建立 / 更新</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-40">最後爬蟲</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-56">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($configs as $config)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm text-slate-500">{{ $config->id }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $config->keyword }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="space-y-1.5">
                                        <div class="flex flex-wrap items-center gap-1">
                                            <span class="text-[10px] uppercase tracking-wider text-slate-400 mr-1 w-8">標題</span>
                                            @forelse ($config->title_tags_array as $tag)
                                                <span class="inline-flex items-center rounded bg-blue-50 px-2 py-0.5 text-xs text-blue-700 ring-1 ring-blue-100">{{ $tag }}</span>
                                            @empty
                                                <span class="text-xs text-slate-400">—</span>
                                            @endforelse
                                        </div>
                                        <div class="flex flex-wrap items-center gap-1">
                                            <span class="text-[10px] uppercase tracking-wider text-slate-400 mr-1 w-8">內文</span>
                                            @forelse ($config->content_tags_array as $tag)
                                                <span class="inline-flex items-center rounded bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700 ring-1 ring-emerald-100">{{ $tag }}</span>
                                            @empty
                                                <span class="text-xs text-slate-400">—</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                    <div>
                                        <span class="text-slate-400">建立</span>
                                        {{ optional($config->created_at)->format('Y-m-d H:i') ?? '—' }}
                                        <div class="text-slate-400 truncate max-w-[14rem]">{{ $config->created_by_email ?? '—' }}</div>
                                    </div>
                                    @if ($config->updated_at)
                                        <div class="mt-1 pt-1 border-t border-slate-100">
                                            <span class="text-slate-400">更新</span>
                                            {{ $config->updated_at->format('Y-m-d H:i') }}
                                            <div class="text-slate-400 truncate max-w-[14rem]">{{ $config->updated_by_email ?? '—' }}</div>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs whitespace-nowrap">
                                    @if ($config->last_scraped_at)
                                        <div class="text-slate-700">
                                            {{ $config->last_scraped_at->format('Y-m-d H:i') }}
                                        </div>
                                        <div class="text-slate-400">
                                            {{ $config->last_scraped_at->diffForHumans() }}
                                        </div>
                                    @else
                                        <span class="text-slate-300">尚未執行</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex gap-2 flex-wrap">
                                        <a href="{{ route('search-configs.edit', $config) }}"
                                           class="rounded-md bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200 transition">
                                            編輯
                                        </a>

                                        @if ($config->isCreatedToday())
                                            <button type="button"
                                                    onclick="triggerScrape({{ $config->id }}, @js($config->keyword))"
                                                    data-scrape-btn="{{ $config->id }}"
                                                    class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                                更新
                                            </button>
                                        @else
                                            <span class="rounded-md bg-slate-50 px-3 py-1.5 text-xs text-slate-400 ring-1 ring-slate-200"
                                                  title="非今日建立的關鍵字由排程自動執行">
                                                由排程執行
                                            </span>
                                        @endif

                                        <form method="POST"
                                              action="{{ route('search-configs.destroy', $config) }}"
                                              onsubmit="return confirm('確定刪除「{{ $config->keyword }}」？');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-md bg-rose-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-700 transition">
                                                刪除
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <script>
        // 觸發 job-digger 爬蟲。先跳 ETL 提示讓使用者確認,再 fetch 後端。
        const JOB_DIGGER_API_BASE = @json($apiBase);

        async function triggerScrape(configId, keyword) {
            const ok = confirm(
                "⚠️ 資料 ETL 提醒\n\n" +
                "即將為「" + keyword + "」執行完整爬蟲流程:\n" +
                "  1. 職缺清單抓取\n" +
                "  2. 內文清洗(關鍵字篩選)\n" +
                "  3. 公司資訊補全\n\n" +
                "依職缺數量,預計需 30~60 分鐘完成。\n" +
                "任務會在背景執行,完成後可在列表查看「最後爬蟲」時間。\n\n" +
                "確定要繼續嗎?"
            );
            if (!ok) return;

            const btn = document.querySelector('[data-scrape-btn="' + configId + '"]');
            if (btn) { btn.disabled = true; btn.textContent = '啟動中...'; }

            try {
                const resp = await fetch(JOB_DIGGER_API_BASE + '/api/scrape/' + configId, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                });
                const data = await resp.json().catch(() => ({}));

                if (resp.ok) {
                    alert('✅ 已開始,任務在背景執行中。\n稍後重新整理頁面可查看「最後爬蟲」時間。');
                } else if (resp.status === 409) {
                    alert('⏸ 另一個關鍵字的爬蟲正在執行中,請稍後再試。\n' + (data.detail || ''));
                } else if (resp.status === 403) {
                    alert('⛔ 此關鍵字非今日建立,將由排程自動執行,無法手動觸發。');
                } else if (resp.status === 400) {
                    alert('⏸ ' + (data.detail || '此關鍵字的任務已在執行中。'));
                } else {
                    alert('❌ 啟動失敗 (HTTP ' + resp.status + '):\n' + (data.detail || resp.statusText));
                }
            } catch (e) {
                alert('❌ 連線 job-digger 失敗:' + e.message + '\n請確認 API 服務是否已啟動。');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = '更新'; }
            }
        }
    </script>
@endsection
