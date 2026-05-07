<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * search_configs 加上「誰新增 / 誰更新」追蹤欄位。
 *
 * 設計考量:
 *   - 存 email 而非 user_id:中台才是真實身分來源,本系統 users 表只是
 *     SSO session-carrier;email 跨系統穩定,user_id 只在本 DB 內有意義。
 *   - updated_at 是 nullable:第一次新增時不填,只在 update 時寫。
 *   - Python 端只 SELECT keyword/filter_tags,不會被多出來的欄位影響。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_configs', function (Blueprint $table) {
            $table->string('created_by_email', 191)->nullable()->after('filter_tags');
            $table->string('updated_by_email', 191)->nullable()->after('created_by_email');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('search_configs', function (Blueprint $table) {
            $table->dropColumn(['created_by_email', 'updated_by_email', 'updated_at']);
        });
    }
};
