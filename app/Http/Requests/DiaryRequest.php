<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiaryRequest extends FormRequest
{
    /**
     * 認証を設けないローカル評価用の操作を許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * タイトル・本文・画像と画像削除指定の検証ルールを返す。
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['bail', 'required', 'string', 'max:50', 'not_regex:/\R/u', 'regex:/[^\s\p{Z}]/u'],
            'body' => ['bail', 'required', 'string', 'max:140', 'not_regex:/\R/u', 'regex:/[^\s\p{Z}]/u'],
            'image' => ['bail', 'nullable', Rule::prohibitedIf($this->boolean('remove_image')), 'file', 'image', 'mimes:jpg,jpeg', 'extensions:jpg,jpeg', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * 入力エラーに対応する日本語メッセージを返す。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attributeを入力してください。',
            'string' => ':attributeは文字列で入力してください。',
            'title.max' => 'タイトルは50文字以内で入力してください。',
            'body.max' => '本文は140文字以内で入力してください。',
            'not_regex' => ':attributeに改行は使用できません。',
            'regex' => ':attributeは空白以外の文字を入力してください。',
            'image.prohibited' => '画像の差し替えと削除は同時に指定できません。',
            'image.file' => '画像ファイルを選択してください。',
            'image.image' => 'JPEG形式の画像を選択してください。',
            'image.mimes' => 'JPEG形式の画像を選択してください。',
            'image.extensions' => '画像の拡張子はjpgまたはjpegにしてください。',
            'image.max' => '画像は5MB以下にしてください。',
            'image.uploaded' => '画像をアップロードできませんでした。5MB以下の画像を選び直してください。',
            'remove_image.boolean' => '画像の削除指定が正しくありません。',
        ];
    }

    /**
     * エラーメッセージ内の項目名を日本語にする。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => 'タイトル', 'body' => '本文'];
    }
}
