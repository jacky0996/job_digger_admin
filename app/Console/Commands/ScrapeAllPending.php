<?php

namespace App\Console\Commands;

use App\Models\SearchConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 排程觸發:把「非今日建立」的 keyword 逐一送進 job-digger 跑爬蟲。
 *
 * 設計重點:
 *   1. **逐筆序列化**:打一個就 poll 到結束才打下一個 — 避免後端同時跑多個
 *      Chromium 把 RAM 打爆,也避免同 IP 對 104 製造尖峰流量。
 *   2. **跳過今日建立**:那些 keyword 由使用者在 admin 手動觸發,scheduler 不該重複動。
 *   3. **withoutOverlapping**(在 schedule 註冊時):跨日還沒跑完就不會重複觸發。
 *   4. **任務 timeout 保護**:單一 keyword 超過 task_timeout(預設 2 小時) 視為卡死,
 *      不再阻塞後續 keyword。
 */
class ScrapeAllPending extends Command
{
    protected $signature = 'scrape:all-pending
                            {--dry-run : 只列出會被處理的 keyword,不實際觸發}';

    protected $description = '輪詢所有非今日建立的 keyword,逐一觸發 job-digger 爬蟲';

    public function handle(): int
    {
        $configs = SearchConfig::query()
            ->whereDate('created_at', '!=', today())
            ->orderByRaw('last_scraped_at IS NULL DESC') // NULL(從沒跑過) 優先
            ->orderBy('last_scraped_at', 'asc')          // 其次最久沒更新
            ->get();

        if ($configs->isEmpty()) {
            $this->info('沒有待處理的 keyword,結束。');

            return self::SUCCESS;
        }

        $this->info("發現 {$configs->count()} 個 keyword 待處理:");
        foreach ($configs as $c) {
            $last = $c->last_scraped_at?->diffForHumans() ?? '從未';
            $this->line("  • [{$c->id}] {$c->keyword}  (上次更新:{$last})");
        }

        if ($this->option('dry-run')) {
            $this->warn('(dry-run) 不實際觸發。');

            return self::SUCCESS;
        }

        $base    = rtrim((string) config('services.job_digger.internal_url'), '/');
        $poll    = (int) config('services.job_digger.poll_interval', 30);
        $timeout = (int) config('services.job_digger.task_timeout', 7200);

        $okCount = 0;
        $skipCount = 0;
        $failCount = 0;

        foreach ($configs as $config) {
            $tag = "[{$config->id}] {$config->keyword}";
            $this->info("→ 觸發 {$tag}");

            try {
                // 觸發 — 用 /scheduled 端點繞過 today 守衛
                $resp = Http::timeout(10)->post("{$base}/api/scrape/scheduled/{$config->id}");
            } catch (\Throwable $e) {
                $this->error("  ❌ 連線 job-digger 失敗:{$e->getMessage()}");
                Log::warning('[scrape] connect failed', ['id' => $config->id, 'err' => $e->getMessage()]);
                $failCount++;
                continue;
            }

            $status = $resp->status();
            if ($status === 403) {
                // 不該發生(SQL 已濾掉今日的),保險:略過
                $this->warn('  ⏭ 403 非今日 keyword,略過');
                $skipCount++;
                continue;
            }
            if ($status === 400 || $status === 409) {
                $this->warn("  ⏸ HTTP {$status}:{$resp->json('detail')} — 等下一輪重試");
                continue;
            }
            if (! $resp->successful()) {
                $this->error("  ❌ HTTP {$status}:{$resp->body()}");
                $failCount++;
                continue;
            }

            // 輪詢直到完成 or timeout
            $deadline = time() + $timeout;
            while (true) {
                sleep($poll);

                if (time() > $deadline) {
                    $this->error("  ⏰ 超過 {$timeout} 秒仍未結束,放棄等待繼續下一個");
                    Log::warning('[scrape] task timeout', ['id' => $config->id]);
                    $failCount++;
                    break;
                }

                try {
                    $st = Http::timeout(10)
                        ->get("{$base}/api/scrape/status/{$config->id}")
                        ->json();
                } catch (\Throwable $e) {
                    $this->warn("  ⚠ 查詢 status 失敗,繼續等:{$e->getMessage()}");
                    continue;
                }

                if (! ($st['is_running'] ?? false)) {
                    $stage = $st['stage'] ?? '?';
                    if ($stage === 'done') {
                        $this->info("  ✅ {$tag} 完成");
                        $okCount++;
                    } else {
                        $this->error("  ❌ {$tag} 結束但 stage={$stage} (error: " . ($st['error'] ?? '-') . ')');
                        $failCount++;
                    }
                    break;
                }
            }
        }

        $this->newLine();
        $this->info("=== 排程結束:成功 {$okCount} / 失敗 {$failCount} / 略過 {$skipCount} ===");

        return self::SUCCESS;
    }
}
