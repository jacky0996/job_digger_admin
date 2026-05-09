<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchConfig extends Model
{
    protected $table = 'search_configs';

    // 我們手動管 created_at(DB default current_timestamp)+ updated_at(controller 寫),
    // 不走 Eloquent 自動 timestamps,避免在 store() 時誤覆蓋 DB default。
    public $timestamps = false;

    protected $fillable = [
        'keyword',
        'title_tags',
        'content_tags',
        'created_by_email',
        'updated_by_email',
        'updated_at',
    ];

    protected $casts = [
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'last_scraped_at' => 'datetime',
    ];

    /** 是否為今日建立(決定 admin 是否顯示「更新」按鈕)。 */
    public function isCreatedToday(): bool
    {
        return $this->created_at?->isToday() ?? false;
    }

    /** Stage A 過濾標籤(標題層) — 給 view 渲染 chip。 */
    public function getTitleTagsArrayAttribute(): array
    {
        return self::splitTags($this->title_tags);
    }

    /** Stage B 過濾標籤(內文層) — 給 view 渲染 chip。 */
    public function getContentTagsArrayAttribute(): array
    {
        return self::splitTags($this->content_tags);
    }

    private static function splitTags(?string $raw): array
    {
        if (empty($raw)) {
            return [];
        }

        return collect(explode(',', $raw))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->values()
            ->all();
    }

    /** 把 textarea 的多行 / 全形逗號等正規化成「a,b,c」儲存格式。 */
    public static function normalizeTags(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $tags = collect(preg_split('/[\r\n,,、]+/u', $raw))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique()
            ->values();

        return $tags->isEmpty() ? null : $tags->implode(',');
    }
}
