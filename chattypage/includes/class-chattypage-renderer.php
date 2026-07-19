<?php
/**
 * ChattyPage_Renderer — THE single render funnel for a section, shared by the shortcode, the
 * Gutenberg block, and the Elementor widget. A section's HTML is fetched server-side from
 * ChattyPage once, cached in a transient, and served inline from then on — so pages stay fast,
 * fully crawlable (the HTML is in the document, no iframe/client fetch), and keep working even
 * if ChattyPage is briefly unreachable. ChattyPage pings /wp-json/chattypage/v1/refresh on every
 * publish, which busts these transients (see ChattyPage_Rest).
 *
 * The fetched HTML is printed UNSANITIZED by design: it is the site owner's own design, produced
 * by the service their account token points at, and it carries scoped <style>/<script> that
 * wp_kses would destroy. It is the same trust model as a theme file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ChattyPage_Renderer {

	const CACHE_PREFIX = 'chattypage_section_';
	const CACHE_TTL    = 12 * HOUR_IN_SECONDS;
	// Registry of cached section ids, so flush_all() can delete them without a slow LIKE query.
	const INDEX_OPTION = 'chattypage_cached_sections';

	/**
	 * The section's HTML, cache-first. Returns '' (renders nothing) when the id is missing,
	 * the plugin is unconnected, or the first-ever fetch fails — a broken section must never
	 * break the page around it.
	 */
	public static function html( $section_id ) {
		$section_id = self::sanitize_id( $section_id );
		if ( '' === $section_id || ! ChattyPage_Api_Client::is_connected() ) {
			return '';
		}

		$cached = get_transient( self::CACHE_PREFIX . $section_id );
		if ( false !== $cached ) {
			return (string) $cached;
		}

		$html = ChattyPage_Api_Client::get( 'sections/' . $section_id . '/html' );
		if ( is_wp_error( $html ) || ! is_string( $html ) ) {
			// Negative-cache a short empty result so a dead section doesn't slow every request.
			set_transient( self::CACHE_PREFIX . $section_id, '', 5 * MINUTE_IN_SECONDS );
			return '';
		}

		set_transient( self::CACHE_PREFIX . $section_id, $html, self::CACHE_TTL );
		self::remember( $section_id );
		return $html;
	}

	/** Render with the wrapper the editors expect (a stable, targetable container). */
	public static function render( $section_id ) {
		$html = self::html( $section_id );
		if ( '' === $html ) {
			// Editors/admins get a visible placeholder; visitors get nothing.
			if ( current_user_can( 'edit_posts' ) ) {
				return '<div class="chattypage-section chattypage-section--empty" data-chattypage-section="' . esc_attr( $section_id ) . '">'
					. esc_html__( 'ChattyPage section not available. Check the section id and connection.', 'chattypage' )
					. '</div>';
			}
			return '';
		}
		return '<div class="chattypage-section" data-chattypage-section="' . esc_attr( $section_id ) . '">' . $html . '</div>';
	}

	/** Drop specific sections' caches (webhook refresh), or all when $ids is empty. */
	public static function flush( array $ids ) {
		if ( empty( $ids ) ) {
			self::flush_all();
			return;
		}
		foreach ( $ids as $id ) {
			$id = self::sanitize_id( $id );
			if ( '' !== $id ) {
				delete_transient( self::CACHE_PREFIX . $id );
			}
		}
		delete_transient( 'chattypage_sections_index' );
	}

	public static function flush_all() {
		$known = get_option( self::INDEX_OPTION, array() );
		foreach ( (array) $known as $id ) {
			delete_transient( self::CACHE_PREFIX . $id );
		}
		delete_option( self::INDEX_OPTION );
		delete_transient( 'chattypage_sections_index' );
	}

	private static function remember( $section_id ) {
		$known = (array) get_option( self::INDEX_OPTION, array() );
		if ( ! in_array( $section_id, $known, true ) ) {
			$known[] = $section_id;
			update_option( self::INDEX_OPTION, $known, false );
		}
	}

	/** Section ids are Mongo ObjectIds: 24 hex chars. Anything else is rejected outright. */
	public static function sanitize_id( $value ) {
		$value = is_string( $value ) ? strtolower( trim( $value ) ) : '';
		return preg_match( '/^[0-9a-f]{24}$/', $value ) ? $value : '';
	}
}
