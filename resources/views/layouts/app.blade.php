<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | 一行日記</title>
    <link rel="stylesheet" href="{{ asset('css/diary.css') }}">
</head>
<body>
<main>
    <header><a href="{{ route('diaries.index') }}">一行日記</a></header>
    @if (session('status'))
        <p class="notice" role="status">{{ session('status') }}</p>
    @endif
    @yield('content')
</main>
</body>
</html>
