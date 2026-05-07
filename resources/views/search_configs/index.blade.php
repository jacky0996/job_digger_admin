@extends('layouts.app')

@section('title', '關鍵字設定')

@section('content')
    <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200">
        <div class="px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200">
            <div>
                <h2 class="text-lg sm:text-xl font-semibold text-slate-900">104 搜尋關鍵字</h2>
                <p class="text-sm text-slate-500 mt-1">設定要爬取的關鍵字與清洗階段使用的過濾標籤。</p>
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
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @forelse ($config->filter_tags_array as $tag)
                                        <span class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-700">{{ $tag }}</span>
                                    @empty
                                        <span class="text-xs text-slate-400">無過濾標籤</span>
                                    @endforelse
                                </div>
                                <div class="mt-2 text-xs text-slate-400 space-y-0.5">
                                    <div>建立 {{ optional($config->created_at)->format('Y-m-d H:i') ?? '—' }} · {{ $config->created_by_email ?? '—' }}</div>
                                    @if ($config->updated_at)
                                        <div>更新 {{ $config->updated_at->format('Y-m-d H:i') }} · {{ $config->updated_by_email ?? '—' }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <a href="{{ route('search-configs.edit', $config) }}"
                               class="flex-1 text-center rounded-md bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200 transition">
                                編輯
                            </a>
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
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">過濾標籤 (清洗用)</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-56">建立 / 更新</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-40">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($configs as $config)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm text-slate-500">{{ $config->id }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $config->keyword }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse ($config->filter_tags_array as $tag)
                                            <span class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-700">{{ $tag }}</span>
                                        @empty
                                            <span class="text-xs text-slate-400">—</span>
                                        @endforelse
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
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex gap-2">
                                        <a href="{{ route('search-configs.edit', $config) }}"
                                           class="rounded-md bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200 transition">
                                            編輯
                                        </a>
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
@endsection
