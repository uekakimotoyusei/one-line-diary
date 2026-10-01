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

このディレクトリがGitリポジトリのルートです。`docker-compose.yml`、`Dockerfile`、`docker/`もリポジトリに含まれ、親ディレクトリの設定は不要です。
`.env`、`vendor/`、`node_modules/`は管理対象外です。PHP依存パッケージのバージョンは`composer.lock`で固定しています。

## 動作環境

Docker Desktop（またはDocker EngineとComposeプラグイン）とGitを用意してください。
コンテナ内の確認済み環境はPHP 8.5.11、MySQL 26.7.0、Composer 2.10.3です。ホストへのPHP・Composer・MySQL・Node.jsのインストールは不要です。
このDocker構成はローカル開発・評価用です。

## Dockerでの初回セットアップ

以下のコマンドは、すべてリポジトリのルートで実行します。

```bash
git clone https://github.com/uekakimotoyusei/one-line-diary.git
cd one-line-diary
cp .env.example .env
docker compose up -d --build --wait
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan storage:link
```

[http://localhost:8080](http://localhost:8080)へアクセスします。初回のイメージ作成には数分かかる場合があります。
画面のCSS・JavaScriptは`public/`に配置しているため、Viteのビルドは不要です。

`.env`をLaravelとComposeで共用します。DB接続先はコンテナ間通信用の`DB_HOST=db`、`DB_PORT=3306`です。DB名・ユーザー・パスワードは`.env`の`DB_DATABASE`、`DB_USERNAME`、`DB_PASSWORD`、管理用パスワードは`DB_ROOT_PASSWORD`で設定します。ひな形のパスワードはローカル評価用です。
DBの環境変数は空のボリュームを初期化するときに使用されます。既存DBのパスワードは`.env`を書き換えるだけでは変更されません。

8080番ポートが使用中の場合は、`.env`の`APP_PORT`と`APP_URL`のポートを同じ番号へ変更し、`docker compose up -d --wait`を実行してください。Webサーバーは`127.0.0.1`だけに公開し、MySQLはホストへ公開しません。

## 起動・停止・日常の操作

```bash
# 起動（2回目以降）
docker compose up -d --wait

# 状態とログの確認
docker compose ps
docker compose logs --tail=100 app db

# 一時停止・再開
docker compose stop
docker compose start

# コンテナとネットワークを削除して停止
docker compose down

# DockerfileやPHP・Apache設定を変更した場合の再ビルド
docker compose up -d --build --wait
```

日記のDBは名前付きボリュームに、画像はホスト側の`storage/app/public/`に保存されます。通常の`docker compose down`では両方とも保持されます。`docker compose down -v`はDBボリュームを削除するため、データを残す場合は実行しないでください。

権限エラーで`storage/`や`bootstrap/cache/`へ書き込めない場合は、コンテナのWeb実行ユーザーへ書き込み権限を付けます。

```bash
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose exec app chmod -R ug+rwX storage bootstrap/cache
```

## 以前の親ディレクトリ構成からの移行

Docker設定はアプリのルートへ移動しました。以前`cd ..`していた操作は、今後このディレクトリで行います。
Composeのプロジェクト名は`hanaprime`を維持しているため、既存の`hanaprime_mysql-data`ボリュームと同じソース・画像を引き継ぎます。既存環境では`.env`のコピーやキー再生成は不要です。

以前の親ディレクトリの`.env`で変更していた`APP_PORT`、`DB_DATABASE`、`DB_USERNAME`、`DB_PASSWORD`、`DB_ROOT_PASSWORD`がある場合は、同じ値をアプリ側の`.env`へ移してから起動してください。

```bash
# アプリのルートで実行
docker compose up -d --build --wait
```

別の独立した環境を同じマシンに作る場合は、`docker compose -p one-line-diary-other ...`のように別のプロジェクト名を指定し、`APP_PORT`も変更してください。別プロジェクト名ではDBボリュームも別になり、初回セットアップが必要です。

## テスト

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan test
```

テストはSQLiteのメモリDBとテスト用ストレージを使用します。Dockerから渡されるDB環境変数もテスト専用値に上書きします。
設定キャッシュなどによりSQLiteメモリDB以外を参照する場合は、DB操作の前に停止します。
機能テストで、CRUD、5件単位のページ分割、入力制限、画像の保持・差し替え・削除、保存失敗時の後片付け、出力エスケープ、デバッグツールバーの有効化条件などを確認しています。

## AIツールの利用記録

- 使用ツール: Codex
- AIツールの使用有無: あり
- 使用範囲: 要件整理・設計の補助、Docker環境とLaravelの初期構築、日記機能・テストの実装、コードレビュー、動作確認、README作成。実装担当・レビュー担当のサブエージェントも利用しました。
