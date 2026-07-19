<?php
/**
 * ChattyPage_Shortcode — `[chattypage section="<id>"]`, the placement mechanism that works in
 * every editor and theme (classic editor, widgets, page builders with shortcode support).
 * Thin wrapper over the shared renderer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ChattyPage_Shortcode {

	public static function init() {
		add_shortcode( 'chattypage', array( __CLASS__, 'render' ) );
	}

	public static function render( $atts ) {
		$atts = shortcode_atts( array( 'section' => '' ), $atts, 'chattypage' );
		return ChattyPage_Renderer::render( $atts['section'] );
	}
}
