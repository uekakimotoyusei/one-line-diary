@extends('layouts.app')
@section('title', '一覧')
@section('content')
<div class="heading"><h1>日記一覧</h1><a class="button" href="{{ route('diaries.create') }}">新規投稿</a></div>
@forelse ($diaries as $diary)
    <article>
        <h2>{{ $diary->title }}</h2>
        <p>{{ $diary->body }}</p>
        @if ($diary->image_path)
            <img class="diary-image" src="{{ Storage::disk('public')->url($diary->image_path) }}" alt="{{ $diary->title }}の画像" loading="lazy">
        @endif
        <p class="dates">投稿: {{ $diary->created_at->timezone('Asia/Tokyo')->format('Y/m/d H:i') }} ／ 更新: {{ $diary->updated_at->timezone('Asia/Tokyo')->format('Y/m/d H:i') }}（日本時間）</p>
        <div class="actions">
            <a href="{{ route('diaries.edit', $diary) }}">編集</a>
            <form method="POST" action="{{ route('diaries.destroy', $diary) }}" onsubmit="return confirm('この日記を削除します。よろしいですか？');">
                @csrf
                @method('DELETE')
                <button class="danger" type="submit">削除</button>
            </form>
        </div>
    </article>
@empty
    <p>日記はまだありません。最初の日記を投稿しましょう。</p>
@endforelse
@if ($diaries->hasPages())
    <nav class="pagination" aria-label="ページ切り替え">
        @if ($diaries->onFirstPage())<span>前へ</span>@else<a href="{{ $diaries->previousPageUrl() }}">前へ</a>@endif
        <span>{{ $diaries->currentPage() }} / {{ $diaries->lastPage() }} ページ</span>
        @if ($diaries->hasMorePages())<a href="{{ $diaries->nextPageUrl() }}">次へ</a>@else<span>次へ</span>@endif
    </nav>
@endif
@endsection
