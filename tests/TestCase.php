<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * テストアプリを起動し、開発DBへの誤接続を防ぐ。
     *
     *
     * @return Application 安全なテスト設定を確認したアプリケーション。
     *
     * @throws RuntimeException テスト環境またはSQLiteメモリDB以外を参照する場合。
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        // マイグレーションによって開発データを消さないよう、DB接続前に制限する。
        if (! $app->environment('testing')
            || $app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('テストはSQLiteメモリDB専用です。php artisan config:clearを実行してください。');
        }

        return $app;
    }
}
