<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiaryRequest;
use App\Models\Diary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class DiaryController extends Controller
{
    // 1ページあたりの表示件数
    const int PER_PAGE = 5;

    /**
     * 一覧画面を表示する。
     * PER_PAGEごとにページネーションを行い、最新の日記から順に表示する。
     * 存在しないページ指定は先頭へ戻す。
     *
     * @param  Request  $request  ページ番号を含む一覧リクエスト。
     * @return View|RedirectResponse 一覧画面、または1ページ目へのリダイレクト。
     */
    public function index(Request $request): View|RedirectResponse
    {
        $query = $request->query();
        $requestedPage = 1;
        // 空値の指定を未指定と区別し、不正なURLを先頭へ戻す。
        if (array_key_exists('page', $query)) {
            $requestedPage = $query['page'];
        }

        $page = filter_var($requestedPage, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($page === false) {
            return to_route('diaries.index');
        }

        // 巨大なページ番号がSQLのOFFSETに渡らないよう、取得前に範囲を確認する。
        $perPage = self::PER_PAGE;
        $total = Diary::query()->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        if ($page > $lastPage) {
            return to_route('diaries.index');
        }

        // 同じ作成日時の日記も順序を固定し、件数の再集計を避ける。
        $diaries = Diary::query()
            ->latest()
            ->orderByDesc('id')
            ->paginate($perPage, page: $page, total: $total);

        return view('diaries.index', ['diaries' => $diaries]);
    }

    /**
     * 新規投稿フォームを表示する。
     *
     * @return View 入力前の日記を渡した新規投稿画面。
     */
    public function create(): View
    {
        return view('diaries.create', ['diary' => new Diary]);
    }

    /**
     * 検証済みの日記と任意の画像を登録し、一覧へ戻す。
     *
     * @param  DiaryRequest  $request  検証済みの投稿内容。
     * @return RedirectResponse 投稿完了メッセージ付きの一覧へのリダイレクト。
     *
     * @throws Throwable
     */
    public function store(DiaryRequest $request): RedirectResponse
    {
        $this->saveDiary($request, new Diary);

        return to_route('diaries.index')->with('status', '日記を投稿しました。');
    }

    /**
     * 対象の日記を編集フォームに表示する。
     *
     * @param  Diary  $diary  編集対象の日記。
     * @return View 現在の内容を渡した編集画面。
     */
    public function edit(Diary $diary): View
    {
        return view('diaries.edit', compact('diary'));
    }

    /**
     * 検証済みの内容と画像操作を反映し、一覧へ戻す。
     *
     * @param  DiaryRequest  $request  検証済みの更新内容。
     * @param  Diary  $diary  更新対象の日記。
     * @return RedirectResponse 更新完了メッセージ付きの一覧へのリダイレクト。
     *
     * @throws Throwable
     */
    public function update(DiaryRequest $request, Diary $diary): RedirectResponse
    {
        $this->saveDiary($request, $diary);

        return to_route('diaries.index')->with('status', '日記を更新しました。');
    }

    /**
     * 日記の削除を確定してから関連画像を削除する。
     *
     * @param  Diary  $diary  削除対象の日記。
     * @return RedirectResponse 削除完了メッセージ付きの一覧へのリダイレクト。
     *
     * @throws Throwable
     */
    public function destroy(Diary $diary): RedirectResponse
    {
        /**
         * 削除対象の最新画像パスを取得し、日記を削除する。
         *
         * @return string|null 削除確定後に片付ける画像のパス。
         */
        $deleteDiary = function () use ($diary): ?string {
            // 同時更新された画像を取り残さないよう、ロック後の状態で削除する。
            $diary = Diary::query()
                ->lockForUpdate()
                ->findOrFail($diary->id);
            $diary->delete();

            return $diary->image_path;
        };
        $imagePath = DB::transaction($deleteDiary);
        // DBの削除が失敗した場合に、日記だけ画像を失うことを防ぐ。
        $this->deleteImage($imagePath);

        return to_route('diaries.index')->with('status', '日記を削除しました。');
    }

    /**
     * 日記と画像を保存し、DB保存失敗時は新規画像を片付ける。
     *
     * @param  DiaryRequest  $request  検証済みの入力とアップロード画像。
     * @param  Diary  $diary  保存対象の日記。
     * @return void 日記と画像の保存だけを行う。
     *
     * @throws Throwable 画像または日記の保存に失敗した場合。
     */
    private function saveDiary(DiaryRequest $request, Diary $diary): void
    {
        $newPath = null;
        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('diaries', 'public');
            if ($newPath === false) {
                throw new RuntimeException('画像を保存できませんでした。');
            }
        }

        try {
            /**
             * 日記と画像パスをまとめて更新する。
             *
             * @return string|null 差し替えによって不要になった画像のパス。
             */
            $persistDiary = function () use ($request, $diary, $newPath): ?string {
                if ($diary->exists) {
                    // 同時編集による画像の不整合を防ぐため、最新の状態をロックする。
                    $diary = Diary::query()
                        ->lockForUpdate()
                        ->findOrFail($diary->id);
                }
                $oldPath = $diary->image_path;
                // 検証対象外の項目がモデルへ書き込まれないようにする。
                $attributes = $request->safe()->only(['title', 'body']);
                $diary->fill($attributes);
                if ($newPath !== null) {
                    $diary->image_path = $newPath;
                }
                $diary->save();

                return $oldPath !== $diary->image_path ? $oldPath : null;
            };
            $oldPath = DB::transaction($persistDiary);
        } catch (Throwable $exception) {
            // DBに紐付かなかった新規画像を残さない。
            $this->deleteImage($newPath);
            throw $exception;
        }

        // 更新確定までは、元の画像を復元できる状態に保つ。
        $this->deleteImage($oldPath);
    }

    /**
     * 不要画像を削除し、削除失敗時は例外を報告する。
     *
     * @param  string|null  $path  削除するpublicディスク上のパス。nullは処理不要。
     * @return void 削除を試み、失敗をログへ報告する。
     */
    private function deleteImage(?string $path): void
    {
        if ($path === null) {
            return;
        }

        try {
            if (! Storage::disk('public')->delete($path)) {
                report(new RuntimeException('不要な画像を削除できませんでした: '.$path));
            }
        } catch (Throwable $exception) {
            // DB操作は確定済みのため、再投稿を誘発せず保守用に失敗を記録する。
            report($exception);
        }
    }
}
