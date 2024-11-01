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

Link API services to search addresses from Japanese postal codes.

== Description ==

ZIP-JP Postalcode Address Search bridges the ZIP-JP Postalcode Address Search API service (https://zipcode.milkyfieldcompany.com/) with WordPress.
It supports form input with the ability to search for an address by zip code or zip code by address.
It can be easily combined and operated with Contact Form 7.

Please note that the author of this plugin is not related to the developer of Contact Form 7 plugin.

= Privacy notices =

This plugin sends a zip code search request with the keywords required for the search and the IP address of the API caller to a specific external server (ZIP-JP Zip Code Address Search API Service: 
https://zipcode.milkyfieldcompany.com/).
In addition, the processing using the API uses the script (https://resources.milkyfieldcompany.com/zip-jp/js/mfczip_finder_wpplugin_v1.js) prepared by ZIP-JP.
All communication done by this plugin is SSL secure.

Besides that, this plugin itself does not do the following.

* Tracks the user by stealth.
* Write the user's personal information to the database.
* Send data to external servers.
* Use cookies.

= usage rules =

ZIP-JP Postcode Address Search API [usage rules](https://zipcode.milkyfieldcompany.com/terms.html).

= ZIP-JP Postalcode Address Search needs your support =

If you use ZIP-JP Postalcode Address Search and find it useful, please consider a paid plan for ZIP-JP Postalcode Address Search API service [https://zipcode.milkyfieldcompany.com/](https://zipcode.milkyfieldcompany.com/). This will encourage us to continue developing this plugin and the ZIP-JP Postalcode Address Search service, and to provide better user support.

== Installation ==

1. Upload the entire `zip-jp-postalcode-address-search` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the **Plugins** screen (**Plugins > Installed Plugins**).

You will find **Postal Code Address Search** menu in your WordPress admin screen.

== Frequently Asked Questions ==

= Is this plugin available for free? =

The plug-in is free to use.
To use the API, you need to register on the My Page of the API service.
My Page registration is free.
To improve the quality and service, we hope you will consider a paid plan.

= How do you use it? =

Please refer to this [document](https://zipcode.milkyfieldcompany.com/zip-jp-postalcode-address-search/).
The usage is also explained in the plugin's configuration screen.



== Screenshots ==

1. screenshot-1.png

== Changelog ==

= 1.0.0 =
* First release of ZIP-JP ZIP Code Search API Plug-in

= 1.0.1 =
* fix readme

= 1.0.2 =
* fix readme

= 1.1.0 =
* admin-ajax.php path fix.

= 2.0.0 =
* Supports automatic search from postalcode.
* Changed the standard operation mode to JSONP operation to improve search speed.
* Abolition of wordpress-ajax.

== Upgrade Notice ==

= 2.0.0 =
Added support for automatic search from postalcode.