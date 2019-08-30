# 概要

#### ビットコイン自動売買

ビットコイン取引所に実装されているAPIを使用して、仮想通貨の売買を自動化するプログラムです。
改造しやすいようにシンプルな作りとなっています。指定された価格の条件にもとづき売買が自動的に行われます。

#### 動作概要

PHP環境にて動作します。レンタルサーバ（さくらインターネットなど）に配置し実行することも可能です。
また、CRON等で定期実行することで仮想通貨の売買を自動化できます。

# 利用方法

#### 1. 取引所のAPI設定を有効化

    ビットコイン取引所に実装されているAPIを有効にします。
    例）bitFlyer
    https://lightning.bitflyer.com/developer
    - 上記にアクセスし、API キーを有効にする
	- 必要なActionは、トレードの「新規注文を出す」のみ

#### 2. プログラムにAPI設定を定義

    プログラムのAPI定義の箇所に必要な値を追記します。
    API定義はAPIキーやアクセストークンです。
    ※これらの定義は機密情報となります。Webサイトに記載したり外部にでないよう注意が必要です。
    

| プログラム名 | 定義名 | 内容 | 説明 |
|:---:|:---:|:---:|:---|
|bitFlyer.php |BITFLYER_API_KEY    |API Key   |【API Key】の箇所にAPI有効化画面の API Keyを転記 |
|             |BITFLYER_API_SECRET |API Secret|【API Secret】の箇所にAPI有効化画面の API Secretを転記|
|coincheck.php| －                 | －         | －  |
|bitbank.php  | －                 | －         | －  |


#### 3. サーバに配置し実行

    プログラムをサーバに配置します。
    Webサイトに配置する場合、Webサイトがhttpsに対応している必要があります。
    例）https://www.xxx.xxx/bitFlyer.php
    
    配置したURLにブラウザでアクセスします。処理が実行され、売買が行われることを確認します。
    必要に応じてCRONなどのスケジューラで定期的に実行し、売買を自動化させます。

# リファレンス

#### ログファイルの見方

    (1) 2018-06-25 21:30:12【購入】     前回：1,071,759（SELL）→今回：1,066,001（-5758）
    (2) 2018-06-25 21:30:20【取引なし】 前回：1,066,001（BUY）→今回：1,066,000（-1）
    (3) 2018-06-25 21:31:08【売却】     前回：1,066,001（BUY）→今回：1,073,181（+7180）

- (1) 前回の売却価格(SELL)から5758円、価格が下がっているため、購入処理が実行された
- (2) 前回の購入価格(BUY)から1円、価格が下がっているため、取引は行われない
- (3) 前回の購入価格(BUY)から7180円、価格が上がっているため、売却処理が実行された
- 結果、合計12,938円のプラスとなる。※閾値を5000円とし、1BITCOINの取引の場合
