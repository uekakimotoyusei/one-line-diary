<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * 真偽値の基本アサーションが成功することを確認する。
     */
    #[TestDox('真偽値の基本アサーションが成功する')]
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
