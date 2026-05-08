<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| 排程任務
|--------------------------------------------------------------------------
| 每天凌晨 03:00 觸發 job-digger 爬蟲,逐一處理「非今日建立」的 keyword。
| - withoutOverlapping:跨天還沒跑完不重複觸發(整輪可能 1~2 小時)
| - runInBackground:Laravel scheduler tick 不被這個長任務阻塞
| - onOneServer:多 instance 部署時只在一台跑(目前單機可不在意,先設好)
*/
Schedule::command('scrape:all-pending')
    ->dailyAt('03:00')
    ->withoutOverlapping(120)
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/scrape-all-pending.log'));
