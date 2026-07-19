<?php
/**
 * ChattyPage_Api_Client — the single place that talks to the ChattyPage service
 * (/integration/v1/*, bearer-authenticated with the account's integration token).
 * Every call is a server-side wp_remote_* request; no visitor traffic ever hits ChattyPage.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ChattyPage_Api_Client {

	const OPTION = 'chattypage_settings';

	/** @return array{token:string,webhook_secret:string,designable_id:string,page_id:string,site_url:string} */
	public static function settings() {
		return wp_parse_args( get_option( self::OPTION, array() ), array(
			'token'          => '',
			'webhook_secret' => '',
			'designable_id'  => '',
			'page_id'        => '',
			'site_url'       => '',
		) );
	}

	public static function is_connected() {
		$s = self::settings();
		return ! empty( $s['token'] ) && ! empty( $s['designable_id'] );
	}

	/**
	 * Low-level GET against /integration/v1. Returns the decoded JSON envelope's `response`
	 * (or raw body for text/html endpoints), or a WP_Error.
	 */
	public static function get( $path, $token = null ) {
		return self::request( 'GET', $path, null, $token );
	}

	public static function post( $path, $body, $token = null ) {
		return self::request( 'POST', $path, $body, $token );
	}

	private static function request( $method, $path, $body, $token ) {
		$token = $token ?: self::settings()['token'];
		if ( empty( $token ) ) {
			return new WP_Error( 'chattypage_no_token', __( 'No ChattyPage token configured.', 'chattypage' ) );
		}

		$args = array(
			'method'  => $method,
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
		);
		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$res = wp_remote_request( trailingslashit( CHATTYPAGE_API_BASE ) . 'integration/v1/' . ltrim( $path, '/' ), $args );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$code = wp_remote_retrieve_response_code( $res );
		$raw  = wp_remote_retrieve_body( $res );
		if ( $code < 200 || $code >= 300 ) {
			$decoded = json_decode( $raw, true );
			$message = isset( $decoded['response']['error']['message'] )
				? $decoded['response']['error']['message']
				: sprintf( __( 'ChattyPage API error (HTTP %d).', 'chattypage' ), $code );
			return new WP_Error( 'chattypage_http_' . $code, $message );
		}

		$content_type = wp_remote_retrieve_header( $res, 'content-type' );
		if ( is_string( $content_type ) && 0 === strpos( $content_type, 'text/' ) ) {
			return $raw; // fragment endpoints (text/html, text/css): the body IS the payload
		}

		$decoded = json_decode( $raw, true );
		return isset( $decoded['response'] ) ? $decoded['response'] : $decoded;
	}

	/**
	 * Register (or re-register) this site with ChattyPage: sends the site URL + our webhook
	 * endpoint + a freshly minted shared secret, and stores the returned connection ids.
	 * Called from the settings screen after the user saves a token.
	 *
	 * @return true|WP_Error
	 */
	public static function connect( $token ) {
		$secret = wp_generate_password( 48, false, false );

		$response = self::post( 'connect', array(
			'siteUrl'       => home_url( '/' ),
			'webhookUrl'    => rest_url( 'chattypage/v1/refresh' ),
			'webhookSecret' => $secret,
		), $token );

		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( empty( $response['designableId'] ) ) {
			return new WP_Error( 'chattypage_bad_connect', __( 'Unexpected response from ChattyPage.', 'chattypage' ) );
		}

		update_option( self::OPTION, array(
			'token'          => $token,
			'webhook_secret' => $secret,
			'designable_id'  => (string) $response['designableId'],
			'page_id'        => isset( $response['pageId'] ) ? (string) $response['pageId'] : '',
			'site_url'       => home_url( '/' ),
		), false );

		return true;
	}

	public static function disconnect() {
		delete_option( self::OPTION );
		ChattyPage_Renderer::flush_all();
	}

	/**
	 * The connection's placeable sections, cached briefly so admin pickers stay snappy.
	 *
	 * @return array<int,array{id:string,name:string,updated_at:string,hasContent:bool}>|WP_Error
	 */
	public static function sections( $force = false ) {
		$cached = get_transient( 'chattypage_sections_index' );
		if ( ! $force && is_array( $cached ) ) {
			return $cached;
		}
		$response = self::get( 'sections' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$sections = isset( $response['sections'] ) && is_array( $response['sections'] ) ? $response['sections'] : array();
		set_transient( 'chattypage_sections_index', $sections, 5 * MINUTE_IN_SECONDS );
		return $sections;
	}

	/** Account/connection summary for the settings screen header. */
	public static function me() {
		return self::get( 'me' );
	}

	/**
	 * Ask ChattyPage to redesign one page of this site as a new section (queued generation in
	 * the connected account; uses the account's credits).
	 *
	 * @return array{queued:bool,blockId:string}|WP_Error
	 */
	public static function redesign( $url ) {
		return self::post( 'redesign', array( 'url' => $url ) );
	}
}
