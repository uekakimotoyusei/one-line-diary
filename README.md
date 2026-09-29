# HanaPrime 1行日記

Laravel 13による1行日記サイトのプロジェクトです。現在はLaravelの初期導入まで完了しています。

## ソース管理

このディレクトリがGitリポジトリのルートです。親ディレクトリのDocker設定はローカル開発用で、リポジトリには含めません。
`.env`、`vendor/`、`node_modules/`は管理対象外です。依存パッケージのバージョンは`composer.lock`で固定しています。

## 動作環境

確認済み: PHP 8.5.11、MySQL 26.7.0、Composer 2.10.3。

## セットアップ

PHP、Composer、MySQLを用意し、リポジトリのルートで実行します。

```bash
composer install
cp .env.example .env
php artisan key:generate
```

`.env`の`DB_HOST`、`DB_PORT`、`DB_DATABASE`、`DB_USERNAME`、`DB_PASSWORD`を自身の環境に合わせ、データベースを作成してください。ひな形はローカルDocker用です。Docker以外では通常`DB_HOST=127.0.0.1`などに変更します。

```bash
php artisan migrate
php artisan storage:link
php artisan serve --port=8080
```

http://localhost:8080 にアクセスします。Webサーバーを使用する場合は公開先を`public/`にし、実行ユーザーが`storage/`と`bootstrap/cache/`へ書き込めるようにしてください。

## ローカルDocker環境での操作

親ディレクトリに用意した`docker-compose.yml`を使います。詳細な起動・停止ガイドは親ディレクトリのREADMEに記載しています。

```bash
cd ..
docker compose up -d --wait
docker compose exec app php artisan test
docker compose down
```

## AIツールの利用記録

- 使用ツール: Codex
- 使用範囲: Docker開発環境の構築支援、Git管理範囲の設定、Laravelの初期導入・設定・動作確認、README作成。
- 日記機能は未実装です。
