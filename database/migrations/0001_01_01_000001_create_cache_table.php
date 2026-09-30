<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 必要なテーブルを作成する。
     *
     * @return void テーブル作成のみを行い、値は返さない。
     */
    public function up(): void
    {
        /**
         * cacheテーブルの構造を定義する。
         *
         * @param  Blueprint  $table  作成対象のテーブル定義。
         * @return void カラムと索引を定義し、値は返さない。
         */
        $defineTable = function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration')->index();
        };

        Schema::create('cache', $defineTable);

        /**
         * cache_locksテーブルの構造を定義する。
         *
         * @param  Blueprint  $table  作成対象のテーブル定義。
         * @return void カラムと索引を定義し、値は返さない。
         */
        $defineTable = function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->string('owner');
            $table->bigInteger('expiration')->index();
        };

        Schema::create('cache_locks', $defineTable);
    }

    /**
     * 作成したテーブルを削除する。
     *
     * @return void テーブル削除のみを行い、値は返さない。
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
