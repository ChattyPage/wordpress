=== ChattyPage Sections ===
Contributors: chattypage
Tags: ai, sections, design, page builder, elementor
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-designed sections for your WordPress site. Keep WordPress, let ChattyPage design it.

== Description ==

ChattyPage Sections places beautiful, AI-designed page sections on your existing WordPress site. Your site keeps running exactly as it does today: your theme, your plugins, your hosting. ChattyPage takes over the part WordPress makes hard, the design.

**How it works**

1. Create a free account at chattypage.com and generate an API token (My Account, Integrations).
2. Paste the token into the plugin. Your site is now connected.
3. Design sections in ChattyPage: describe what you want, or let it redesign a page of your current site. Edit by drawing on the design and telling it what to change.
4. Place a section anywhere with the ChattyPage Section block, the Elementor widget, or the `[chattypage section="..."]` shortcode.
5. Publish in ChattyPage and your WordPress site updates automatically.

**Why it is fast and SEO-safe**

Sections are fetched server-side and cached by WordPress, then served inline as plain HTML. No iframes, no client-side fetching, no render-blocking builder runtime. Search engines see the full content in your page source.

**Works with your tools**

* Gutenberg: a native block with live preview.
* Elementor: a widget you can drop into any layout (Elementor is not required).
* Everything else: the shortcode works in any editor that accepts shortcodes.

== External services ==

This plugin connects to the ChattyPage service to function. It sends your site URL and section requests to chattypage.com, authenticated with the API token you create in your ChattyPage account. Design generation and editing happen in the ChattyPage app under your account's subscription and credits.

* Service: https://chattypage.com
* Terms: https://chattypage.com/terms
* Privacy: https://chattypage.com/privacy

No visitor data is sent to ChattyPage. Section HTML is fetched server-to-server and cached locally; your visitors only ever talk to your own site.

== Installation ==

1. Install and activate the plugin.
2. Go to the ChattyPage menu in wp-admin.
3. Paste your API token from chattypage.com (My Account, Integrations) and click Connect.
4. Place sections via the block, widget, or shortcode.

== Frequently Asked Questions ==

= Do I need a ChattyPage account? =

Yes. The free tier lets you design and place sections; subscriptions and credits unlock more generation and remove the badge.

= Does this slow my site down? =

No. Sections are cached in your WordPress database and served as inline HTML. There is no runtime dependency on ChattyPage while serving visitors.

= What happens if I deactivate the plugin? =

Your pages simply stop rendering the ChattyPage sections. Nothing else on your site is touched. Your designs stay safe in your ChattyPage account.

== Changelog ==

= 0.1.0 =
* First release: connect flow, section browser, Gutenberg block, Elementor widget, shortcode, automatic cache refresh on publish.
