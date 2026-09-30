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
     * @throws RuntimeException テスト環境またはSQLiteメモリDB以外を参照する場合。
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if (! $app->environment('testing')
            || $app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('テストはSQLiteメモリDB専用です。php artisan config:clearを実行してください。');
        }

        return $app;
    }
}
