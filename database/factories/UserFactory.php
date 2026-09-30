<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * 繰り返しのハッシュ計算を避けるために共有するパスワード。
     */
    protected static ?string $password;

    /**
     * ユーザーの初期属性を生成する。
     *
     * @return array<string, mixed> ユーザーの初期属性。
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * メールアドレスを未確認の状態にする。
     *
     * @return static 未確認状態を設定したファクトリ。
     */
    public function unverified(): static
    {
        return $this->state([
            'email_verified_at' => null,
        ]);
    }
}
