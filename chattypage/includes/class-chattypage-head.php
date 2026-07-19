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

	const RESET_TRANSIENT = 'chattypage_reset_css';
	const RESET_TTL       = DAY_IN_SECONDS;

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

		$reset = self::reset_css();
		if ( '' !== $reset ) {
			// Our own stylesheet from the connected service — printed verbatim like the sections.
			echo '<style id="chattypage-reset">' . $reset . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/** The scoped mini-reset, cache-first (refreshed by the same webhook flush as sections). */
	public static function reset_css() {
		$cached = get_transient( self::RESET_TRANSIENT );
		if ( false !== $cached ) {
			return (string) $cached;
		}
		$css = ChattyPage_Api_Client::get( 'template/reset-css' );
		if ( is_wp_error( $css ) || ! is_string( $css ) ) {
			set_transient( self::RESET_TRANSIENT, '', 5 * MINUTE_IN_SECONDS ); // negative-cache
			return '';
		}
		// Defense in depth: a stylesheet must never be able to close its <style> tag.
		$css = str_ireplace( '</style', '', $css );
		set_transient( self::RESET_TRANSIENT, $css, self::RESET_TTL );
		return $css;
	}

	public static function flush() {
		delete_transient( self::RESET_TRANSIENT );
	}
}
