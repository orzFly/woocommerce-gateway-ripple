=== Plugin Name ===
Contributors: orzfly
Donate link: http://github.com/orzfly/woocommerce-gateway-ripple
Tags: WooCommerce, Payment Gateway, Ripple
Requires at least: 3.9
Tested up to: 3.9.1
Stable tag: 1.0.0
License: MIT
License URI: http://orzfly.mit-license.org

It is a WooCommerce payment gateway extension for Ripple via JSON RPC. Developed as Wordpress plugin.

== Description ==

It is a WooCommerce payment gateway extension for Ripple via JSON RPC. Developed as Wordpress plugin.

Any order older than 7 days will not be checked automatically. Any order received more/less money then excepted will be put on hold for manually review.

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Change the plugin settings throught the 'WooCommerce' settings in WordPress
4. Set a cron job on your server with the following command: `curl http://path.to.your.wordpress/index.php?woocommerce_ripplejson_secret=SECRET`. The SECRET should be set in the settings. 

Note: You must have WooCommerce Installed to make this plugin work.

== Frequently Asked Questions ==

= What are the dependencies for this plugin. =
WooCommerce must be installed.

== Screenshots ==
There is no need to have a screenshot.

== Changelog ==

= 1.0 =
* First release.

== Upgrade Notice ==

= 1.0 =
First release.
