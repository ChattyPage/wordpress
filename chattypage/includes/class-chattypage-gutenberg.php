<?php
/**
 * ChattyPage_Gutenberg — the `chattypage/section` dynamic block. Server-rendered through the
 * shared renderer (so the published page carries the real HTML), with a plain-JS editor control
 * (no build step): a dropdown of the account's sections fed by /wp-json/chattypage/v1/sections.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ChattyPage_Gutenberg {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		wp_register_script(
			'chattypage-section-block',
			CHATTYPAGE_PLUGIN_URL . 'blocks/section/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-api-fetch', 'wp-i18n', 'wp-server-side-render' ),
			CHATTYPAGE_VERSION,
			true
		);

		register_block_type( CHATTYPAGE_PLUGIN_DIR . 'blocks/section', array(
			'render_callback' => array( __CLASS__, 'render' ),
		) );
	}

	public static function render( $attributes ) {
		$section = isset( $attributes['sectionId'] ) ? $attributes['sectionId'] : '';
		return ChattyPage_Renderer::render( $section );
	}
}
