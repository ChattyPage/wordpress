<?php
/**
 * ChattyPage_Template — the CHROME TAKEOVER: render the whole front-end inside the ChattyPage
 * design (pulled header + footer fragments) while WordPress keeps doing everything else.
 *
 * Mental model (the one responsibility boundary that matters):
 *   - WordPress owns ALL pages, posts, URLs, menus, and content. We never create or map pages.
 *   - ChattyPage owns the design: header/footer chrome, section designs, article typography.
 *
 * Implemented via the `template_include` filter (the Elementor-theme-builder pattern), NOT an
 * actual theme: nothing is installed or switched, the owner's theme stays active underneath,
 * and turning the toggle off restores it instantly. `wp_head()`/`wp_footer()` run normally, so
 * other plugins keep working.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ChattyPage_Template {

	const OPTION = 'chattypage_takeover';

	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'maybe_take_over' ), 999 );
	}

	public static function is_enabled() {
		return '1' === get_option( self::OPTION, '' ) && ChattyPage_Api_Client::is_connected();
	}

	public static function set_enabled( $enabled ) {
		update_option( self::OPTION, $enabled ? '1' : '', true );
		// The head assets differ under takeover (article-css) → drop page caches if a cache
		// plugin hooks our action; our own fragment caches are unaffected by the toggle.
		do_action( 'chattypage_takeover_toggled', (bool) $enabled );
	}

	/**
	 * Replace the active theme's template with ours for regular front-end requests.
	 * Deliberately narrow: feeds, embeds, robots, and anything non-HTML keep the original
	 * template. The owner's theme remains active — this filter is the entire "theme".
	 */
	public static function maybe_take_over( $template ) {
		if ( ! self::is_enabled() ) {
			return $template;
		}
		if ( is_feed() || is_embed() || is_robots() || is_trackback() || is_admin() ) {
			return $template;
		}
		return CHATTYPAGE_PLUGIN_DIR . 'templates/takeover.php';
	}
}
