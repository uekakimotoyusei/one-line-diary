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
    public function index(): View
    {
        return view('diaries.index', ['diaries' => Diary::query()->latest()->orderByDesc('id')->paginate(5)]);
    }

    public function create(): View
    {
        return view('diaries.create', ['diary' => new Diary]);
    }

    public function store(DiaryRequest $request): RedirectResponse
    {
        $this->saveDiary($request, new Diary);

        return to_route('diaries.index')->with('status', '日記を投稿しました。');
    }

    public function edit(Diary $diary): View
    {
        return view('diaries.edit', compact('diary'));
    }

    public function update(DiaryRequest $request, Diary $diary): RedirectResponse
    {
        $this->saveDiary($request, $diary);

        return to_route('diaries.index')->with('status', '日記を更新しました。');
    }

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
                if ($newPath !== null || $request->boolean('remove_image')) {
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
