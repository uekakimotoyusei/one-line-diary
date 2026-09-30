<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * 真偽値の基本アサーションが成功することを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('真偽値の基本アサーションが成功する')]
    public function testThatTrueIsTrue(): void
    {
        $this->assertTrue(true);
    }
}
