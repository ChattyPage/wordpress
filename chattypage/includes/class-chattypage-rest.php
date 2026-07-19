<?php
/**
 * ChattyPage_Rest — the plugin's REST surface:
 *
 *  POST /wp-json/chattypage/v1/refresh   (public, HMAC-authenticated)
 *      ChattyPage calls this after every publish. The payload {"blockIds":[...],"ts":<unix>} is
 *      signed with the connection's shared secret: X-ChattyPage-Signature =
 *      hex(HMAC-SHA256(secret, "<ts>.<rawBody>")). A stale timestamp (>5 min) or bad signature
 *      is rejected, so the endpoint cannot be used to flush caches by strangers.
 *
 *  GET /wp-json/chattypage/v1/sections   (edit_posts)
 *      The section list for the Gutenberg/Elementor pickers, proxied + briefly cached.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ChattyPage_Rest {

	const TS_TOLERANCE = 5 * MINUTE_IN_SECONDS;

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route( 'chattypage/v1', '/refresh', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_refresh' ),
			'permission_callback' => '__return_true', // auth = the HMAC check inside
		) );

		register_rest_route( 'chattypage/v1', '/sections', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'handle_sections' ),
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		) );
	}

	/** @param WP_REST_Request $request */
	public static function handle_refresh( $request ) {
		$secret = ChattyPage_Api_Client::settings()['webhook_secret'];
		if ( empty( $secret ) ) {
			return new WP_Error( 'chattypage_not_connected', 'Not connected.', array( 'status' => 409 ) );
		}

		$raw       = $request->get_body();
		$signature = (string) $request->get_header( 'x-chattypage-signature' );
		$ts        = (int) $request->get_header( 'x-chattypage-timestamp' );

		if ( abs( time() - $ts ) > self::TS_TOLERANCE ) {
			return new WP_Error( 'chattypage_stale', 'Stale timestamp.', array( 'status' => 403 ) );
		}
		$expected = hash_hmac( 'sha256', $ts . '.' . $raw, $secret );
		if ( ! hash_equals( $expected, strtolower( $signature ) ) ) {
			return new WP_Error( 'chattypage_bad_signature', 'Bad signature.', array( 'status' => 403 ) );
		}

		$payload  = json_decode( $raw, true );
		$blockIds = isset( $payload['blockIds'] ) && is_array( $payload['blockIds'] ) ? $payload['blockIds'] : array();
		ChattyPage_Renderer::flush( $blockIds );

		return rest_ensure_response( array( 'refreshed' => true, 'count' => count( $blockIds ) ) );
	}

	public static function handle_sections() {
		$sections = ChattyPage_Api_Client::sections();
		if ( is_wp_error( $sections ) ) {
			return new WP_Error( 'chattypage_upstream', $sections->get_error_message(), array( 'status' => 502 ) );
		}
		return rest_ensure_response( $sections );
	}
}
