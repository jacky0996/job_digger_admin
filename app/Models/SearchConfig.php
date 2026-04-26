<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchConfig extends Model
{
    protected $table = 'search_configs';

    public $timestamps = false;

    protected $fillable = [
        'keyword',
        'filter_tags',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function getFilterTagsArrayAttribute(): array
    {
        if (empty($this->filter_tags)) {
            return [];
        }

        return collect(explode(',', $this->filter_tags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->values()
            ->all();
    }

    public static function normalizeFilterTags(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $tags = collect(preg_split('/[\r\n,，、]+/u', $raw))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique()
            ->values();

        return $tags->isEmpty() ? null : $tags->implode(',');
    }
}
