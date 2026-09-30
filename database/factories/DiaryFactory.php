<?php

namespace Database\Factories;

use App\Models\Diary;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Diary> */
class DiaryFactory extends Factory
{
    /**
     * テスト用の日記の初期値を生成する。
     *
     * @return array<string, mixed> 日記の初期属性。
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'body' => fake()->sentence(),
            'image_path' => null,
        ];
    }
}
