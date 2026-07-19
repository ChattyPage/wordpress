<?php
/**
 * Uninstall cleanup: remove every option and transient the plugin created. The user's designs
 * live in their ChattyPage account and are untouched by uninstalling the plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$known = (array) get_option( 'chattypage_cached_sections', array() );
foreach ( $known as $id ) {
	if ( is_string( $id ) ) {
		delete_transient( 'chattypage_section_' . $id );
	}
}
delete_transient( 'chattypage_sections_index' );
delete_transient( 'chattypage_reset_css' );
foreach ( array( 'header', 'footer', 'article-css', 'reset-css' ) as $fragment ) {
	delete_transient( 'chattypage_fragment_' . $fragment );
}
delete_option( 'chattypage_cached_sections' );
delete_option( 'chattypage_settings' );
delete_option( 'chattypage_takeover' );
