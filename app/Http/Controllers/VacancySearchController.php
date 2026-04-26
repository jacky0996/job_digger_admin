<?php

namespace App\Http\Controllers;

use App\Models\SearchConfig;
use App\Models\Vacancy;
use Illuminate\Http\Request;

class VacancySearchController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request)
    {
        $keywordOptions = SearchConfig::orderBy('keyword')->pluck('keyword');

        $request->validate([
            'keyword' => ['nullable', 'string', 'max:50'],
        ]);

        $selectedKeyword = $request->input('keyword');
        $vacancies = null;
        $totalCount = 0;

        if ($selectedKeyword !== null && $selectedKeyword !== '') {
            if (! $keywordOptions->contains($selectedKeyword)) {
                return redirect()
                    ->route('vacancies.search')
                    ->withErrors(['keyword' => '所選關鍵字不在 search_configs 中']);
            }

            $query = Vacancy::query()
                ->where('keyword', $selectedKeyword)
                ->orderByDesc('id');

            $totalCount = (clone $query)->count();

            $vacancies = $query->paginate(self::PER_PAGE)->withQueryString();
        }

        return view('vacancies.search', [
            'keywordOptions'  => $keywordOptions,
            'selectedKeyword' => $selectedKeyword,
            'vacancies'       => $vacancies,
            'totalCount'      => $totalCount,
        ]);
    }
}
