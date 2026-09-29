<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Diary;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use RuntimeException;
use Tests\TestCase;

class DiaryControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * 新しい日記から5件ずつ安定した順序と日本時間で表示することを確認する。
     */
    #[TestDox('新しい日記から5件ずつ安定した順序と日本時間で表示する')]
    public function test_lists_five_diaries_in_stable_newest_order_and_japan_time(): void
    {
        $older = Diary::factory()->create(['title' => '古い日記', 'created_at' => '2026-01-01 00:00:00']);
        $diaries = Diary::factory()->count(5)->sequence(fn ($sequence) => ['title' => '日記'.$sequence->index])->create(['created_at' => '2026-01-02 00:00:00']);

        $this->get('/diaries')->assertSeeInOrder(['日記4', '日記3', '日記2', '日記1', '日記0'])
            ->assertDontSee($older->title)->assertSee('2026/01/02 09:00')->assertSee('次へ');
        $this->get('/diaries?page=2')->assertSee($older->title)->assertDontSee($diaries->last()->title);
    }

    /**
     * 日記がない場合の案内と投稿フォームを表示することを確認する。
     */
    #[TestDox('日記がない場合の案内と投稿フォームを表示する')]
    public function test_renders_empty_list_and_creation_form(): void
    {
        $this->get('/diaries')->assertSee('日記はまだありません。');
        $this->get('/diaries/create')->assertSee('新規投稿')->assertSee('name="_token"', false);
    }

    /**
     * 文字数上限の日記を画像なしで登録し未許可項目を保存しないことを確認する。
     */
    #[TestDox('文字数上限の日記を画像なしで登録し未許可項目を保存しない')]
    public function test_creates_diary_at_character_limits_without_image_and_ignores_unexpected_fields(): void
    {
        $data = ['title' => str_repeat('題', 50), 'body' => str_repeat('文', 140)];

        $this->post('/diaries', $data + ['image_path' => 'untrusted.jpg'])->assertRedirect('/diaries')->assertSessionHas('status', '日記を投稿しました。');

        $this->assertDatabaseHas('diaries', $data + ['image_path' => null]);
    }

    /**
     * JPEG画像を生成ファイル名で保存し一覧に表示することを確認する。
     */
    #[TestDox('JPEG画像を生成ファイル名で保存し一覧に表示する')]
    public function test_creates_and_displays_jpeg_with_generated_name(): void
    {
        Storage::fake('public');

        $this->post('/diaries', ['title' => '写真', 'body' => '晴れ', 'image' => $this->jpeg('photo.jpeg')])->assertRedirect('/diaries');

        $diary = Diary::sole();
        Storage::disk('public')->assertExists($diary->image_path);
        $this->assertNotSame('diaries/photo.jpeg', $diary->image_path);
        $this->get('/diaries')->assertSee(Storage::disk('public')->url($diary->image_path));
    }

    /**
     * 不正なタイトルまたは本文を拒否し日本語エラーを返すことを確認する。
     */
    #[DataProvider('invalidText')]
    #[TestDox('不正なタイトルまたは本文を拒否し日本語エラーを返す')]
    public function test_rejects_invalid_text(string $field, mixed $value, string $message): void
    {
        $data = array_replace(['title' => '題', 'body' => '本文'], [$field => $value]);

        $this->from('/diaries/create')->post('/diaries', $data)->assertRedirect('/diaries/create')->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('diaries', 0);
    }

    /**
     * 拒否対象の入力値と期待するエラーメッセージを提供する。
     *
     * @return array<string, array{0: string, 1: mixed, 2: string}>
     */
    public static function invalidText(): array
    {
        return [
            'タイトル必須' => ['title', '', 'タイトルを入力してください。'],
            '本文必須' => ['body', '', '本文を入力してください。'],
            'タイトル超過' => ['title', str_repeat('題', 51), 'タイトルは50文字以内で入力してください。'],
            '本文超過' => ['body', str_repeat('文', 141), '本文は140文字以内で入力してください。'],
            '先頭改行' => ['title', "\n題", 'タイトルに改行は使用できません。'],
            '末尾改行' => ['body', "本文\r\n", '本文に改行は使用できません。'],
            'Unicode改行' => ['body', "文\u{2028}文", '本文に改行は使用できません。'],
            'Unicode空白' => ['title', "\u{3000}\u{00a0}", 'タイトルは空白以外の文字を入力してください。'],
            '配列' => ['body', ['本文'], '本文は文字列で入力してください。'],
        ];
    }

    /**
     * 画像の偽装と容量超過を拒否しファイルを残さないことを確認する。
     */
    #[TestDox('画像の偽装と容量超過を拒否しファイルを残さない')]
    public function test_rejects_fake_jpeg_extension_and_oversized_images_without_files(): void
    {
        Storage::fake('public');
        $files = [
            UploadedFile::fake()->createWithContent('fake.jpg', 'not an image'),
            $this->jpeg('photo.png'),
            UploadedFile::fake()->createWithContent('large.jpg', file_get_contents(base_path('tests/Fixtures/photo.jpg')).str_repeat('x', 5120 * 1024)),
        ];

        foreach ($files as $file) {
            $file->mimeType(mime_content_type($file->getPathname()));
            $this->post('/diaries', ['title' => '題', 'body' => '本文', 'image' => $file])->assertSessionHasErrors('image');
        }

        $this->assertDatabaseCount('diaries', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /**
     * 画像を保持したままタイトルと本文を更新することを確認する。
     */
    #[TestDox('画像を保持したままタイトルと本文を更新する')]
    public function test_updates_text_while_retaining_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('diaries/old.jpg', 'old');
        $diary = Diary::factory()->create(['image_path' => 'diaries/old.jpg']);

        $this->put(route('diaries.update', $diary), ['title' => '変更', 'body' => '変更本文'])->assertRedirect('/diaries')->assertSessionHas('status', '日記を更新しました。');

        $this->assertSame('変更', $diary->fresh()->title);
        $this->assertSame('diaries/old.jpg', $diary->fresh()->image_path);
        Storage::disk('public')->assertExists('diaries/old.jpg');
    }

    /**
     * 画像を差し替えると旧ファイルを削除することを確認する。
     */
    #[TestDox('画像を差し替えると旧ファイルを削除する')]
    public function test_replaces_image_and_removes_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('diaries/old.jpg', 'old');
        $diary = Diary::factory()->create(['image_path' => 'diaries/old.jpg']);

        $this->put(route('diaries.update', $diary), ['title' => '変更', 'body' => '本文', 'image' => $this->jpeg()])->assertRedirect('/diaries');

        Storage::disk('public')->assertMissing('diaries/old.jpg');
        Storage::disk('public')->assertExists($diary->fresh()->image_path);
    }

    /**
     * 日記を残して画像だけを削除することを確認する。
     */
    #[TestDox('日記を残して画像だけを削除する')]
    public function test_removes_image_without_deleting_diary(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('diaries/old.jpg', 'old');
        $diary = Diary::factory()->create(['image_path' => 'diaries/old.jpg']);

        $this->put(route('diaries.update', $diary), ['title' => '変更', 'body' => '本文', 'remove_image' => '1'])->assertRedirect('/diaries');

        $this->assertNull($diary->fresh()->image_path);
        Storage::disk('public')->assertMissing('diaries/old.jpg');
    }

    /**
     * 画像の差し替えと削除の同時指定を拒否し入力値を保持することを確認する。
     */
    #[TestDox('画像の差し替えと削除の同時指定を拒否し入力値を保持する')]
    public function test_rejects_simultaneous_image_replacement_and_removal_and_preserves_inputs(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('diaries/old.jpg', 'old');
        $diary = Diary::factory()->create(['image_path' => 'diaries/old.jpg']);

        $this->from(route('diaries.edit', $diary))->put(route('diaries.update', $diary), [
            'title' => '保持する題', 'body' => '保持する本文', 'image' => $this->jpeg(), 'remove_image' => '1',
        ])->assertSessionHasErrors(['image' => '画像の差し替えと削除は同時に指定できません。']);

        $this->get(route('diaries.edit', $diary))->assertSee('保持する題')->assertSee('保持する本文')->assertSee('現在の画像');
        $this->assertSame('diaries/old.jpg', $diary->fresh()->image_path);
        $this->assertSame(['diaries/old.jpg'], Storage::disk('public')->allFiles());
    }

    /**
     * 日記と関連画像を削除することを確認する。
     */
    #[TestDox('日記と関連画像を削除する')]
    public function test_deletes_diary_and_its_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('diaries/old.jpg', 'old');
        $diary = Diary::factory()->create(['image_path' => 'diaries/old.jpg']);

        $this->delete(route('diaries.destroy', $diary))->assertRedirect('/diaries')->assertSessionHas('status', '日記を削除しました。');

        $this->assertModelMissing($diary);
        Storage::disk('public')->assertMissing('diaries/old.jpg');
    }

    /**
     * 存在しない日記への編集と更新と削除は404を返すことを確認する。
     */
    #[TestDox('存在しない日記への編集と更新と削除は404を返す')]
    public function test_missing_diaries_return_404(): void
    {
        $this->get('/diaries/999/edit')->assertNotFound();
        $this->put('/diaries/999', ['title' => '題', 'body' => '本文'])->assertNotFound();
        $this->delete('/diaries/999')->assertNotFound();
    }

    /**
     * 一覧と編集フォームでタイトルと本文をエスケープすることを確認する。
     */
    #[TestDox('一覧と編集フォームでタイトルと本文をエスケープする')]
    public function test_escapes_title_and_body_in_list_and_edit_form(): void
    {
        $diary = Diary::factory()->create(['title' => '<script>alert(1)</script>', 'body' => '<img src=x onerror=alert(1)>']);

        foreach (['/diaries', route('diaries.edit', $diary)] as $url) {
            $this->get($url)->assertSee($diary->title)->assertSee($diary->body)->assertDontSee($diary->title, false)->assertDontSee($diary->body, false);
        }
    }

    /**
     * DB更新失敗時に新規画像を削除し既存画像を保持することを確認する。
     */
    #[TestDox('DB更新失敗時に新規画像を削除し既存画像を保持する')]
    public function test_database_failure_cleans_new_upload_and_preserves_old_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('diaries/old.jpg', 'old');
        $diary = Diary::factory()->create(['image_path' => 'diaries/old.jpg']);
        Diary::updating(function (): void {
            throw new RuntimeException('保存失敗のテスト');
        });

        try {
            $this->put(route('diaries.update', $diary), ['title' => '変更', 'body' => '本文', 'image' => $this->jpeg()])->assertStatus(500);
        } finally {
            Diary::flushEventListeners();
        }

        $this->assertSame('diaries/old.jpg', $diary->fresh()->image_path);
        $this->assertSame(['diaries/old.jpg'], Storage::disk('public')->allFiles());
    }

    /**
     * JPEGの実データを持つアップロード用テストファイルを作成する。
     */
    private function jpeg(string $name = 'photo.jpg'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, file_get_contents(base_path('tests/Fixtures/photo.jpg')))->mimeType('image/jpeg');
    }
}
