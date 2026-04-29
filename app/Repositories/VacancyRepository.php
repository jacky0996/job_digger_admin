<?php

namespace App\Repositories;

use App\Models\Vacancy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VacancyRepository
{
    /**
     * 搜尋職缺
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(array $filters, int $perPage = 30): LengthAwarePaginator
    {
        $query = Vacancy::query();

        // 關鍵字篩選
        if (!empty($filters['keyword'])) {
            $query->where('keyword', $filters['keyword']);
        }

        // 職缺狀態篩選 (active, closed)
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Check Type 篩選
        if (!empty($filters['check_type']) && $filters['check_type'] !== 'all') {
            $query->where('check_type', $filters['check_type']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }
}
