<?php

namespace App\Http\Controllers;

use App\Models\SearchConfig;
use App\Repositories\VacancyRepository;
use Illuminate\Http\Request;

class VacancySearchController extends Controller
{
    private const PER_PAGE = 30;

    protected $vacancyRepository;

    public function __construct(VacancyRepository $vacancyRepository)
    {
        $this->vacancyRepository = $vacancyRepository;
    }

    public function index(Request $request)
    {
        $keywordOptions = SearchConfig::orderBy('keyword')->get(['id', 'keyword']);

        $request->validate([
            'keyword'    => ['nullable', 'string', 'max:50'],
            'status'     => ['nullable', 'string', 'in:all,active,closed'],
            'check_type' => ['nullable', 'string'],
        ]);

        $selectedKeyword = $request->input('keyword');
        $selectedStatus = $request->input('status', 'all');
        $selectedCheckType = $request->input('check_type', 'all');

        $vacancies = null;
        $totalCount = 0;

        if ($selectedKeyword !== null && $selectedKeyword !== '') {
            // 驗證關鍵字是否存在
            if (! $keywordOptions->pluck('keyword')->contains($selectedKeyword)) {
                return redirect()
                    ->route('vacancies.search')
                    ->withErrors(['keyword' => '所選關鍵字不在 search_configs 中']);
            }

            $filters = [
                'keyword'    => $selectedKeyword,
                'status'     => $selectedStatus,
                'check_type' => $selectedCheckType,
            ];

            $vacancies = $this->vacancyRepository->search($filters, self::PER_PAGE)->withQueryString();
            $totalCount = $vacancies->total();
        }

        return view('vacancies.search', [
            'keywordOptions'    => $keywordOptions,
            'selectedKeyword'   => $selectedKeyword,
            'selectedStatus'    => $selectedStatus,
            'selectedCheckType' => $selectedCheckType,
            'vacancies'         => $vacancies,
            'totalCount'        => $totalCount,
        ]);
    }
}
