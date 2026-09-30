<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class DebugbarTest extends TestCase
{
    /**
     * 環境とデバッグ設定の組み合わせで表示可否を検証する。
     */
    #[DataProvider('environments')]
    #[TestDox('ローカル開発かつデバッグ有効のときだけツールバーを有効化する')]
    public function test_enables_toolbar_only_for_local_debug_environment(string $environment, string $debug, string $enabled, bool $expected): void
    {
        $values = ['APP_ENV' => $environment, 'APP_DEBUG' => $debug, 'DEBUGBAR_ENABLED' => $enabled];
        $original = [];

        foreach ($values as $key => $value) {
            $original[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
            $_ENV[$key] = $_SERVER[$key] = $value;
            putenv($key.'='.$value);
        }

        try {
            $settings = require config_path('debugbar.php');
            $this->assertSame($expected, $settings['enabled']);
            $this->assertFalse($settings['storage']['enabled']);
        } finally {
            foreach ($original as $key => $value) {
                unset($_ENV[$key], $_SERVER[$key]);
                if ($value[0] !== null) {
                    $_ENV[$key] = $value[0];
                }
                if ($value[1] !== null) {
                    $_SERVER[$key] = $value[1];
                }
                putenv($value[2] === false ? $key : $key.'='.$value[2]);
            }
        }
    }

    /**
     * ローカル・本番・テスト環境と明示的な無効化の組み合わせを返す。
     *
     * @return array<string, array{string, string, string, bool}>
     */
    public static function environments(): array
    {
        return [
            '開発環境' => ['local', 'true', 'true', true],
            'デバッグ無効' => ['local', 'false', 'true', false],
            '明示的に無効' => ['local', 'true', 'false', false],
            '本番で有効指定' => ['production', 'true', 'true', false],
            'ステージング' => ['staging', 'true', 'true', false],
            'テスト環境' => ['testing', 'true', 'true', false],
        ];
    }

    /**
     * テスト環境のHTMLレスポンスにツールバーを挿入しないことを確認する。
     */
    #[TestDox('テスト環境ではHTMLにツールバーを挿入しない')]
    public function test_does_not_inject_toolbar_in_testing_environment(): void
    {
        $this->get('/diaries/create')->assertOk()->assertDontSee('phpdebugbar', false);
        $this->assertFalse(app('debugbar')->isEnabled());
    }
}
