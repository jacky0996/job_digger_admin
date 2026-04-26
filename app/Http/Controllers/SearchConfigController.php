<?php

namespace App\Http\Controllers;

use App\Models\SearchConfig;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SearchConfigController extends Controller
{
    public function index()
    {
        $configs = SearchConfig::orderByDesc('id')->get();

        return view('search_configs.index', compact('configs'));
    }

    public function create()
    {
        return view('search_configs.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateInput($request);

        SearchConfig::create([
            'keyword'     => $data['keyword'],
            'filter_tags' => SearchConfig::normalizeFilterTags($data['filter_tags'] ?? null),
        ]);

        return redirect()
            ->route('search-configs.index')
            ->with('status', '已新增關鍵字');
    }

    public function edit(SearchConfig $searchConfig)
    {
        return view('search_configs.edit', ['config' => $searchConfig]);
    }

    public function update(Request $request, SearchConfig $searchConfig)
    {
        $data = $this->validateInput($request, $searchConfig->id);

        $searchConfig->update([
            'keyword'     => $data['keyword'],
            'filter_tags' => SearchConfig::normalizeFilterTags($data['filter_tags'] ?? null),
        ]);

        return redirect()
            ->route('search-configs.index')
            ->with('status', '已更新關鍵字');
    }

    public function destroy(SearchConfig $searchConfig)
    {
        $searchConfig->delete();

        return redirect()
            ->route('search-configs.index')
            ->with('status', '已刪除關鍵字');
    }

    private function validateInput(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'keyword' => [
                'required',
                'string',
                'max:50',
                Rule::unique('search_configs', 'keyword')->ignore($ignoreId),
            ],
            'filter_tags' => ['nullable', 'string', 'max:5000'],
        ], [], [
            'keyword'     => '搜尋關鍵字',
            'filter_tags' => '過濾標籤',
        ]);
    }
}
