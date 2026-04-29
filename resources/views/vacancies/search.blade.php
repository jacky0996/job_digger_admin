@extends('layouts.app')

@section('title', '職缺搜尋')

@section('content')
    <div class="max-w-[1600px] mx-auto bg-white shadow-sm rounded-lg ring-1 ring-slate-200">
        <div class="px-4 sm:px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg sm:text-xl font-semibold text-slate-900">職缺搜尋</h2>
            <p class="text-sm text-slate-500 mt-1">選擇關鍵字並按搜尋</p>
        </div>

        <div class="px-4 sm:px-6 py-4 border-b border-slate-200">
            <form method="GET" action="{{ route('vacancies.search') }}" id="search-form" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    {{-- 關鍵字 --}}
                    <div class="min-w-0">
                        <label for="keyword" class="block text-sm font-medium text-slate-700">關鍵字</label>
                        <select name="keyword" id="keyword" required onchange="checkScrapeStatus()"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">-- 請選擇 --</option>
                            @foreach ($keywordOptions as $kw)
                                <option value="{{ $kw->keyword }}" data-config-id="{{ $kw->id }}"
                                    @selected($selectedKeyword === $kw->keyword)>{{ $kw->keyword }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 職缺狀態 --}}
                    <div class="min-w-0">
                        <label for="status" class="block text-sm font-medium text-slate-700">職缺狀態</label>
                        <select name="status" id="status"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="all" @selected($selectedStatus === 'all')>全選</option>
                            <option value="active" @selected($selectedStatus === 'active')>開啟 (在徵)</option>
                            <option value="closed" @selected($selectedStatus === 'closed')>關閉 (已關)</option>
                        </select>
                    </div>

                    {{-- 篩選結果分類 (check_type) --}}
                    <div class="min-w-0 lg:col-span-1">
                        <label for="check_type" class="block text-sm font-medium text-slate-700">篩選分類</label>
                        <select name="check_type" id="check_type"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="all" @selected($selectedCheckType === 'all')>全選</option>
                            <option value="工作內容有含關鍵字" @selected($selectedCheckType === '工作內容有含關鍵字')>1. 工作內容有含關鍵字</option>
                            <option value="加分條件或必要條件內有含關鍵字" @selected($selectedCheckType === '加分條件或必要條件內有含關鍵字')>2. 加分/必要條件含關鍵字</option>
                            <option value="僅有擅長工具含關鍵字,建議確認後再進行履歷投遞" @selected($selectedCheckType === '僅有擅長工具含關鍵字,建議確認後再進行履歷投遞')>3. 僅有擅長工具含關鍵字 (建議確認)
                            </option>
                        </select>
                    </div>

                    {{-- 操作按鈕 --}}
                    <div class="flex gap-2 sm:flex-shrink-0 lg:justify-end">
                        <button type="submit"
                            class="inline-flex justify-center items-center rounded-md bg-blue-600 px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition h-[38px]">
                            搜尋
                        </button>
                        <button type="button" id="btn-scrape" onclick="triggerScrape(event)"
                            class="inline-flex justify-center items-center rounded-md bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition h-[38px]">
                            更新資料
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if ($selectedKeyword)
            <div class="px-4 sm:px-6 py-3 bg-slate-50 border-b border-slate-200 text-sm text-slate-600">
                關鍵字
                <span class="font-semibold text-blue-700">{{ $selectedKeyword }}</span>
                共有
                <span class="font-semibold text-blue-700">{{ number_format($totalCount) }}</span>
                筆職缺
                @if ($vacancies && $vacancies->total() > 0)
                    <span class="text-slate-500">
                        （第 {{ $vacancies->firstItem() }} – {{ $vacancies->lastItem() }} 筆）
                    </span>
                @endif
            </div>

            @if ($vacancies && $vacancies->total() > 0)
                {{-- Mobile: card list --}}
                <ul class="md:hidden divide-y divide-slate-200">
                    @foreach ($vacancies as $vacancy)
                        <li class="px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 text-xs text-slate-400">
                                        <span>#{{ $vacancy->id }}</span>
                                        @if ($vacancy->status === 'active')
                                            <span
                                                class="inline-flex items-center rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700">在徵</span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-medium text-rose-700">已關</span>
                                        @endif
                                    </div>
                                    <div class="mt-1">
                                        @if ($vacancy->job_link)
                                            <a href="{{ $vacancy->job_link }}" target="_blank" rel="noopener"
                                                class="font-semibold text-slate-900 hover:text-blue-600 break-words">{{ $vacancy->title }}</a>
                                        @else
                                            <span
                                                class="font-semibold text-slate-900 break-words">{{ $vacancy->title }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-1 text-sm text-slate-600 break-words">
                                        @if ($vacancy->company_link)
                                            <a href="{{ $vacancy->company_link }}" target="_blank" rel="noopener"
                                                class="hover:text-blue-600">{{ $vacancy->company_name }}</a>
                                        @else
                                            {{ $vacancy->company_name }}
                                        @endif
                                    </div>
                                    <dl class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                                        <div>
                                            <dt class="text-slate-400">薪資</dt>
                                            <dd class="text-slate-700">{{ $vacancy->salary_text ?: '—' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-slate-400">資本額</dt>
                                            <dd class="text-slate-700">{{ $vacancy->capital ?: '—' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-slate-400">員工數</dt>
                                            <dd class="text-slate-700">{{ $vacancy->employee_count ?: '—' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-slate-400">分類</dt>
                                            <dd class="text-slate-700">
                                                <span
                                                    class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">
                                                    {{ $vacancy->check_type ?: '未分類' }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-slate-400">抓取時間</dt>
                                            <dd class="text-slate-700">
                                                {{ optional($vacancy->created_at)->format('m-d H:i') ?? '—' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Desktop: table --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-16">
                                    ID</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    職缺職稱</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    公司</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-32">
                                    薪資</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-24">
                                    資本額</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-24">
                                    員工數</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-20">
                                    狀態</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 min-w-[150px]">
                                    篩選分類</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-32">
                                    抓取時間</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach ($vacancies as $vacancy)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-sm text-slate-500">{{ $vacancy->id }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($vacancy->job_link)
                                            <a href="{{ $vacancy->job_link }}" target="_blank" rel="noopener"
                                                class="font-medium text-slate-900 hover:text-blue-600 line-clamp-2"
                                                title="{{ $vacancy->title }}">{{ $vacancy->title }}</a>
                                        @else
                                            <span class="font-medium text-slate-900 line-clamp-2"
                                                title="{{ $vacancy->title }}">{{ $vacancy->title }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        @if ($vacancy->company_link)
                                            <a href="{{ $vacancy->company_link }}" target="_blank" rel="noopener"
                                                class="hover:text-blue-600 line-clamp-2"
                                                title="{{ $vacancy->company_name }}">{{ $vacancy->company_name }}</a>
                                        @else
                                            <span class="line-clamp-2"
                                                title="{{ $vacancy->company_name }}">{{ $vacancy->company_name }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">
                                        {{ $vacancy->salary_text ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">
                                        {{ $vacancy->capital ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">
                                        {{ $vacancy->employee_count ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($vacancy->status === 'active')
                                            <span
                                                class="inline-flex items-center rounded bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">在徵</span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">已關</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($vacancy->check_type === '工作內容有含關鍵字')
                                            <span
                                                class="inline-flex items-center rounded bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">工作內容</span>
                                        @elseif ($vacancy->check_type === '加分條件或必要條件內有含關鍵字')
                                            <span
                                                class="inline-flex items-center rounded bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">條件含關鍵字</span>
                                        @elseif (str_contains($vacancy->check_type, '僅有擅長工具含關鍵字'))
                                            <span
                                                class="inline-flex items-center rounded bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-700/10">僅擅長工具</span>
                                        @else
                                            <span class="text-slate-400 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                        {{ optional($vacancy->created_at)->format('Y-m-d H:i') ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 sm:px-6 py-4 border-t border-slate-200">
                    {{ $vacancies->links() }}
                </div>
            @else
                <div class="px-4 sm:px-6 py-10 text-center text-slate-500">沒有符合的職缺。</div>
            @endif
        @else
            <div class="px-4 sm:px-6 py-10 text-center text-slate-500">請選擇關鍵字後按「搜尋」。</div>
        @endif
    </div>
@endsection

<script>
    // 初始化：頁面載入時檢查一次狀態
    document.addEventListener('DOMContentLoaded', () => {
        checkScrapeStatus();
    });

    /**
     * 檢查當前選擇的關鍵字是否正在抓取中
     */
    async function checkScrapeStatus() {
        const select = document.getElementById('keyword');
        if (!select) return;

        const selectedOption = select.options[select.selectedIndex];
        const configId = selectedOption ? selectedOption.getAttribute('data-config-id') : null;
        const btn = document.getElementById('btn-scrape');

        if (!configId || !btn) {
            if (btn) setBtnState(btn, 'normal');
            return;
        }

        try {
            const response = await fetch(`http://localhost:83/api/scrape/status/${configId}`);
            const data = await response.json();

            if (data.is_running) {
                setBtnState(btn, 'running');
            } else {
                setBtnState(btn, 'normal');
            }
        } catch (error) {
            console.warn('無法檢查任務狀態:', error);
            setBtnState(btn, 'normal');
        }
    }

    /**
     * 設定按鈕外觀與狀態
     */
    function setBtnState(btn, state) {
        if (state === 'running') {
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed', 'bg-slate-50', 'text-slate-500');
            btn.classList.remove('text-slate-700', 'hover:bg-slate-50');
            btn.innerHTML = `<span>更新任務執行中</span>`;
        } else {
            btn.disabled = false;
            btn.classList.remove('opacity-70', 'cursor-not-allowed', 'bg-slate-50', 'text-slate-500');
            btn.classList.add('text-slate-700', 'hover:bg-slate-50');
            btn.innerHTML = '更新資料';
        }
    }

    function triggerScrape(event) {
        const select = document.getElementById('keyword');
        const selectedOption = select.options[select.selectedIndex];
        const configId = selectedOption.getAttribute('data-config-id');
        const keyword = selectedOption.value;

        if (!configId || !keyword) {
            alert('請先選擇關鍵字');
            return;
        }

        if (!confirm(`確定要針對「${keyword}」啟動資料抓取任務嗎？\n這將在背景執行 Stage A, C, B 流程。`)) {
            return;
        }

        const btn = event.currentTarget;
        const originalContent = btn.innerHTML;

        // 立即進入啟動中狀態
        btn.disabled = true;
        btn.classList.add('opacity-70', 'cursor-not-allowed', 'bg-slate-50', 'text-slate-500');
        btn.classList.remove('text-slate-700', 'hover:bg-slate-50');
        btn.innerHTML = `<span>啟動中...</span>`;

        fetch(`http://localhost:83/api/scrape/${configId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(async response => {
                const data = await response.json();
                if (response.ok) {
                    alert('✅ 任務已成功啟動！\n' + (data.message || '請稍候片刻再重新搜尋查看結果。'));
                    // 啟動成功後立即更新按鈕狀態為「已有任務」
                    setBtnState(btn, 'running');
                } else {
                    alert('❌ 啟動失敗：' + (data.detail || data.message || '未知錯誤'));
                    setBtnState(btn, 'normal');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('❌ 無法連線至爬蟲服務 (http://localhost:83)。\n請確認 Python API 是否已啟動。');
                setBtnState(btn, 'normal');
            });
    }
</script>
