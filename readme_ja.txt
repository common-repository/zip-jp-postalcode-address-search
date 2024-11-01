===ZIP-JP Postalcode Address Search===
Contributors: milkyfield
Donate link: https://milkyfieldcompany.com/
Tags: postalcode, postal code, address, search, contact, form, contact form, ajax, zip, code, cf7
Requires at least: 5.5
Tested up to: 6.0
Stable tag: 2.0.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

日本の郵便番号から住所を検索するAPIサービスを連携します。

== Description ==

ZIP-JP Postalcode Address Search は ZIP-JP 郵便番号住所検索APIサービス(https://zipcode.milkyfieldcompany.com/)をWordpress上で機能させる橋渡しを行います。
郵便番号から住所の検索や、住所から郵便番号を検索する機能で、フォーム入力を支援します。
Contact Form 7とも簡単に組み合わせて動作できます。

このプラグインの作者はContact Form 7プラグイン開発元とは関係ありません、ご注意下さい。

= Privacy notices =

このプラグインは、検索に関するキーワード、APIコール元のIPアドレスを特定の外部サーバ(ZIP-JP 郵便番号住所検索APIサービス: https://zipcode.milkyfieldcompany.com/)にSSL送信します。
また、APIを利用した処理は ZIP-JP が用意したスクリプト(https://resources.milkyfieldcompany.com/zip-jp/js/mfczip_finder_wpplugin_v1.js)を利用します。
このプラグインが行う全ての通信はSSLで安全に行われます。

それ以外、このプラグイン自体は以下のことを行いません。

* ステルスでユーザーを追跡します。
* ユーザーの個人情報をデータベースに書き込みます。
* 外部のサーバーにデータを送信することができます。
* クッキーの使用。

= usage rules =

ZIP-JP Postcode Address Search API [usage rules](https://zipcode.milkyfieldcompany.com/terms.html).

= ZIP-JP Postalcode Address Search is needs your support =

ZIP-JP Postalcode Address Search をお使いいただき、便利だと感じていただけましたら、ぜひ ZIP-JP郵便番号住所検索APIサービス[https://zipcode.milkyfieldcompany.com/](https://zipcode.milkyfieldcompany.com/) の有料プラン をご検討ください。このプラグインとZIP-JP郵便番号住所検索サービスの継続的な開発とより良いユーザーサポートのための励みになります。

== Installation ==

1. `zip-jp-postalcode-address-search` フォルダを、`wp-content/plugins/` ディレクトリにアップロードします。
1. プラグイン**画面（**Plugins > Installed Plugins**）でプラグインを有効化します。

WordPressの管理画面で、**郵便番号住所検索**メニューが表示されています。

==よくある質問===

= このプラグインは無料で利用できますか？ =

プラグインは無料でお使いいただけます。
APIの利用にはAPIサービスのマイページへ登録が必要です。
マイページへの登録は無料です。
品質とサービスの向上ためには有料プランをご検討いただけますと幸いです。

= どうやって使うんですか？ =

こちらの[ドキュメント](https://zipcode.milkyfieldcompany.com/zip-jp-postalcode-address-search/)を参照ください。
プラグインの設定画面にも利用方法を解説しています。


== Screenshots ==

1. screenshot-1.png

== Changelog ==

= 1.0.0 =
* ZIP-JP 郵便番号検索API連携プラグイン最初のリリース

= 1.0.1 =
* fix readme.

= 1.0.2 =
* fix readme.

= 1.1.0 =
* admin-ajax.php path fix.

= 2.0.0 =
* 郵便番号からの自動検索に対応
* 標準動作モードをJSONPでの動作に変更して検索速度を向上
* wordpress-ajaxの廃止

== Upgrade Notice ==

= 2.0.0 =
郵便番号からの自動検索に対応しました。