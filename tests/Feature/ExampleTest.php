<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * トップページから日記一覧へリダイレクトすることを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('トップページから日記一覧へリダイレクトする')]
    public function testTheApplicationReturnsASuccessfulResponse(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/diaries');
    }
}
