<?php
/*
Plugin Name: ZIP-JP Postalcode Address Search
Plugin URI: https://zipcode.milkyfieldcompany.com/
Description: Zip code address search plugin. It supports input by displaying zip code to address, address to zip code, and place name to address and zip code candidates. * To use it, get the API key from the ZIP-JP API service site.
Author: milkyfield
Version: 2.0.0
Author URI: https://milkyfieldcompany.com/
License: GPL2

Copyright 2021 milkyfield (https://milkyfieldcompany.com/)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

*/
define(
  "ZJPAS_ZIPJP_SERVICE_API",
  "https://zipcode.milkyfieldcompany.com/api/v1/"
);
define(
  "ZJPAS_ZIPJP_FRONTEND_SCRIPT",
  "https://resources.milkyfieldcompany.com/zip-jp/js/mfczip_finder_wpplugin_v2.js"
);

class ZJPAS_Admin
{
  var $table_name_cash;

  function __construct()
  {
    global $wpdb;
    // 接頭辞（wp_）を付けてテーブル名を設定
    $this->table_name_cash = $wpdb->prefix . "zjpas_mfczipjp_cash";

    load_plugin_textdomain(
      "zip-jp-postalcode-address-search",
      false,
      basename(dirname(__FILE__)) . "/languages"
    );

    /* do stuff once right after activation */
    add_action("init", [$this, "init"]);

    add_action("wp_enqueue_scripts", [$this, "requireScripts"]);

    add_action("wp_footer", [$this, "fnAddScript"]);

    add_action("plugins_loaded", [$this, "mfczipjp_dbcheck"]);

    // Register shortcodes
    add_action("wpcf7_init", [$this, "add_shortcodes"]);

    // Tag generator
    add_action("admin_init", [$this, "tag_generator"], 590);
  }

  function requireScripts()
  {
    wp_enqueue_script("jquery");
  }

  public static function add_shortcodes()
  {
    if (function_exists("wpcf7_add_form_tag")) {
      wpcf7_add_form_tag("mfczipbtn", [__CLASS__, "shortcode_handler"], true);
    } elseif (function_exists("wpcf7_add_shortcode")) {
      wpcf7_add_shortcode("mfczipbtn", [__CLASS__, "shortcode_handler"], true);
    } else {
      throw new Exception(
        "functions wpcf7_add_form_tag and wpcf7_add_shortcode not found."
      );
    }
  }

  public static function shortcode_handler($tag)
  {
    $label = "";
    if (is_array($tag->labels)) {
      $label = $tag->labels[0];
    }

    $tagtext =
      "<button type='button' " .
      mb_ereg_replace("class:", "class='", $tag->name) .
      "' >" .
      $label .
      "</button>";
    return $tagtext;
  }

  public static function tag_generator()
  {
    if (!function_exists("wpcf7_add_tag_generator")) {
      return;
    }

    wpcf7_add_tag_generator(
      "zipjpaddr",
      __("Find from postcode", "zip-jp-postalcode-address-search"),
      "wpcf7-tg-pane-btnaddress",
      [__CLASS__, "tg_pane_btnaddress"]
    );

    wpcf7_add_tag_generator(
      "zipjppost",
      __("Find from address", "zip-jp-postalcode-address-search"),
      "wpcf7-tg-pane-btnpostcode",
      [__CLASS__, "tg_pane_btnpostcode"]
    );

    wpcf7_add_tag_generator(
      "zipjptext",
      __("ZIPJP text", "zip-jp-postalcode-address-search"),
      "wpcf7-tg-pane-text",
      [__CLASS__, "tg_pane_text"]
    );

    do_action("wpcf7cf_tag_generator");
  }

  static function tg_pane_btnaddress($contact_form, $args = "")
  {
    $args = wp_parse_args($args, []);
    $type = "button";

    $description = __(
      "Generate a address search button tag.",
      "zip-jp-postalcode-address-search"
    );
    $desc_link = "";

    include "cbox_btnaddress.php";
  }

  static function tg_pane_btnpostcode($contact_form, $args = "")
  {
    $args = wp_parse_args($args, []);
    $type = "button";

    $description = __(
      "Generate a postalcode search button tag.",
      "zip-jp-postalcode-address-search"
    );
    $desc_link = "";

    include "cbox_btnpostcode.php";
  }

  static function tg_pane_text($contact_form, $args = "")
  {
    $args = wp_parse_args($args, []);
    $type = "text";

    $description = __(
      "Generates a form tag for a single-line postalcode search plain text input.",
      "zip-jp-postalcode-address-search"
    );
    $desc_link = "";

    include "cbox_text.php";
  }

  function mfczipjp_dbcheck()
  {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    //DBのバージョン
    $zipjpcash_db_version = "1.0";

    //現在のDBバージョン取得
    $installed_ver = get_option("zjpas_mfczipjp_cash_version");

    // DBバージョンが違ったら作成
    if ($installed_ver != $zipjpcash_db_version) {
      $sql =
        "CREATE TABLE " .
        $this->table_name_cash .
        " (
					execdatetime INT NULL,
					method VARCHAR(100) NULL,
					zipcode VARCHAR(20) NULL,
					address VARCHAR(500) NULL,
					resulttext TEXT(50000) NULL,
					UNIQUE KEY  idx (execdatetime ASC, method ASC, zipcode ASC, address ASC));
					) $charset_collate;";

      require_once ABSPATH . "wp-admin/includes/upgrade.php";

      dbDelta($sql);

      //オプションにDBバージョン保存
      update_option("zjpas_mfczipjp_cash_version", $zipjpcash_db_version);
    }
  }

  function init()
  {
    add_action("admin_menu", [$this, "add_menu"]);

    // ログインしているユーザー向け関数
    add_action("wp_ajax_zjpas_mfczipjpApiCall", [$this, "fnApiCall"]);

    // 非ログインユーザー用関数
    add_action("wp_ajax_nopriv_zjpas_mfczipjpApiCall", [$this, "fnApiCall"]);

    // ログインしているユーザー向け関数
    add_action("wp_ajax_zjpasGetZipJPDlgHTML", [$this, "fnGetZipJPDlgHTML"]);

    // 非ログインユーザー用関数
    add_action("wp_ajax_nopriv_zjpasGetZipJPDlgHTML", [
      $this,
      "fnGetZipJPDlgHTML",
    ]);

    add_action("admin_print_styles", [$this, "fnAdminPrintStyleScripts"]);
  }

  function fnAdminPrintStyleScripts()
  {
    wp_enqueue_style("wp-color-picker");

    wp_enqueue_style("admin_style", plugins_url("css/admin.css", __FILE__));

    wp_enqueue_script("jquery-ui-tabs", ["jquery", "jquery-ui-tabs"]);

    wp_enqueue_script("wp-color-picker");

    wp_enqueue_script(
      "my-admin-script",
      plugins_url("js/admin.js", __FILE__),
      ["wp-color-picker"],
      false,
      true
    );
  }

  function add_menu()
  {
    $icon =
      "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNzAgMjcwIj48cGF0aCBmaWxsPSIjYTdhYWFkIiBkPSJNIDExLjA5Nzk4MSw2LjQwMzI2MTggViA1NC44Nzk4NzYgSCAyNTkuODA0MDkgViA2LjQwMzI2MTggWiBNIDE2MS45NDc1NywxMzYuNDc3MTYgaCA5Ny44NTY1MiBWIDg4LjYwMjczOSBIIDExLjA5Nzk4MSB2IDQ3Ljg3NDQyMSBoIDk2LjA0OTkzOSB2IDEyMC40Mzg3OSBoIDU0Ljc5OTY1IHoiPjwvcGF0aD48L3N2Zz4=";
    add_menu_page(
      __("Postal Code Address Search", "zip-jp-postalcode-address-search"),
      __("Postal Code Address Search", "zip-jp-postalcode-address-search"),
      "level_8",
      "mfc_zipjp_general",
      [$this, "show_text_option_page"],
      $icon,
      31
    );
  }

  function show_text_option_page()
  {
    if (isset($_POST["zjpas_mfczipjpservice_options"])) {

      $zjpas_mfczipjpservice_options_defaults = [
        "apikey" => "",
        "autosearchfrompostcode" => "",
        "zipcodewithhyphen" => "",
        "warnBgColor" => "#FDD",
        "apiuri" => ZJPAS_ZIPJP_SERVICE_API,
        "pluginscript" => "",
      ];

      $zjpas_mfczipjpservice_options = [];
      if (array_key_exists("zjpas_mfczipjpapi_apikey", $_POST)) {
        $zjpas_mfczipjpservice_options["apikey"] = sanitize_text_field(
          $_POST["zjpas_mfczipjpapi_apikey"]
        );
      }
      if (
        array_key_exists("zjpas_mfczipjpapi_autosearchfrompostcode", $_POST)
      ) {
        $zjpas_mfczipjpservice_options[
          "autosearchfrompostcode"
        ] = sanitize_text_field(
          $_POST["zjpas_mfczipjpapi_autosearchfrompostcode"]
        );
      }
      if (array_key_exists("zjpas_mfczipjpapi_zipcodewithhyphen", $_POST)) {
        $zjpas_mfczipjpservice_options[
          "zipcodewithhyphen"
        ] = sanitize_text_field($_POST["zjpas_mfczipjpapi_zipcodewithhyphen"]);
      }
      if (array_key_exists("zjpas_mfczipjpapi_warnBgColor", $_POST)) {
        $zjpas_mfczipjpservice_options["warnBgColor"] = sanitize_text_field(
          $_POST["zjpas_mfczipjpapi_warnBgColor"]
        );
      }
      if (array_key_exists("zjpas_mfczipjpapi_apiuri", $_POST)) {
        $zjpas_mfczipjpservice_options["apiuri"] = esc_url_raw(
          $_POST["zjpas_mfczipjpapi_apiuri"]
        );
      }
      if (array_key_exists("zjpas_mfczipjpapi_pluginscript", $_POST)) {
        $zjpas_mfczipjpservice_options["pluginscript"] = sanitize_text_field(
          $_POST["zjpas_mfczipjpapi_pluginscript"]
        );
      }
      $zjpas_mfczipjpservice_options = array_replace_recursive(
        $zjpas_mfczipjpservice_options_defaults,
        $zjpas_mfczipjpservice_options
      );

      check_admin_referer("zjpas_zipjp_config", "zjpas_zipjpadmn");
      update_option(
        "zjpas_mfczipjpservice_options",
        $zjpas_mfczipjpservice_options
      );
      ?><div class="updated fade"><p><strong><?php _e(
  "The setting value has been saved.",
  "zip-jp-postalcode-address-search"
); ?></strong></p></div><?php
    } ?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br /></div>
	
			<h2>ZIP-JP Postalcode Address Search 設定</h2>

			<div id="zjpas_mfczipjp_tabs">
			  <ul id="zjpas_mfczipjp_tabwrapper">
				<li><a href="#tabs-1">設定</a></li>
				<li><a href="#tabs-2">利用方法(ContactForm7)</a></li>
				<li><a href="#tabs-3">利用方法(高度)</a></li>
				<li><a href="#tabs-4">このプラグインについて</a></li>
			  </ul>
			  <div id="tabs-1">
				<p>
					<form action="" method="post">
						<?php
      wp_nonce_field("zjpas_zipjp_config", "zjpas_zipjpadmn");
      $opt = get_option("zjpas_mfczipjpservice_options");
      $apikey = isset($opt["apikey"]) ? $opt["apikey"] : "";
      $apiaddress = isset($opt["apiuri"])
        ? $opt["apiuri"]
        : ZJPAS_ZIPJP_SERVICE_API;
      $mfczip_autosearchfrompostcode = isset($opt["autosearchfrompostcode"])
        ? $opt["autosearchfrompostcode"]
        : "true";
      $mfczip_zipcodewithhyphen = isset($opt["zipcodewithhyphen"])
        ? $opt["zipcodewithhyphen"]
        : "true";
      $mfczip_warnBgColor = isset($opt["warnBgColor"])
        ? $opt["warnBgColor"]
        : "#FDD";
      $mfczip_pluginscriptmode = isset($opt["pluginscript"])
        ? $opt["pluginscript"]
        : "true";
      ?> 
						<input name="zjpas_mfczipjpservice_options" type="hidden" id="" value="" class="regular-text" style="text-align: left; width: 100%;"/>
						<table class="form-table">
							<tr valign="top" class="zjpas_mfczip_individualscriptmode_disable">
								<th scope="row"><label for="zjpas_mfczipjpapi_apikey">APIキー</label></th>
								<td><input name="zjpas_mfczipjpapi_apikey" type="text" id="zjpas_mfczipjpapi_apikey" placeholder="後述の「APIキーの入手方法」を参考にし、入手したAPIキーを入力してください。" value="<?php echo esc_attr(
          $apikey
        ); ?>" class="regular-text" style="text-align: left; width: 100%;"/></td>
							</tr>
							<tr valign="top">
								<th scope="row"><label for="zjpas_mfczipjpapi_autosearchfrompostcode">郵便番号からの自動検索</label></th>
								<td><label style="margin-right: 1em;"><input name="zjpas_mfczipjpapi_autosearchfrompostcode" type="radio" id="zjpas_mfczipjpapi_autosearchfrompostcode" value="true" <?php echo esc_attr(
          $mfczip_autosearchfrompostcode == "true" ? 'checked="checked"' : ""
        ); ?> class="" style="text-align: left; "/> する</label><label><input name="zjpas_mfczipjpapi_autosearchfrompostcode" type="radio" id="zjpas_mfczipjpapi_autosearchfrompostcode" value="false" <?php echo esc_attr(
   $mfczip_autosearchfrompostcode == "true" ? "" : 'checked="checked"'
 ); ?> class="" style="text-align: left; "/>しない</label></td>
							</tr>
							<tr valign="top">
								<th scope="row"><label for="zjpas_mfczipjpapi_zipcodewithhyphen">郵便番号のハイフン区切り</label></th>
								<td><label style="margin-right: 1em;"><input name="zjpas_mfczipjpapi_zipcodewithhyphen" type="radio" id="zjpas_mfczipjpapi_zipcodewithhyphenon" value="true" <?php echo esc_attr(
          $mfczip_zipcodewithhyphen == "true" ? 'checked="checked"' : ""
        ); ?> class="" style="text-align: left; "/> 区切る(000-0000の形式)</label><label><input name="zjpas_mfczipjpapi_zipcodewithhyphen" type="radio" id="zjpas_mfczipjpapi_zipcodewithhyphenonoff" value="false" <?php echo esc_attr(
   $mfczip_zipcodewithhyphen == "true" ? "" : 'checked="checked"'
 ); ?> class="" style="text-align: left; "/>区切らない(0000000の形式)</label></td>
							</tr>
							<tr valign="top">
								<th scope="row"><label for="zjpas_mfczipjpapi_warnBgColor">入力項目のワーニング色</label></th>
								<td><input name="zjpas_mfczipjpapi_warnBgColor" type="text" id="zjpas_mfczipjpapi_warnBgColor" value="<?php echo esc_attr(
          $mfczip_warnBgColor
        ); ?>" class="my-color-field" style="text-align: left; width: 100%;" /></td>
		
							</tr>
							<tr valign="top" class="zjpas_mfczip_individualscriptmode_disable">
								<th scope="row"><label for="zjpas_mfczipjpapi_apiuri">API Address</label></th>
								<td><input name="zjpas_mfczipjpapi_apiuri" type="text" id="zjpas_mfczipjpapi_apiuri" value="<?php echo esc_url(
          $apiaddress
        ); ?>" class="regular-text" style="text-align: left; width: 100%;"/></td>
							</tr>
							<tr valign="top">
								<th scope="row"><label for="zjpas_mfczipjpapi_pluginscript">スクリプト動作モード<br>(高度な設定)</label></th>
								<td><label style="margin-right: 1em;"><input name="zjpas_mfczipjpapi_pluginscript" type="radio" id="zjpas_mfczipjpapi_pluginscripton" value="true" <?php echo esc_attr(
          $mfczip_pluginscriptmode == "true" ? 'checked="checked"' : ""
        ); ?> class="" style="text-align: left; "/> 標準動作</label><label><input name="zjpas_mfczipjpapi_pluginscript" type="radio" id="zjpas_mfczipjpapi_pluginscriptoff" value="false" <?php echo esc_attr(
   $mfczip_pluginscriptmode == "true" ? "" : 'checked="checked"'
 ); ?> class="" style="text-align: left; "/>マイページからダウンロードしたスクリプトを利用してAPIキーを隠す</label><br><span class="caution">標準動作では、閲覧者のデバイスからAPIを直接実行するのでAPIキーを通知しています。<br>APIキーを秘匿したい場合には、マイページからダウンロードした組み込み用スクリプトをサーバに配置してから「マイページからダウンロードしたスクリプトを利用してAPIキーを隠す」に設定してください。</span></td>
							</tr>
						</table>
						<style>
							.caution {
								display: inline-block;
								position: relative;
								margin: 0.5em 0 0.5em 1.2em;
								width: calc(100% - 1.2em);
								line-height: 1.5em;
							}
							.caution::before {
								content: '※';
								display: block;
								position: absolute;
								margin-left: -1.2em;
							}
						</style>
	
						<div id="" class=""><br>
							
							<h2>APIキーの入手方法</h2>
							1.<br>
							<a href="https://zipcode.milkyfieldcompany.com/" target="_blank">https://zipcode.milkyfieldcompany.com/</a>
							こちらのサイトからマイページに登録して APIキー を取得してください。マイページへの登録は無料です。<br>
							<br>
							2.<br>
							マイページに表示されたAPIキーを上記に入力して、保存すると郵便番号検索機能が利用可能になります。
						</div>
			
						<p class="submit"><input type="submit" name="Submit" class="button-primary" value="保存" /></p>
					</form>
				</p>
			  </div>
			  <div id="tabs-2">
				<p>
					<h2>利用方法(ContactForm7)</h2>
					
					郵便番号住所検索 は Contact Form 7 と組み合わせて利用できます。<br>
					Contact Form 7 で利用する方法をご説明いたします。<br>
					<p>
						このプラグインは、Contact Form 7 の編集画面にタグ追加ボタンを３つ追加します。<br>
						<img src="<?php echo esc_url(
        plugins_url("images/buttons.png", __FILE__)
      ); ?>" style="max-width: 515px;"><br>
						それらを使用して、検索ボタンと検索結果と連携した単一行のテキストコントロールを作成します。<br>
						作成済みのコントロールに検索結果を連携する場合は、後述の <a href="#tabs-2-4" class="jump">4.作成済みのフォームに連携させる場合</a> を参照ください。<br>
					</p>
					<h3>それぞれのボタンと、ボタンを使わずに連携させる方法について</h3>
					それぞれのボタンと、ボタンを使わずに連携させる方法を説明します。<br>
					それぞれのボタンの例として追加するタグやコントロールタグを記載しており、それらを直接 Contact Form 7 のフォームへコピー＆ペーストしても利用可能です。
					<ul class="jumpmenu">
						<li><a href="#tabs-2-1" class="jump">1.郵便番号から住所検索ボタン</a></li>
						<li><a href="#tabs-2-2" class="jump">2.住所(地名)から郵便番号検索ボタン</a></li>
						<li><a href="#tabs-2-3" class="jump">3.ZIPJP連携テキストボタン</a></li>
						<li><a href="#tabs-2-4" class="jump">4.作成済みのフォームに連携させる場合</a></li>
					</ul>
					<br>
					<h4 id="tabs-2-1">1.郵便番号から住所検索ボタン</h4>
					<p>
						<img src="<?php echo esc_url(
        plugins_url("images/addrfind.png", __FILE__)
      ); ?>" style="max-width: 178px;"><br>
						郵便番号から住所検索ボタンのタグを生成します。<br>
						ボタンをクリックすると下図ダイアログを表示します。<br>
						<img src="<?php echo esc_url(
        plugins_url("images/addressfinddialog.png", __FILE__)
      ); ?>" style="max-width: 630px;"><br>
						ラベルがボタンのタイトルになるので、ご希望に応じて変更してください。<br>
						ID属性やクラス属性は任意に入力してください。<br>
						タグを挿入 ボタンをクリックすると、下記のようなコードが挿入されます。<br>
						<div class="sourcecode"><span class="">&lt;button type="button" class="mfczip_findaddr" &gt;郵便番号⇒住所検索&lt;/button&gt;</div>
					</p>
					<h4 id="tabs-2-2">2.住所(地名)から郵便番号検索ボタン</h4>
					<p>
						<img src="<?php echo esc_url(
        plugins_url("images/postcodefind.png", __FILE__)
      ); ?>" style="max-width: 211px;"><br>
						住所(地名)から郵便番号検索ボタンのタグを生成します。<br>
						ボタンをクリックすると下図ダイアログを表示します。<br>
						<img src="<?php echo esc_url(
        plugins_url("images/postcodefinddialog.png", __FILE__)
      ); ?>" style="max-width: 630px;"><br>
						ラベルがボタンのタイトルになるので、ご希望に応じて変更してください。<br>
						ID属性やクラス属性は任意に入力してください。<br>
						タグを挿入 ボタンをクリックすると、下記のようなコードが挿入されます。<br>
						<div class="sourcecode"><span class="">&lt;button type="button" class="mfczip_findzipcode" &gt;住所(地名)⇒郵便番号検索&lt;/button&gt;</div>
						<br>
					</p>
					<h4 id="tabs-2-3">3.ZIPJP連携テキストボタン</h4>
					<p>
						<img src="<?php echo esc_url(
        plugins_url("images/zipjptext.png", __FILE__)
      ); ?>" style="max-width: 126px;"><br>
						郵便番号検索機能と連携した単一行のプレーン入力フィールドのフォームタグを生成します。<br>
						ボタンをクリックすると下図ダイアログを表示します。<br>
						<img src="<?php echo esc_url(
        plugins_url("images/zipjptextdialog.png", __FILE__)
      ); ?>" style="max-width: 630px;"><br>
						上の項目タイプからクラス属性までは、Contact Form 7 標準のテキストと同様です。<br>
						郵便番号・都道府県・町名・事業所名・都道府県(カナ)・町名(カナ)は、連携させたい場合にチェックを入れてください。<br>
						都道府県 と 町名 または 都道府県(カナ) と 町名(カナ) は組み合わせて指定できます。<br>
						タグを挿入 ボタンをクリックすると、下記のようなコードが挿入されます。<br>
						<br>
						例)それぞれの機能に連携させたコントロールの挿入例を掲載します。<br>
						<br>
						郵便番号に対応します。
						<div class="sourcecode"><span class="">[text* postalcode class:mfczip_postcode]</span></div>
						<br>
						都道府県に対応します。
						<div class="sourcecode"><span class="">[text* pref class:mfczip_prefname]</span></div>
						<br>
						町名に対応します。
						<div class="sourcecode"><span class="">[text* town class:mfczip_address]</span></div>
						<br>
						都道府県(カナ)を出力します。
						<div class="sourcecode"><span class="">[text* prefkana class:mfczip_prefkana]</span></div>
						<br>
						町名(カナ)を出力します。
						<div class="sourcecode"><span class="">[text* townkana class:mfczip_addresskana]</span></div>
						<br>
						事業所名を出力します。
						<div class="sourcecode"><span class="">[text* comname class:mfczip_companyname]</span></div>
						<br>
						都道府県 と 町名 は組み合わせて指定できます。<br>
						また、都道府県(カナ) と 町名(カナ) も同様に組み合わせて指定できます。
						<div class="sourcecode"><span class="">[text* address class:mfczip_prefname class:mfczip_address]</span>
<span class="">[text* addresskana class:mfczip_prefkana class:mfczip_addresskana]</span></div>
					</p>
					<h3 id="tabs-2-4">4.作成済みのフォームに連携させる場合</h3>
						これらのクラス指定文字列をコントロールに追加することでも郵便番号住所検索機能との連携が行えます。<br>
						これらを設定し、郵便番号住所検索ボタンを追加してください。<br>
						<div class="sourcecode"><span class=""><span class="label">郵便番号</span>class:mfczip_postcode
<span class="label">都道府県</span>class:mfczip_prefname
<span class="label">町名</span>class:mfczip_address
<span class="label">都道府県(カナ)</span>class:mfczip_prefkana
<span class="label">町名(カナ)</span>class:mfczip_addresskana
<span class="label">事業所名</span>class:mfczip_companyname</span></div>
				</p>
			  </div>
			  <div id="tabs-3">
				<p>
					<h2>利用方法(高度)</h2>

					郵便番号住所検索を行いたいフォームのコントロールにクラスを追加してください。<br>
					追加するクラスは、こちらの通りです。<br>
					<table class="mfczipjp_grid">
						<tr>
							<th>クラス名</th>
							<th>機能</th>
							<th>対応コントロール</th>
							<th>入力</th>
							<th>出力</th>
						</tr>
						<tr>
							<td>mfczip_postcode</td>
							<td>郵便番号</td>
							<td>テキストボックス</td>
							<td >○</td>
							<td>○</td>
						</tr>
						<tr>
							<td>mfczip_prefname</td>
							<td>都道府県名</td>
							<td>テキストボックス、リスト、ドロップダウンリスト</td>
							<td>○</td>
							<td>○</td>
						</tr>
						<tr>
							<td>mfczip_address</td>
							<td>町名<br>※ mfczip_prefname と組み合わせ指定が可能です。</td>
							<td>テキストボックス</td>
							<td>○</td>
							<td>○</td>
						</tr>
						<tr>
							<td>mfczip_companyname</td>
							<td>事業所名</td>
							<td>テキストボックス</td>
							<td>×</td>
							<td>○</td>
						</tr>
						<tr>
							<td>mfczip_prefkana</td>
							<td>都道府県(カナ)</td>
							<td>テキストボックス</td>
							<td>×</td>
							<td>○</td>
						</tr>
						<tr>
							<td>mfczip_addresskana</td>
							<td>町名(カナ)<br>※ mfczip_prefkana と組み合わせ指定が可能です。</td>
							<td>テキストボックス</td>
							<td>×</td>
							<td>○</td>
						</tr>
						<tr>
							<td>mfczip_findaddr</td>
							<td>郵便番号から住所を検索</td>
							<td>ボタン</td>
							<td>－</td>
							<td>－</td>
						</tr>
						<tr>
							<td>mfczip_findzipcode</td>
							<td>住所(地名)から郵便番号を検索</td>
							<td>ボタン</td>
							<td>－</td>
							<td>－</td>
						</tr>
					</table>
					<span class="caution">[都道府県名]と[町名]は、組み合わせて利用することもできます。</span>
					
					<p>
						<h3>サンプルコード</h3>
						<div class="sourcecode"><span class="red tbold">&lt;button type="button" id="findaddr" class="mfczip_findaddr" &gt;郵便番号⇒住所検索&lt;/button&gt;
&lt;button type="button" id="findpostcode" class="mfczip_findzipcode" &gt;住所(地名)⇒郵便番号検索&lt;/button&gt;<!-- &lt;button type="button" id="findaddr" class="mfczip_findcomplement" &gt;住所入力補完&lt;/button&gt; --></span>
&lt;input type="text" name="postcode" value="" <span class="red tbold">class="<span class="yellow tbold">mfczip_postcode</span>"</span> &gt;
&lt;input type="text" name="address" value="" <span class="red tbold">class="<span class="green tbold">mfczip_prefname</span> <span class="blue tbold">mfczip_address</span>"</span> &gt;
&lt;input type="text" name="address" value="" <span class="red tbold">class="<span class="green tbold">mfczip_prefkana</span> <span class="blue tbold">mfczip_addresskana</span>"</span> &gt;
&lt;input type="text" name="atena" value="" <span class="red tbold">class="<span class="lightblue tbold">mfczip_companyname</span>"</span> &gt;</div>
			
						<span class="textspace tbold">郵便番号から住所を検索</span>するボタンに <span class="uline tbold">mfczip_findaddr</span> のクラスを入れてください。<br>
						<span class="textspace tbold">住所から郵便番号を検索</span>するボタンに <span class="uline tbold">mfczip_findzipcode</span> のクラスを入れてください。<br>
						<span class="textspace tbold">郵便番号</span>のフィールドに <span class="uline tbold">mfczip_postcode</span> のクラスを入れてください。<br>
						<span class="textspace tbold">都道府県</span>のフィールドに <span class="uline tbold">mfczip_prefname</span> のクラスを入れてください。<br>
						<span class="textspace tbold">町名</span>のフィールドに <span class="uline tbold">mfczip_address</span> のクラスを入れてください。<br>
						<span class="textspace tbold">都道府県</span>と<span class="textspace tbold">町名</span>を合わせたフィールドの場合には、<span class="uline tbold">mfczip_prefname</span> と <span class="uline tbold">mfczip_address</span> の両方のクラスを入れてください。<br>
						<span class="textspace tbold">事業所名</span>を入れたいフィールドに <span class="uline tbold">mfczip_companyname</span> のクラスを入れてください。<br>
			
						<span class="textspace tbold">都道府県(カナ)</span>を入れたいフィールドに <span class="uline tbold">mfczip_prefkana</span> のクラスを入れてください。<br>
						<span class="textspace tbold">町名(カナ)</span>を入れたいフィールドに <span class="uline tbold">mfczip_addresskana</span> のクラスを入れてください。<br>
						<span class="textspace tbold">都道府県(カナ)</span>と<span class="textspace tbold">町名(カナ)</span>を合わせたフィールドの場合には、<span class="uline tbold">mfczip_prefkana</span> と <span class="uline tbold">mfczip_addresskana</span> の両方のクラスを入れてください。<br>
						<br>
						<span class="caution">カナを利用する場合は、有料の「基本+カナ」プランへのご加入で利用可能となります。</span>
						<span class="caution">APIを直接利用する場合は、<a href="apireference.html" target="_blank"> <span class="tbold">郵便番号検索 APIリファレンス</span> </a>をご覧ください。</span>
					</p>
				</p>
			  </div>
			  <div id="tabs-4">
				<h2>このプラグインについて</h2>
				このプラグインをダウンロードしていただき、また、ご利用いただきありがとうございます。<br>
				<br>
				このプラグインは、郵便番号から住所の検索 や、住所や地名からの郵便番号の検索 を Wordpress から簡単に利用できるようにします。<br>
				今後も機能の追加や改善など行ってまいりますので、どうぞよろしくお願いいたします。<br>
				<br>
				このプラグインや検索機能は無料でお使いいただけますが、サービスの継続と品質向上のため、有料プランをご利用いただけると幸いです。<br>
				<a href="https://zipcode.milkyfieldcompany.com/" target="_blank">https://zipcode.milkyfieldcompany.com/</a><br>
				<br>
				お問い合わせはこちらからお願いいたします。<br>
				<a href="https://milkyfieldcompany.com/contactus/" target="_blank">https://milkyfieldcompany.com/contactus/</a><br>
			  </div>
			</div>
		<!-- /.wrap --></div>
<?php
  }

  function fnAddScript()
  {
    $opt = get_option("zjpas_mfczipjpservice_options");
    $mfczip_apikey = isset($opt["apikey"]) ? $opt["apikey"] : "";
    $mfczip_autosearchfrompostcode = isset($opt["autosearchfrompostcode"])
      ? $opt["autosearchfrompostcode"]
      : "true";
    $mfczip_zipcodewithhyphen = isset($opt["zipcodewithhyphen"])
      ? $opt["zipcodewithhyphen"]
      : "true";
    $mfczip_warnBgColor = isset($opt["warnBgColor"])
      ? $opt["warnBgColor"]
      : "#FDD";
    $mfczip_pluginscriptmode = isset($opt["pluginscript"])
      ? $opt["pluginscript"]
      : "true";

    if ($mfczip_pluginscriptmode == "true") {
      $mfczipjspath = ZJPAS_ZIPJP_FRONTEND_SCRIPT;
      $mfczip_api_serviceurl = admin_url() . "/admin-ajax.php";
    } else {
      $mfczipjspath = home_url("/") . "/mfczip_finder_v1.js";
      $mfczip_api_serviceurl = home_url("/") . "/mfcziprelay_v1.php";
    }

    if (is_admin()) {
      wp_enqueue_script("jquery-ui-tabs");
    }
    ?>
		<script src="<?php echo esc_url($mfczipjspath); ?>"> </script>
		<script>
			var mfczip_apk = '<?php echo esc_js($mfczip_apikey); ?>';
			var mfczip_autosearchfrompostcode = <?php echo esc_js(
     $mfczip_autosearchfrompostcode
   ); ?>;
			var mfczip_zipcodewithhyphen = <?php echo esc_js($mfczip_zipcodewithhyphen); ?>;
			var mfczip_warnBgColor = '<?php echo esc_js($mfczip_warnBgColor); ?>';
			var mfczip_api_serviceurl = '<?php echo esc_url($mfczip_api_serviceurl); ?>';
			jQuery(function () {
  			if (mfczip_autosearchfrompostcode) {
				jQuery("input.mfczip_postcode").on("keyup", function () {
	  			zipcode = jQuery(this).val();
	  			if (
					zipcode.match(/^\d{3}-\d{4}$/g) != null ||
					zipcode.match(/^\d{7}$/g) != null
	  			) {
					mfczip_GetAddr(this);
	  			}
				});
  			}
			});
		</script>
<?php if ($mfczip_pluginscriptmode == "true") { ?>
			<div id="ZJPAS_RESOURCE">
				<div id="mfczip_overlay" style="display: none;"></div>
				<div id="mfczip_chooseaddrdialog" style="display: none;">
					<div id="mfczip_chooseaddrdialog_titlebar"><span id="mfczip_chooseaddrdialog_titlebar_num">0</span>件の候補が見つかりました。選択してください。<div id="mfczip_chooseaddrdialog_cancelbutton" style="cursor: pointer" onclick="jQuery('#mfczip_overlay, #mfczip_chooseaddrdialog').fadeOut();"></div></div>
					<ul id="mfczip_chooseaddrdialog_list"></ul>
					<div id="mfczip_chooseaddrdialog_controler">
					</div>
				</div>
				<style>
					#mfczip_overlay {
						position: fixed;
						display: block;
						width: 100vw;
						height: 100vh;
						background-color: rgba(0,0,0,0.3);
						top: 0;
						z-index: 998;
					}
					#mfczip_chooseaddrdialog {
						position: fixed;
						display: block;
						width: 95vw;
						height: 80vh;
						top: 0;
						z-index: 999;
						background-color: #fff;
						border: 1px solid #222;
						left: calc(2.5vw - 1px);
						top: calc(5vh - 1px);
					}
					#mfczip_chooseaddrdialog #mfczip_chooseaddrdialog_titlebar {
						position: relative;
						display: block;
						border: 2px solid #fff;
						border-bottom: 1px solid #777;
						padding: 1em 2.5em 1em 0.5em;
						font-size: 150%;
						font-weight: bold;
						background: #888;
						color: #fff;
					}
					#mfczip_chooseaddrdialog #mfczip_chooseaddrdialog_titlebar #mfczip_chooseaddrdialog_cancelbutton {
						position: absolute;
						display: block;
						width: 2.5em;
						height: 2.5em;
						top: 0.3em;
						right: 0.3em;
						border-radius: 2.3em;
						background-color: rgba(255,255,255,0.5);
					}
					#mfczip_chooseaddrdialog #mfczip_chooseaddrdialog_titlebar #mfczip_chooseaddrdialog_cancelbutton:before,
					#mfczip_chooseaddrdialog #mfczip_chooseaddrdialog_titlebar #mfczip_chooseaddrdialog_cancelbutton:after {
						content: ' ';
						position: absolute;
						display: block;
						height: 0.3em;
						width: 2em;
						transform: rotate(45deg);
						background-color: #555;
						top: 1.1em;
						left: 0.25em;
					}
					#mfczip_chooseaddrdialog #mfczip_chooseaddrdialog_titlebar #mfczip_chooseaddrdialog_cancelbutton:after {
						transform: rotate(-45deg);
					}
					#mfczip_chooseaddrdialog ul#mfczip_chooseaddrdialog_list {
						max-height: 58vh;
						overflow: scroll;
						padding: 1em;
						position: relative;
						display: block;
						margin: 1%;
					}
					#mfczip_chooseaddrdialog ul#mfczip_chooseaddrdialog_list li {
						list-style: none;
						line-height: 4em;
						font-size: 12pt;
					}
					#mfczip_chooseaddrdialog ul#mfczip_chooseaddrdialog_list li:nth-child(even) {
						background-color: #eee;
					}
					#mfczip_chooseaddrdialog ul#mfczip_chooseaddrdialog_list li button {
						margin-right: 1em;
					}
					@media screen and (max-width: 768px) {
						#mfczip_chooseaddrdialog #mfczip_chooseaddrdialog_titlebar {
							padding: 0.5em 10vw 0.5em 0.5em;
							font-size: 6vw;
						}
						#mfczip_chooseaddrdialog #mfczip_chooseaddrdialog_titlebar #mfczip_chooseaddrdialog_cancelbutton {
							width: 10vw;
							height: 10vw;
						}
						#mfczip_chooseaddrdialog #mfczip_chooseaddrdialog_titlebar #mfczip_chooseaddrdialog_cancelbutton:before,
						#mfczip_chooseaddrdialog #mfczip_chooseaddrdialog_titlebar #mfczip_chooseaddrdialog_cancelbutton:after {
							height: 0.3em;
							width: 1.2em;
							top: 4.2vw;
							left: 1.4vw;
						}
						#mfczip_chooseaddrdialog ul#mfczip_chooseaddrdialog_list li button {
							padding: 1em 0;
							width: 5em;
							margin-right: 0.5em;
							text-align: center;
						}
						#mfczip_chooseaddrdialog ul#mfczip_chooseaddrdialog_list li span {
							display: inline-block;
							line-height: 2em;
							width: calc(100% - 5.6em);
							vertical-align: middle;
						}
						#mfczip_chooseaddrdialog ul#mfczip_chooseaddrdialog_list li span span.spbr {
							display: inline;
						}
						#mfczip_chooseaddrdialog ul#mfczip_chooseaddrdialog_list li span span.spbr::before {
							content: "\A";
							white-space: pre;
						}
					}
					#mfczip_chooseaddrdialog #mfczip_chooseaddrdialog_controler {
						position: absolute;
						display: block;
						border-bottom: 5px solid #777;
						width: 95vw;
						bottom: 0;
					}
				</style>
			</div><!-- #ZJPAS_RESOURCE --><?php }
  }

  // フロントエンドとZipcodeAPIの受け渡し
  function fnApiCall()
  {
    global $wpdb;

    // 古いキャッシュの削除
    $older = time() - 3600;
    $wpdb->query(
      $wpdb->prepare(
        "
				DELETE FROM " .
          $this->table_name_cash .
          "
				WHERE execdatetime <= " .
          $older
      )
    );

    // キャッシュの検索
    $method = sanitize_text_field($_POST["method"]);
    $zipcode = array_key_exists("zipcode", $_POST)
      ? sanitize_text_field($_POST["zipcode"])
      : null;
    $address = array_key_exists("address", $_POST)
      ? sanitize_text_field($_POST["address"])
      : null;
    $wpdb->get_row($query, $output_type, $row_offset);
    $resulttext = $wpdb->get_col(
      $wpdb->prepare(
        "
				SELECT	  resulttext
				FROM	   " .
          $this->table_name_cash .
          "
				WHERE	   method = %s " .
          ($zipcode != null
            ? "AND zipcode = '$zipcode'"
            : "AND zipcode IS NULL ") .
          ($address != null
            ? "AND address = '$address' "
            : "AND address IS NULL ") .
          "
				ORDER BY	execdatetime DESC
				LIMIT 0,1
				",
        $method
      )
    );

    if ($resulttext) {
      try {
        $resultval = "";
        if (is_array($resulttext)) {
          $resultval = $resulttext[0];
        } else {
          $resultval = $resulttext;
        }

        $resultval = json_decode($resultval, true);
        wp_send_json($resultval);
      } catch (Exception $e) {
        $ret = [];
        $ret["message"] = "該当する住所が見つかりませんでした。";
        wp_send_json($ret);
      }
    } else {
      $opt = get_option("zjpas_mfczipjpservice_options");
      $apikey = isset($opt["apikey"]) ? $opt["apikey"] : "";
      $apiaddress = isset($opt["apiaddress"])
        ? $opt["apiaddress"]
        : ZJPAS_ZIPJP_SERVICE_API;

      $SERVICE_KEY = $apikey;
      $ret = [];
      $ret["result"] = "NG";

      $API_URL = $apiaddress;
      $postdata = [];

      if (sizeof($_POST) == 0 && sizeof($_GET) == 0) {
        fnGetZipJPDlgHTML();
      } elseif (sizeof($_POST) > 0) {
        foreach ($_POST as $key => $val) {
          $key = strtolower(sanitize_key($key));
          $postdata[$key] = sanitize_text_field($val);
        }
      } elseif (sizeof($_GET) > 0) {
        foreach ($_GET as $key => $val) {
          $key = strtolower(sanitize_key($key));
          $postdata[$key] = sanitize_text_field($val);
        }
      }

      if (isset($postdata["method"])) {
        $API_URL .= $postdata["method"];
        unset($postdata["method"]);
        $postdata["apikey"] = $SERVICE_KEY;
      } else {
        $ret["message"] = "サービスは利用できません。";
      }
      if (isset($postdata["action"])) {
        unset($postdata["action"]);
      }

      $ret = wp_remote_post($API_URL, [
        "method" => "POST",
        "body" => $postdata,
      ]);
      if (is_wp_error($ret)) {
        $error_message = $response->get_error_message();
        $ret["message"] =
          "サービスは利用できません。(リクエストに失敗しました。[" .
          $error_message .
          "])";
        wp_send_json($ret);
      } else {
        try {
          $resultval = json_decode($ret["body"], true);

          //	キャッシュに保存
          $execdatetime = time();
          $resulttext = $ret["body"];

          //保存するために配列にする
          $set_arr = [
            "execdatetime" => $execdatetime,
            "method" => $method,
            "zipcode" => $zipcode,
            "address" => $address,
            "resulttext" => $resulttext,
          ];

          //レコードがなかったら新規追加あったら更新
          $wpdb->insert($this->table_name_cash, $set_arr);

          // 					$wpdb->show_errors();

          wp_send_json($resultval);
        } catch (Exception $e) {
          $ret = [];
          $ret["message"] = "該当する住所が見つかりませんでした。";
          wp_send_json($ret);
        }
      }
    }

    wp_die();
  }

  function fnGetZipJPDlgHTML()
  {
    // echo DIALOG_TEMPLATE;

    wp_die();
  }
}
new ZJPAS_Admin();
?>
