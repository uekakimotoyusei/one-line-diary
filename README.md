# 1行日記

Laravel 13による1行日記サイトです。日記の一覧・投稿・編集・削除とJPEG画像のアップロードに対応しています。

## 仕様

- タイトルは必須・最大50文字、本文は必須・最大140文字。改行および空白のみの入力は不可です。
- 画像は任意で1枚、JPEG形式（jpg/jpeg）・5MB以下です。ファイルの内容と拡張子を検証します。
- 一覧は作成日時の降順、同日時ならIDの降順で、1ページ5件表示します。
- 日時はUTCで保存し、日本時間で表示します。編集しても投稿順は変わりません。
- 編集では新しい画像を選択して更新すると差し替わります。未選択の場合は現在の画像を保持します。画像だけを削除する操作は設けていません。
- 投稿・編集で画像を選択すると、保存前にブラウザ内でプレビューを表示します。選択だけではアップロードされず、投稿・更新ボタンで保存されます。
- 日記の削除には確認ダイアログを表示します。投稿・更新・削除後は一覧の先頭に戻ります。
- 入力エラー時はタイトルと本文を保持します。画像は選び直してください。
- 認証は設けていません。ローカル評価用で、アクセスした人がすべての日記を操作できます。

画像本体は公開ストレージに保存し、DBには生成した保存先パスを記録します。
画像差し替え時はDB更新成功後に旧画像を削除し、DB保存失敗時は新しい画像を削除します。
不要画像の削除に失敗した場合はログに記録します。DBとファイルの操作は単一のトランザクションではないため、運用時には残存ファイルの確認が必要です。

## ソース管理

このディレクトリがGitリポジトリのルートです。親ディレクトリのDocker設定はローカル開発用で、リポジトリには含めません。
`.env`、`vendor/`、`node_modules/`は管理対象外です。依存パッケージのバージョンは`composer.lock`で固定しています。

## 動作環境

確認済み: PHP 8.5.11、MySQL 26.7.0、Composer 2.10.3。

## セットアップ

PHP、Composer、MySQLを用意し、リポジトリのルートで実行します。
PHPの`pdo_mysql`、`mbstring`、`fileinfo`などLaravelの必要拡張を有効にしてください。テストには`pdo_sqlite`も必要です。

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
画像の受け付けにはPHPの`upload_max_filesize`を5M以上、`post_max_size`を8M以上にしてください。
画面のCSSは`public/css/diary.css`に配置しているため、Node.jsやViteのビルドは不要です。

## ローカルDocker環境での操作

親ディレクトリに用意した`docker-compose.yml`を使います。詳細な起動・停止ガイドは親ディレクトリのREADMEに記載しています。

```bash
cd ..
docker compose up -d --wait
docker compose exec app php artisan migrate
docker compose exec app php artisan storage:link
docker compose exec app php artisan test
docker compose down
```

## テスト

```bash
php artisan config:clear
php artisan test
```

テストはSQLiteのメモリDBとテスト用ストレージを使用します。Dockerから渡されるDB環境変数もテスト専用値に上書きします。
設定キャッシュなどによりSQLiteメモリDB以外を参照する場合は、DB操作の前に停止します。
機能テストで、CRUD、5件単位のページ分割、入力制限、画像の保持・差し替え・削除、保存失敗時の後片付け、出力エスケープ、デバッグツールバーの有効化条件などを確認しています。

## AIツールの利用記録

- 使用ツール: Codex
- AIツールの使用有無: あり
- 使用範囲: 要件整理・設計の補助、Docker環境とLaravelの初期構築、日記機能・テストの実装、コードレビュー、動作確認、README作成。実装担当・レビュー担当のサブエージェントも利用しました。
