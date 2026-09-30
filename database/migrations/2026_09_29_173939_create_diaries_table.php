<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 日記テーブルを作成する。
     *
     * @return void テーブル作成のみを行い、値は返さない。
     */
    public function up(): void
    {
        /**
         * diariesテーブルの構造を定義する。
         *
         * @param  Blueprint  $table  作成対象のテーブル定義。
         * @return void カラムと索引を定義し、値は返さない。
         */
        $defineTable = function (Blueprint $table): void {
            $table->id();
            $table->string('title', 50);
            $table->string('body', 140);
            $table->string('image_path')->nullable();
            $table->timestamps();
            // 一覧の作成日時・ID順の取得を支えるため、複合索引を設ける。
            $table->index(['created_at', 'id']);
        };

        Schema::create('diaries', $defineTable);
    }

    /**
     * 日記テーブルを削除する。
     *
     * @return void テーブル削除のみを行い、値は返さない。
     */
    public function down(): void
    {
        Schema::dropIfExists('diaries');
    }
};
