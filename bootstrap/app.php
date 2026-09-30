<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

/**
 * 入力内容を維持してバリデーションできるようにする。
 *
 * @param  Middleware  $middleware  アプリケーションのミドルウェア設定。
 * @return void 空白除去の対象外項目を設定する。
 */
$configureMiddleware = function (Middleware $middleware): void {
    // 入力した文字数や空白だけの投稿を、そのまま検証するため除去しない。
    $middleware->trimStrings(except: ['title', 'body']);
};

/**
 * リクエストの用途に応じて例外の応答形式を設定する。
 *
 * @param  Exceptions  $exceptions  例外処理の設定。
 * @return void JSONを返す条件を登録する。
 */
$configureExceptions = function (Exceptions $exceptions): void {
    /**
     * API利用時にHTMLエラー画面が返ることを防ぐ。
     *
     * @param  Request  $request  応答形式を判定するリクエスト。
     * @return bool JSON応答が必要な場合にtrue。
     */
    $shouldRenderJson = function (Request $request): bool {
        return $request->is('api/*') || $request->expectsJson();
    };

    $exceptions->shouldRenderJsonWhen($shouldRenderJson);
};

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware($configureMiddleware)
    ->withExceptions($configureExceptions)
    ->create();
