<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * アプリケーションのサービスを登録する。
     *
     * @return void サービス登録のみを行い、値は返さない。
     */
    public function register(): void
    {
        //
    }

    /**
     * アプリケーションのサービスを初期化する。
     *
     * @return void 初期化のみを行い、値は返さない。
     */
    public function boot(): void
    {
        //
    }
}
