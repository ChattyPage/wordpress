<?php
/**
 * ChattyPage_Head — the front-end <head> assets a connected site needs so sections render
 * exactly as designed:
 *
 *  1. The Tailwind Play runtime, BUNDLED with the plugin (assets/tailwind-play.js, pinned) —
 *     WordPress.org forbids loading executable code from external CDNs, and bundling also
 *     removes the third-party dependency. Generated sections use Tailwind utility classes.
 *  2. Preflight OFF: Tailwind's global reset would restyle the host page's other plugins and
 *     widgets — a theme/plugin that resets other people's elements is broken. Instead:
 *  3. The SCOPED mini-reset (pulled from ChattyPage, cached): restores the blank canvas our
 *     designs were made on, fenced to .chattypage-section / .chatty-article containers only.
 *
 * The ChattyPage editor previews connected designables with this exact pair (preflight off +
 * scoped reset), so design-time and serve-time CSS are identical by construction.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ChattyPage_Head {

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'print_assets' ), 5 );
	}

	public static function print_assets() {
		if ( ! ChattyPage_Api_Client::is_connected() ) {
			return;
		}

		$tailwind = CHATTYPAGE_PLUGIN_URL . 'assets/tailwind-play.js?v=' . CHATTYPAGE_VERSION;
		echo '<script src="' . esc_url( $tailwind ) . '"></script>' . "\n";
		echo '<script>tailwind.config = { corePlugins: { preflight: false } };</script>' . "\n";

		self::print_style_fragment( 'reset-css', 'chattypage-reset' );

		// The article typography ships only when the chrome takeover renders content pages.
		if ( class_exists( 'ChattyPage_Template' ) && ChattyPage_Template::is_enabled() ) {
			self::print_style_fragment( 'article-css', 'chattypage-article-css' );
		}
	}

	/** Print one cached CSS fragment as an inline <style> (fragments come from the Renderer funnel). */
	private static function print_style_fragment( $fragment, $element_id ) {
		$css = ChattyPage_Renderer::fragment( $fragment );
		if ( '' === $css ) {
			return;
		}
		// Defense in depth: a stylesheet must never be able to close its <style> tag.
		$css = str_ireplace( '</style', '', $css );
		echo '<style id="' . esc_attr( $element_id ) . '">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function flush() {
		// Fragment transients are owned by the Renderer; kept for back-compat with old installs.
		delete_transient( 'chattypage_reset_css' );
	}
}
