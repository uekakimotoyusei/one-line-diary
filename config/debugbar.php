<?php

return [
    // 明示的に有効化しても、ローカル開発環境以外には表示しない。
    'enabled' => env('APP_ENV', 'production') === 'local'
        && (bool) env('APP_DEBUG', false)
        && (bool) env('DEBUGBAR_ENABLED', true),

    // 過去のリクエスト情報をファイルに保存しない。
    'storage' => [
        'enabled' => false,
    ],
];
