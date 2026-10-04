=== klpsoft Feeds for WooCommerce ===
Contributors: klpsoft, Klaus Plank
Tags: google shopping, woocommerce feed, bing, idealo, google product feed
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 1.0.6
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.klp-soft.com/en/klpsoft-feeds-premium/

== Description ==

**klpsoft Feeds for WooCommerce** is a fast feed export plugin to export your shop products into the Google Merchant Center, Bing, and idealo.

**Core Features:**
* **Real-Time Streaming:** Fetch your feed dynamically via a URL endpoint.
* **Product variants:** Export all, or only default variants, for Google and Bing.
* **Static File Generation:** Write a physical XML file directly to your webspace (stored in `/wp-content/uploads/klp-feeds-xml/`).
* **Flexible Field Mapping:** Map all mandatory Google Shopping fields (ID, Title, Description, Price, Link, Image-URL) to your custom sources.
* **Custom Field Support:** Easily bind individual database meta keys to Google attributes.
* **Universal Product Filter:** Add custom product filters (exclude/include) to all feeds.

Looking for advanced features like real-time Google Merchant API, category mapping, Amazon, or TikTok feeds? Check out [klpsoft Feeds Premium](https://www.klp-soft.com/en/klpsoft-feeds-premium/).

== Installation ==

1. Upload the `klpsoft-feeds` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **klpsoft Feeds** in your admin sidebar to configure your feeds.

== Changelog ==

= 1.0.1 =
* Initial Release.

= 1.0.2 =
* Bugfixes.

= 1.0.3 =
* Bugfixes, Namespaces, Amazon.

= 1.0.4 =
* create 1st feed changed, cron bugfixes

= 1.0.5 =
* Bugfixes, Design

= 1.0.6 =
* Design fix
