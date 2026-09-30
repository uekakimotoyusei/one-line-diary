@extends('layouts.app')
@section('title', '編集')
@section('content')
<h1>日記を編集</h1>
<form method="POST" action="{{ route('diaries.update', $diary) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('diaries.form')
    <div class="actions"><button type="submit">更新する</button><a href="{{ route('diaries.index') }}">一覧に戻る</a></div>
</form>
@endsection
