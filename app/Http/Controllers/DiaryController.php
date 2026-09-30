<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiaryRequest;
use App\Models\Diary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class DiaryController extends Controller
{
    /**
     * 日記を投稿日時とIDの降順で5件ずつ表示する。
     */
    public function index(): View
    {
        return view('diaries.index', ['diaries' => Diary::query()->latest()->orderByDesc('id')->paginate(5)]);
    }

    /**
     * 新規投稿フォームを表示する。
     */
    public function create(): View
    {
        return view('diaries.create', ['diary' => new Diary]);
    }

    /**
     * 検証済みの日記と任意の画像を登録し、一覧へ戻す。
     */
    public function store(DiaryRequest $request): RedirectResponse
    {
        $this->saveDiary($request, new Diary);

        return to_route('diaries.index')->with('status', '日記を投稿しました。');
    }

    /**
     * 対象の日記を編集フォームに表示する。
     */
    public function edit(Diary $diary): View
    {
        return view('diaries.edit', compact('diary'));
    }

    /**
     * 検証済みの内容と画像操作を反映し、一覧へ戻す。
     */
    public function update(DiaryRequest $request, Diary $diary): RedirectResponse
    {
        $this->saveDiary($request, $diary);

        return to_route('diaries.index')->with('status', '日記を更新しました。');
    }

    /**
     * 日記の削除を確定してから関連画像を削除する。
     */
    public function destroy(Diary $diary): RedirectResponse
    {
        $imagePath = DB::transaction(function () use ($diary): ?string {
            $diary = Diary::query()->lockForUpdate()->findOrFail($diary->id);
            $diary->delete();

            return $diary->image_path;
        });
        $this->deleteImage($imagePath);

        return to_route('diaries.index')->with('status', '日記を削除しました。');
    }

    /**
     * 日記と画像を保存し、DB保存失敗時は新規画像を片付ける。
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
            $oldPath = DB::transaction(function () use ($request, $diary, $newPath): ?string {
                if ($diary->exists) {
                    $diary = Diary::query()->lockForUpdate()->findOrFail($diary->id);
                }
                $oldPath = $diary->image_path;
                $diary->fill($request->safe()->only(['title', 'body']));
                if ($newPath !== null) {
                    $diary->image_path = $newPath;
                }
                $diary->save();

                return $oldPath !== $diary->image_path ? $oldPath : null;
            });
        } catch (Throwable $exception) {
            $this->deleteImage($newPath);
            throw $exception;
        }

        $this->deleteImage($oldPath);
    }

    /**
     * 不要画像を削除し、削除失敗時は例外を報告する。
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
            report($exception);
        }
    }
}
