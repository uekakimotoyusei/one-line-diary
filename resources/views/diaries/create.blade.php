@extends('layouts.app')
@section('title', '新規投稿')
@section('content')
<h1>新規投稿</h1>
<form method="POST" action="{{ route('diaries.store') }}" enctype="multipart/form-data">
    @csrf
    @include('diaries.form')
    <div class="actions"><button type="submit">投稿する</button><a href="{{ route('diaries.index') }}">一覧に戻る</a></div>
</form>
@endsection
