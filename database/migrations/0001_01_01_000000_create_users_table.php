<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 重新建立 users 表(原本被清空)。
 * 給 SSO Web Mode 用 — 第一次驗證 JWT 後 User::firstOrCreate 寫一筆,
 * 後續 Laravel session rehydrate Auth::user() 用得到。
 *
 * 本表是 admin 系統自有,不被 job-digger Python 碰。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            // SSO 場景:存隨機 hash,實際不被 check_password 驗
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
