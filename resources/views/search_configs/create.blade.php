@extends('layouts.app')

@section('title', '新增關鍵字')

@section('content')
    <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 max-w-2xl">
        <div class="px-4 sm:px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg sm:text-xl font-semibold text-slate-900">新增搜尋關鍵字</h2>
        </div>
        <div class="px-4 sm:px-6 py-5">
            <form method="POST" action="{{ route('search-configs.store') }}">
                @include('search_configs._form', ['submitLabel' => '建立'])
            </form>
        </div>
    </div>
@endsection
