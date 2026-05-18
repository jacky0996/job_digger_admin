<!DOCTYPE html>
<html lang="zh-Hant" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Job Digger Admin')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased font-sans">
    <div x-data="{ open: false }" class="min-h-full flex flex-col">
        <header class="bg-slate-900 text-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-14">
                    <a href="{{ route('search-configs.index') }}"
                       class="text-base font-semibold tracking-wide">Job Digger Admin</a>

                    <nav class="hidden md:flex items-center gap-6 text-sm">
                        <a href="{{ route('search-configs.index') }}"
                           class="text-slate-300 hover:text-white transition
                                  {{ request()->routeIs('search-configs.*') ? 'text-white' : '' }}">
                            關鍵字設定
                        </a>
                        <a href="{{ route('vacancies.search') }}"
                           class="text-slate-300 hover:text-white transition
                                  {{ request()->routeIs('vacancies.*') ? 'text-white' : '' }}">
                            職缺搜尋
                        </a>
                        <a href="{{ route('help') }}"
                           class="text-slate-300 hover:text-white transition
                                  {{ request()->routeIs('help') ? 'text-white' : '' }}">
                            使用說明
                        </a>
                    </nav>

                    <button type="button"
                            class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-slate-300 hover:text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-white"
                            @click="open = !open"
                            :aria-expanded="open">
                        <span class="sr-only">開啟選單</span>
                        <svg x-show="!open" class="h-6 w-6" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="open" class="h-6 w-6" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"
                             style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div x-show="open" class="md:hidden border-t border-slate-800" style="display: none;">
                <div class="px-4 py-3 space-y-1">
                    <a href="{{ route('search-configs.index') }}"
                       class="block px-3 py-2 rounded-md text-sm text-slate-200 hover:bg-slate-800
                              {{ request()->routeIs('search-configs.*') ? 'bg-slate-800 text-white' : '' }}">
                        關鍵字設定
                    </a>
                    <a href="{{ route('vacancies.search') }}"
                       class="block px-3 py-2 rounded-md text-sm text-slate-200 hover:bg-slate-800
                              {{ request()->routeIs('vacancies.*') ? 'bg-slate-800 text-white' : '' }}">
                        職缺搜尋
                    </a>
                    <a href="{{ route('help') }}"
                       class="block px-3 py-2 rounded-md text-sm text-slate-200 hover:bg-slate-800
                              {{ request()->routeIs('help') ? 'bg-slate-800 text-white' : '' }}">
                        使用說明
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- SSO session heartbeat — 每 60 秒輕量 ping /sso/ping
         拿到 401 表示中台 session 已過期,提示後自動跳回登入頁
         不依賴使用者操作即可主動偵測,避免使用者填到一半 submit 才被踢 --}}
    <script>
        (function () {
            const PING_URL = '{{ route('sso.ping') }}';
            const LOGOUT_URL = '{{ route('sso.logout') }}';
            const INTERVAL_MS = 60_000;

            async function check() {
                try {
                    const res = await fetch(PING_URL, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store',
                    });
                    if (res.status === 401) {
                        // 不要直接跳,先提示一下避免使用者錯愕
                        if (!window.__sso_expired_notified) {
                            window.__sso_expired_notified = true;
                            alert('您的登入已逾期,即將導回登入頁。');
                            window.location.href = LOGOUT_URL;
                        }
                    }
                } catch (_e) {
                    // 網路錯誤忽略,下一次再試
                }
            }

            setInterval(check, INTERVAL_MS);
        })();
    </script>
</body>
</html>
