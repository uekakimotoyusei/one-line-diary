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
         * jobsテーブルの構造を定義する。
         *
         * @param  Blueprint  $table  作成対象のテーブル定義。
         * @return void カラムと索引を定義し、値は返さない。
         */
        $defineTable = function (Blueprint $table): void {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedSmallInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        };

        Schema::create('jobs', $defineTable);

        /**
         * job_batchesテーブルの構造を定義する。
         *
         * @param  Blueprint  $table  作成対象のテーブル定義。
         * @return void カラムと索引を定義し、値は返さない。
         */
        $defineTable = function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        };

        Schema::create('job_batches', $defineTable);

        /**
         * failed_jobsテーブルの構造を定義する。
         *
         * @param  Blueprint  $table  作成対象のテーブル定義。
         * @return void カラムと索引を定義し、値は返さない。
         */
        $defineTable = function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('connection');
            $table->string('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();

            $table->index(['connection', 'queue', 'failed_at']);
        };

        Schema::create('failed_jobs', $defineTable);
    }

    /**
     * 作成したテーブルを削除する。
     *
     * @return void テーブル削除のみを行い、値は返さない。
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
