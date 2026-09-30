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
         * usersテーブルの構造を定義する。
         *
         * @param  Blueprint  $table  作成対象のテーブル定義。
         * @return void カラムと索引を定義し、値は返さない。
         */
        $defineTable = function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        };

        Schema::create('users', $defineTable);

        /**
         * password_reset_tokensテーブルの構造を定義する。
         *
         * @param  Blueprint  $table  作成対象のテーブル定義。
         * @return void カラムと索引を定義し、値は返さない。
         */
        $defineTable = function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        };

        Schema::create('password_reset_tokens', $defineTable);

        /**
         * sessionsテーブルの構造を定義する。
         *
         * @param  Blueprint  $table  作成対象のテーブル定義。
         * @return void カラムと索引を定義し、値は返さない。
         */
        $defineTable = function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        };

        Schema::create('sessions', $defineTable);
    }

    /**
     * 作成したテーブルを削除する。
     *
     * @return void テーブル削除のみを行い、値は返さない。
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
