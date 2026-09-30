<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Diary;
use Illuminate\Database\Eloquent\Factories\Sequence;
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
     * 最終ページより大きい番号では先頭へリダイレクトすることを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('存在しないページを指定すると1ページ目へリダイレクトする')]
    public function testRedirectsOutOfRangePageToFirstPage(): void
    {
        Diary::factory()->count(6)->create();

        $this->get('/diaries?page=14')
            ->assertRedirect(route('diaries.index'));
        $this->get('/diaries?page='.PHP_INT_MAX)
            ->assertRedirect(route('diaries.index'));
        $this->get('/diaries?page=2')
            ->assertOk()
            ->assertSee('2 / 2 ページ');
    }

    /**
     * 投稿がない場合も先頭以外のページを表示しないことを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('日記が0件の場合は1ページ目だけ表示する')]
    public function testRedirectsNonFirstPageWhenNoDiariesExist(): void
    {
        $this->get('/diaries?page=2')
            ->assertRedirect(route('diaries.index'));
        $this->get('/diaries')
            ->assertOk()
            ->assertSee('日記はまだありません。');
        $this->get('/diaries?page=1')
            ->assertOk();
    }

    /**
     * 正の整数として扱えないページ指定を先頭へ戻すことを確認する。
     *
     * @param  string  $query  検証対象のページ指定クエリ。
     * @return void 戻り値なし。
     */
    #[DataProvider('invalidPages')]
    #[TestDox('不正なページ番号を指定すると1ページ目へリダイレクトする')]
    public function testRedirectsInvalidPageToFirstPage(string $query): void
    {
        $this->get('/diaries?'.$query)
            ->assertRedirect(route('diaries.index'));
    }

    /**
     * ページ番号として受け付けないクエリを提供する。
     *
     * @return array<string, array{string}> 拒否対象のページクエリ一覧。
     */
    public static function invalidPages(): array
    {
        return [
            'ゼロ' => ['page=0'],
            '負数' => ['page=-1'],
            '文字列' => ['page=abc'],
            '小数' => ['page=1.5'],
            '空文字' => ['page='],
            '配列' => ['page[]=2'],
            '整数上限超過' => ['page=99999999999999999999999999999'],
        ];
    }

    /**
     * 新しい日記から5件ずつ安定した順序と日本時間で表示することを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('新しい日記から5件ずつ安定した順序と日本時間で表示する')]
    public function testListsFiveDiariesInStableNewestOrderAndJapanTime(): void
    {
        $older = Diary::factory()->create(['title' => '古い日記', 'created_at' => '2026-01-01 00:00:00']);
        // 同一日時の投稿を用意し、IDによる並び順も検証する。
        $diaries = Diary::factory()
            ->count(5)
            ->sequence(
                /**
                 * 並び順を識別できるタイトルを付与する。
                 *
                 * @param  Sequence  $sequence  現在のファクトリー生成位置。
                 * @return array{title: string} 識別用タイトル。
                 */
                function (Sequence $sequence): array {
                    return ['title' => '日記'.$sequence->index];
                }
            )
            ->create(['created_at' => '2026-01-02 00:00:00']);

        $this->get('/diaries')
            ->assertSeeInOrder(['日記4', '日記3', '日記2', '日記1', '日記0'])
            ->assertDontSee($older->title)
            ->assertSee('2026/01/02 09:00')
            ->assertSee('次へ');
        $this->get('/diaries?page=2')
            ->assertSee($older->title)
            ->assertDontSee($diaries->last()->title);
    }

    /**
     * 日記がない場合の案内と投稿フォームを表示することを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('日記がない場合の案内と投稿フォームを表示する')]
    public function testRendersEmptyListAndCreationForm(): void
    {
        $this->get('/diaries')
            ->assertSee('日記はまだありません。');
        $this->get('/diaries/create')
            ->assertSee('新規投稿')
            ->assertSee('name="_token"', false);
    }

    /**
     * 文字数上限の日記を画像なしで登録し未許可項目を保存しないことを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('文字数上限の日記を画像なしで登録し未許可項目を保存しない')]
    public function testCreatesDiaryAtCharacterLimitsWithoutImageAndIgnoresUnexpectedFields(): void
    {
        $data = ['title' => str_repeat('題', 50), 'body' => str_repeat('文', 140)];

        $this->post('/diaries', $data + ['image_path' => 'untrusted.jpg'])
            ->assertRedirect('/diaries')
            ->assertSessionHas('status', '日記を投稿しました。');

        $this->assertDatabaseHas('diaries', $data + ['image_path' => null]);
    }

    /**
     * JPEG画像を生成ファイル名で保存し一覧に表示することを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('JPEG画像を生成ファイル名で保存し一覧に表示する')]
    public function testCreatesAndDisplaysJpegWithGeneratedName(): void
    {
        Storage::fake('public');

        $this->post('/diaries', [
            'title' => '写真',
            'body' => '晴れ',
            'image' => $this->jpeg('photo.jpeg'),
        ])
            ->assertRedirect('/diaries');

        $diary = Diary::sole();
        Storage::disk('public')
            ->assertExists($diary->image_path);
        $this->assertNotSame('diaries/photo.jpeg', $diary->image_path);
        $this->get('/diaries')
            ->assertSee(Storage::disk('public')->url($diary->image_path));
    }

    /**
     * 不正なタイトルまたは本文を拒否し日本語エラーを返すことを確認する。
     *
     * @param  string  $field  検証対象の入力項目。
     * @param  mixed  $value  検証する不正な入力値。
     * @param  string  $message  期待する日本語エラーメッセージ。
     * @return void 戻り値なし。
     */
    #[DataProvider('invalidText')]
    #[TestDox('不正なタイトルまたは本文を拒否し日本語エラーを返す')]
    public function testRejectsInvalidText(string $field, mixed $value, string $message): void
    {
        $data = array_replace(['title' => '題', 'body' => '本文'], [$field => $value]);

        $this->from('/diaries/create')->post('/diaries', $data)
            ->assertRedirect('/diaries/create')
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('diaries', 0);
    }

    /**
     * 拒否対象の入力値と期待するエラーメッセージを提供する。
     *
     * @return array<string, array{0: string, 1: mixed, 2: string}> 入力項目・不正値・期待エラーの組み合わせ。
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
     *
     * @return void 戻り値なし。
     */
    #[TestDox('画像の偽装と容量超過を拒否しファイルを残さない')]
    public function testRejectsFakeJpegExtensionAndOversizedImagesWithoutFiles(): void
    {
        Storage::fake('public');
        // 有効なJPEGへデータを追加し、形式ではなく容量上限で拒否されることを確認する。
        $oversizedImageContents = file_get_contents(base_path('tests/Fixtures/photo.jpg'));
        $oversizedImageContents .= str_repeat('x', 5120 * 1024);
        $files = [
            UploadedFile::fake()->createWithContent('fake.jpg', 'not an image'),
            $this->jpeg('photo.png'),
            UploadedFile::fake()->createWithContent('large.jpg', $oversizedImageContents),
        ];

        foreach ($files as $file) {
            $file->mimeType(mime_content_type($file->getPathname()));
            $this->post('/diaries', [
                'title' => '題',
                'body' => '本文',
                'image' => $file,
            ])
                ->assertSessionHasErrors('image');
        }

        $this->assertDatabaseCount('diaries', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /**
     * 画像を保持したままタイトルと本文を更新することを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('画像を保持したままタイトルと本文を更新する')]
    public function testUpdatesTextWhileRetainingImage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('diaries/old.jpg', 'old');
        $diary = Diary::factory()->create(['image_path' => 'diaries/old.jpg']);

        $this->put(route('diaries.update', $diary), [
            'title' => '変更',
            'body' => '変更本文',
        ])
            ->assertRedirect('/diaries')
            ->assertSessionHas('status', '日記を更新しました。');

        $this->assertSame('変更', $diary->fresh()->title);
        $this->assertSame('diaries/old.jpg', $diary->fresh()->image_path);
        Storage::disk('public')
            ->assertExists('diaries/old.jpg');
    }

    /**
     * 画像を差し替えると旧ファイルを削除することを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('画像を差し替えると旧ファイルを削除する')]
    public function testReplacesImageAndRemovesOldFile(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('diaries/old.jpg', 'old');
        $diary = Diary::factory()->create(['image_path' => 'diaries/old.jpg']);

        $this->put(route('diaries.update', $diary), [
            'title' => '変更',
            'body' => '本文',
            'image' => $this->jpeg(),
        ])
            ->assertRedirect('/diaries');

        Storage::disk('public')
            ->assertMissing('diaries/old.jpg');
        Storage::disk('public')
            ->assertExists($diary->fresh()->image_path);
    }

    /**
     * 廃止した画像削除指定が送信されても現在の画像を保持することを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('廃止した画像削除指定が送信されても現在の画像を保持する')]
    public function testIgnoresRemovedImageDeletionOption(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('diaries/old.jpg', 'old');
        $diary = Diary::factory()->create(['image_path' => 'diaries/old.jpg']);

        $this->put(route('diaries.update', $diary), [
            'title' => '変更',
            'body' => '本文',
            'remove_image' => '1',
        ])
            ->assertRedirect('/diaries');

        $this->assertSame('diaries/old.jpg', $diary->fresh()->image_path);
        Storage::disk('public')
            ->assertExists('diaries/old.jpg');
        $this->get(route('diaries.edit', $diary))
            ->assertDontSee('現在の画像を削除する')
            ->assertDontSee('name="remove_image"', false);
    }

    /**
     * 画像の形式エラー時に入力値と現在の画像を保持することを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('画像の形式エラー時に入力値と現在の画像を保持する')]
    public function testRejectsInvalidReplacementAndPreservesInputsAndCurrentImage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('diaries/old.jpg', 'old');
        $diary = Diary::factory()->create(['image_path' => 'diaries/old.jpg']);

        $this->from(route('diaries.edit', $diary))->put(route('diaries.update', $diary), [
            'title' => '保持する題', 'body' => '保持する本文', 'image' => $this->jpeg('invalid.png'),
        ])
            ->assertSessionHasErrors(['image' => '画像の拡張子はjpgまたはjpegにしてください。']);

        $this->get(route('diaries.edit', $diary))
            ->assertSee('保持する題')
            ->assertSee('保持する本文')
            ->assertSee('現在の画像');
        $this->assertSame('diaries/old.jpg', $diary->fresh()->image_path);
        $this->assertSame(['diaries/old.jpg'], Storage::disk('public')->allFiles());
    }

    /**
     * 日記と関連画像を削除することを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('日記と関連画像を削除する')]
    public function testDeletesDiaryAndItsImage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('diaries/old.jpg', 'old');
        $diary = Diary::factory()->create(['image_path' => 'diaries/old.jpg']);

        $this->delete(route('diaries.destroy', $diary))
            ->assertRedirect('/diaries')
            ->assertSessionHas('status', '日記を削除しました。');

        $this->assertModelMissing($diary);
        Storage::disk('public')
            ->assertMissing('diaries/old.jpg');
    }

    /**
     * 存在しない日記への編集と更新と削除は404を返すことを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('存在しない日記への編集と更新と削除は404を返す')]
    public function testMissingDiariesReturn404(): void
    {
        $this->get('/diaries/999/edit')
            ->assertNotFound();
        $this->put('/diaries/999', [
            'title' => '題',
            'body' => '本文',
        ])
            ->assertNotFound();
        $this->delete('/diaries/999')
            ->assertNotFound();
    }

    /**
     * 一覧と編集フォームでタイトルと本文をエスケープすることを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('一覧と編集フォームでタイトルと本文をエスケープする')]
    public function testEscapesTitleAndBodyInListAndEditForm(): void
    {
        $diary = Diary::factory()->create(['title' => '<script>alert(1)</script>', 'body' => '<img src=x onerror=alert(1)>']);

        foreach (['/diaries', route('diaries.edit', $diary)] as $url) {
            $this->get($url)
                ->assertSee($diary->title)
                ->assertSee($diary->body)
                ->assertDontSee($diary->title, false)
                ->assertDontSee($diary->body, false);
        }
    }

    /**
     * DB更新失敗時に新規画像を削除し既存画像を保持することを確認する。
     *
     * @return void 戻り値なし。
     */
    #[TestDox('DB更新失敗時に新規画像を削除し既存画像を保持する')]
    public function testDatabaseFailureCleansNewUploadAndPreservesOldImage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('diaries/old.jpg', 'old');
        $diary = Diary::factory()->create(['image_path' => 'diaries/old.jpg']);
        // DB保存の失敗を再現し、アップロード済みファイルの後始末を確認する。
        Diary::updating(
            /**
             * 更新処理を意図的に失敗させる。
             *
             * @return void 戻り値なし。
             *
             * @throws RuntimeException 保存失敗を再現するため常に送出する。
             */
            function (): void {
                throw new RuntimeException('保存失敗のテスト');
            }
        );

        try {
            $this->put(route('diaries.update', $diary), [
                'title' => '変更',
                'body' => '本文',
                'image' => $this->jpeg(),
            ])
                ->assertStatus(500);
        } finally {
            // 意図的な例外が後続テストへ影響しないように解除する。
            Diary::flushEventListeners();
        }

        $this->assertSame('diaries/old.jpg', $diary->fresh()->image_path);
        $this->assertSame(['diaries/old.jpg'], Storage::disk('public')->allFiles());
    }

    /**
     * JPEGの実データを持つアップロード用テストファイルを作成する。
     *
     * @param  string  $name  アップロード時のファイル名。
     * @return UploadedFile JPEG実データを持つテスト用アップロードファイル。
     */
    private function jpeg(string $name = 'photo.jpg'): UploadedFile
    {
        // 拡張子だけでなく実体のJPEG検証を通すため、固定の画像データを使う。
        $imageContents = file_get_contents(base_path('tests/Fixtures/photo.jpg'));
        $uploadedFile = UploadedFile::fake()->createWithContent($name, $imageContents);

        return $uploadedFile->mimeType('image/jpeg');
    }
}
