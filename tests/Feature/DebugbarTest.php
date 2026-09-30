<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class DebugbarTest extends TestCase
{
    /**
     * 環境とデバッグ設定の組み合わせで表示可否を検証する。
     *
     * @param  string  $environment  検証するアプリ環境名。
     * @param  string  $debug  デバッグ機能の環境変数値。
     * @param  string  $enabled  ツールバーの環境変数値。
     * @param  bool  $expected  期待するツールバーの有効状態。
     * @return void 戻り値なし。
     */
    #[DataProvider('environments')]
    #[TestDox('ローカル開発かつデバッグ有効のときだけツールバーを有効化する')]
    public function testEnablesToolbarOnlyForLocalDebugEnvironment(string $environment, string $debug, string $enabled, bool $expected): void
    {
        $values = ['APP_ENV' => $environment, 'APP_DEBUG' => $debug, 'DEBUGBAR_ENABLED' => $enabled];
        $original = [];

        foreach ($values as $key => $value) {
            $original[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key.'='.$value);
        }

        try {
            $settings = require config_path('debugbar.php');
            $this->assertSame($expected, $settings['enabled']);
            $this->assertFalse($settings['storage']['enabled']);
        } finally {
            // 環境変数の書き換えを他のテストへ持ち越さないため、失敗時も復元する。
            foreach ($original as $key => $value) {
                unset($_ENV[$key], $_SERVER[$key]);
                if ($value[0] !== null) {
                    $_ENV[$key] = $value[0];
                }
                if ($value[1] !== null) {
                    $_SERVER[$key] = $value[1];
                }
                if ($value[2] === false) {
                    putenv($key);
                } else {
                    putenv($key.'='.$value[2]);
                }
            }
        }
    }

    /**
     * ローカル・本番・テスト環境と明示的な無効化の組み合わせを返す。
     *
     * @return array<string, array{string, string, string, bool}> 環境設定と期待結果の組み合わせ。
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
     *
     * @return void 戻り値なし。
     */
    #[TestDox('テスト環境ではHTMLにツールバーを挿入しない')]
    public function testDoesNotInjectToolbarInTestingEnvironment(): void
    {
        $this->get('/diaries/create')
            ->assertOk()
            ->assertDontSee('phpdebugbar', false);
        $this->assertFalse(app('debugbar')->isEnabled());
    }
}
