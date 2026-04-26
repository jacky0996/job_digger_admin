@extends('layouts.app')

@section('title', '職缺搜尋')

@section('content')
    <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200">
        <div class="px-4 sm:px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg sm:text-xl font-semibold text-slate-900">職缺搜尋</h2>
            <p class="text-sm text-slate-500 mt-1">選擇關鍵字並按搜尋</p>
        </div>

        <div class="px-4 sm:px-6 py-4 border-b border-slate-200">
            <form method="GET" action="{{ route('vacancies.search') }}"
                  class="flex flex-col sm:flex-row sm:items-end gap-3">
                <div class="flex-1 min-w-0">
                    <label for="keyword" class="block text-sm font-medium text-slate-700">關鍵字</label>
                    <select name="keyword"
                            id="keyword"
                            required
                            class="mt-1 block w-full sm:max-w-xs rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">-- 請選擇 --</option>
                        @foreach ($keywordOptions as $kw)
                            <option value="{{ $kw }}" @selected($selectedKeyword === $kw)>{{ $kw }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 sm:flex-shrink-0">
                    <button type="submit"
                            class="inline-flex justify-center items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                        搜尋
                    </button>
                    <button type="button"
                            title="尚未實作"
                            onclick="alert('更新資料功能尚未實作');"
                            class="inline-flex justify-center items-center rounded-md bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        更新資料
                    </button>
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
                                            <span class="inline-flex items-center rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700">在徵</span>
                                        @else
                                            <span class="inline-flex items-center rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-medium text-rose-700">已關</span>
                                        @endif
                                    </div>
                                    <div class="mt-1">
                                        @if ($vacancy->job_link)
                                            <a href="{{ $vacancy->job_link }}" target="_blank" rel="noopener"
                                               class="font-semibold text-slate-900 hover:text-blue-600 break-words">{{ $vacancy->title }}</a>
                                        @else
                                            <span class="font-semibold text-slate-900 break-words">{{ $vacancy->title }}</span>
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
                                            <dt class="text-slate-400">抓取時間</dt>
                                            <dd class="text-slate-700">{{ optional($vacancy->created_at)->format('m-d H:i') ?? '—' }}</dd>
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
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-16">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">職缺職稱</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">公司</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-32">薪資</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-24">資本額</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-24">員工數</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-20">狀態</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-32">抓取時間</th>
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
                                    <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">{{ $vacancy->salary_text ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">{{ $vacancy->capital ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">{{ $vacancy->employee_count ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($vacancy->status === 'active')
                                            <span class="inline-flex items-center rounded bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">在徵</span>
                                        @else
                                            <span class="inline-flex items-center rounded bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">已關</span>
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
