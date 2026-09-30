<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 日記テーブルを作成する。
     */
    public function up(): void
    {
        Schema::create('diaries', function (Blueprint $table) {
            $table->id();
            $table->string('title', 50);
            $table->string('body', 140);
            $table->string('image_path')->nullable();
            $table->timestamps();
            $table->index(['created_at', 'id']);
        });
    }

    /**
     * 日記テーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('diaries');
    }
};
