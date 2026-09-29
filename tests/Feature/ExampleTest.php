<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * トップページから日記一覧へリダイレクトすることを確認する。
     */
    #[TestDox('トップページから日記一覧へリダイレクトする')]
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/diaries');
    }
}
